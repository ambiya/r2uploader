<?php
declare(strict_types=1);

namespace R2Uploader\Service;

use R2Uploader\Auth\SessionAuth;

/**
 * Logs user activity to the SQLite database.
 */
class ActivityLogger
{
    private \PDO $db;
    private SessionAuth $auth;

    public function __construct(string $dbPath, SessionAuth $auth)
    {
        $this->db = new \PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->auth = $auth;
    }

    public function log(string $action, ?string $bucket = null, ?string $objectKey = null, ?int $fileSize = null, ?string $details = null): void
    {
        $user = $this->auth->user();
        $userId = $user ? (int)$user['id'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO activity_log (user_id, action, bucket, object_key, file_size, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $bucket,
            $objectKey,
            $fileSize,
            $details
        ]);
    }

    public function getRecentActivity(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username 
            FROM activity_log a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        // Bind integer parameter properly for LIMIT
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getStorageStatsByBucket(): array
    {
        $stmt = $this->db->query("
            SELECT 
                bucket, 
                COUNT(*) as upload_count, 
                SUM(file_size) as total_uploaded_bytes
            FROM activity_log
            WHERE action = 'upload'
            GROUP BY bucket
        ");
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getUserActivityStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                u.username,
                COUNT(a.id) as total_actions,
                SUM(CASE WHEN a.action = 'upload' THEN 1 ELSE 0 END) as uploads,
                SUM(CASE WHEN a.action = 'delete' THEN 1 ELSE 0 END) as deletions
            FROM users u
            LEFT JOIN activity_log a ON u.id = a.user_id
            GROUP BY u.id
            ORDER BY total_actions DESC
        ");
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function clearAllActivity(): void
    {
        $this->db->exec("DELETE FROM activity_log");
    }
}
