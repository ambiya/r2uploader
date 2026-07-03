<?php
declare(strict_types=1);

namespace R2Uploader\Service;

/**
 * Manages application settings stored in a SQLite database.
 */
class SettingManager
{
    private \PDO $db;

    public function __construct(string $dbPath)
    {
        $this->db = new \PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->initSchema();
    }

    /**
     * Initialize settings table if it does not exist.
     */
    private function initSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );
        ");
    }

    /**
     * Get a setting value by key, with a fallback default.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }

    /**
     * Set/save a setting value. If null is passed, the key will be deleted.
     */
    public function set(string $key, ?string $value): void
    {
        if ($value === null) {
            $stmt = $this->db->prepare("DELETE FROM settings WHERE key = ?");
            $stmt->execute([$key]);
        } else {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }

    /**
     * Fetch all stored settings.
     *
     * @return array<string, string>
     */
    public function getAll(): array
    {
        $rows = $this->db->query("SELECT key, value FROM settings")->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
}
