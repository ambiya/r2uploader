<?php
declare(strict_types=1);

namespace R2Uploader\Service;

use DateTimeInterface;

/**
 * Auto-retention policy: keeps only the N newest files per folder.
 *
 * Uses R2Service::listAllObjects() for full pagination support (>1000 files).
 * Oldest files exceeding the limit are automatically deleted.
 */
class AutoRetention
{
    private R2Service $r2;

    /** @var int Maximum number of files to retain per folder */
    private int $maxFiles;

    /**
     * @param R2Service $r2       The R2 service for bucket operations
     * @param int       $maxFiles Maximum files to keep (0 or negative disables pruning)
     */
    public function __construct(R2Service $r2, int $maxFiles)
    {
        $this->r2 = $r2;
        $this->maxFiles = $maxFiles;
    }

    /**
     * Prune a folder to keep only the N newest files.
     *
     * Lists ALL objects under the given prefix (auto-paginating past the
     * 1000-object S3 limit), sorts by LastModified ascending, and deletes
     * the oldest files that exceed $maxFiles.
     *
     * @param string $bucket       The R2 bucket name
     * @param string $folderPrefix The folder prefix to prune (e.g. "uploads/photos/")
     *
     * @return string[] Array of deleted object keys
     */
    public function prune(string $bucket, string $folderPrefix): array
    {
        if ($this->maxFiles <= 0) {
            return [];
        }

        // Use listAllObjects for full pagination support (>1000 files)
        $objects = $this->r2->listAllObjects($bucket, $folderPrefix);

        $totalCount = count($objects);
        if ($totalCount <= $this->maxFiles) {
            return [];
        }

        // Sort by LastModified ascending (oldest first)
        usort($objects, function ($a, $b) {
            $timeA = $this->getTimestamp($a['LastModified'] ?? null);
            $timeB = $this->getTimestamp($b['LastModified'] ?? null);
            return $timeA <=> $timeB;
        });

        // Calculate how many to delete
        $deleteCount = $totalCount - $this->maxFiles;
        $toDelete = array_slice($objects, 0, $deleteCount);

        $deletedKeys = [];
        foreach ($toDelete as $obj) {
            $key = $obj['Key'];
            $this->r2->deleteObject($bucket, $key);
            $deletedKeys[] = $key;
        }

        // Log the retention action
        $kept = $totalCount - $deleteCount;
        error_log(sprintf(
            '[r2uploader] auto-retention deleted=%d kept=%d bucket=%s prefix=%s keys=[%s]',
            $deleteCount,
            $kept,
            $bucket,
            $folderPrefix,
            implode(', ', $deletedKeys)
        ));

        return $deletedKeys;
    }

    /**
     * Extract a Unix timestamp from a LastModified value.
     *
     * Handles both DateTimeInterface instances (from AWS SDK)
     * and raw date strings.
     *
     * @param DateTimeInterface|string|null $lastModified
     *
     * @return int Unix timestamp
     */
    private function getTimestamp($lastModified): int
    {
        if ($lastModified instanceof DateTimeInterface) {
            return $lastModified->getTimestamp();
        }

        if (is_string($lastModified) && !empty($lastModified)) {
            $ts = strtotime($lastModified);
            return $ts !== false ? $ts : 0;
        }

        return 0;
    }
}
