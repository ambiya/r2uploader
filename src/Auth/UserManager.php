<?php
declare(strict_types=1);

namespace R2Uploader\Auth;

/**
 * Manages SQLite-backed users and roles.
 */
class UserManager
{
    private \PDO $db;

    public function __construct(string $dbPath)
    {
        $this->db = new \PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'editor',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            );
            
            CREATE TABLE IF NOT EXISTS activity_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT NOT NULL,
                bucket TEXT,
                object_key TEXT,
                file_size INTEGER,
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
        ");
    }

    public function migrateFromEnv(string $envUser, string $envPass): bool
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        if ((int) $stmt->fetchColumn() === 0) {
            if ($envUser !== '' && $envPass !== '') {
                $this->createUser($envUser, $envPass, 'admin');
            }
        }
        return true;
    }

    public function createUser(string $username, string $password, string $role = 'editor'): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $role]);
        return (int) $this->db->lastInsertId();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function listAll(): array
    {
        return $this->db->query("SELECT id, username, role, created_at, last_login FROM users ORDER BY created_at DESC")->fetchAll();
    }
    
    public function deleteUser(int $id): bool
    {
        // Prevent deleting the last admin
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] === 'admin') {
            $adminCount = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                throw new \RuntimeException("Tidak dapat menghapus admin terakhir.");
            }
        }
        
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function updateUser(int $id, string $role, ?string $newPassword = null): void
    {
        if ($newPassword) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET role = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$role, $hash, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $id]);
        }
    }
}
