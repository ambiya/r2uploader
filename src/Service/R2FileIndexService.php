<?php
declare(strict_types=1);

namespace R2Uploader\Service;

class R2FileIndexService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS file_index (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bucket TEXT NOT NULL,
                object_key TEXT NOT NULL,
                size INTEGER NOT NULL,
                last_modified TEXT,
                is_dir INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(bucket, object_key)
            );
            CREATE INDEX IF NOT EXISTS idx_file_index_bucket_key ON file_index(bucket, object_key);
        ");
    }

    public function isEmpty(string $bucket): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM file_index WHERE bucket = ?");
        $stmt->execute([$bucket]);
        return ((int) $stmt->fetchColumn()) === 0;
    }

    public function syncBucket(string $bucket, R2Service $r2): void
    {
        $allObjects = $r2->listAllObjects($bucket);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM file_index WHERE bucket = ?");
            $stmt->execute([$bucket]);

            $stmtInsert = $this->db->prepare("
                INSERT OR REPLACE INTO file_index (bucket, object_key, size, last_modified, is_dir)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($allObjects as $obj) {
                $key = $obj['Key'];
                $size = (int)$obj['Size'];
                $lastModified = isset($obj['LastModified']) ? $obj['LastModified']->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
                $isDir = str_ends_with($key, '/') ? 1 : 0;

                $stmtInsert->execute([$bucket, $key, $size, $lastModified, $isDir]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addObject(string $bucket, string $key, int $size, string $lastModified, bool $isDir = false): void
    {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO file_index (bucket, object_key, size, last_modified, is_dir)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$bucket, $key, $size, $lastModified, $isDir ? 1 : 0]);
    }

    public function deleteObject(string $bucket, string $key): void
    {
        $stmt = $this->db->prepare("DELETE FROM file_index WHERE bucket = ? AND object_key = ?");
        $stmt->execute([$bucket, $key]);

        if (str_ends_with($key, '/')) {
            $stmt = $this->db->prepare("DELETE FROM file_index WHERE bucket = ? AND object_key LIKE ?");
            $stmt->execute([$bucket, $key . '%']);
        }
    }

    public function deleteObjects(string $bucket, array $keys): void
    {
        if (empty($keys)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->db->prepare("DELETE FROM file_index WHERE bucket = ? AND object_key IN ($placeholders)");
        $stmt->execute(array_merge([$bucket], $keys));
    }

    public function renameObject(string $bucket, string $oldKey, string $newKey): void
    {
        $stmt = $this->db->prepare("SELECT * FROM file_index WHERE bucket = ? AND object_key = ?");
        $stmt->execute([$bucket, $oldKey]);
        $row = $stmt->fetch();

        if ($row) {
            $this->db->beginTransaction();
            try {
                $stmtDel = $this->db->prepare("DELETE FROM file_index WHERE bucket = ? AND object_key = ?");
                $stmtDel->execute([$bucket, $oldKey]);

                $stmtIns = $this->db->prepare("
                    INSERT OR REPLACE INTO file_index (bucket, object_key, size, last_modified, is_dir)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([
                    $bucket,
                    $newKey,
                    $row['size'],
                    $row['last_modified'],
                    $row['is_dir']
                ]);

                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }
    }

    public function listAll(string $bucket, string $prefix = ''): array
    {
        $stmt = $this->db->prepare("
            SELECT object_key, size, last_modified, is_dir 
            FROM file_index 
            WHERE bucket = ? AND object_key LIKE ? AND is_dir = 0 AND object_key NOT LIKE '%/'
        ");
        $stmt->execute([$bucket, $prefix . '%']);
        $rows = $stmt->fetchAll();

        $objects = [];
        foreach ($rows as $row) {
            $objects[] = [
                'Key' => $row['object_key'],
                'Size' => (int)$row['size'],
                'LastModified' => new \DateTime($row['last_modified']),
            ];
        }

        return $objects;
    }

    public function listDirectory(string $bucket, string $prefix = ''): array
    {
        $prefixLength = strlen($prefix);
        $stmt = $this->db->prepare("
            SELECT object_key, size, last_modified, is_dir 
            FROM file_index 
            WHERE bucket = ? AND object_key LIKE ?
        ");
        $stmt->execute([$bucket, $prefix . '%']);
        $rows = $stmt->fetchAll();

        $objects = [];
        $prefixes = [];

        foreach ($rows as $row) {
            $key = $row['object_key'];
            if ($key === $prefix) {
                continue;
            }

            $relativePart = substr($key, $prefixLength);
            $slashPos = strpos($relativePart, '/');

            if ($slashPos === false) {
                if ($row['is_dir'] || str_ends_with($key, '/')) {
                    $prefixes[] = $key;
                } else {
                    $objects[] = [
                        'Key' => $key,
                        'Size' => (int)$row['size'],
                        'LastModified' => new \DateTime($row['last_modified']),
                    ];
                }
            } else {
                $subDir = $prefix . substr($relativePart, 0, $slashPos + 1);
                $prefixes[] = $subDir;
            }
        }

        $prefixes = array_values(array_unique($prefixes));

        return [
            'objects' => $objects,
            'prefixes' => $prefixes,
        ];
    }
}
