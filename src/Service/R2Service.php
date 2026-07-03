<?php
declare(strict_types=1);

namespace R2Uploader\Service;

use Aws\S3\S3Client;

/**
 * Wrapper around AWS S3Client for Cloudflare R2 operations.
 */
class R2Service
{
    private S3Client $client;

    public function __construct(S3Client $client)
    {
        $this->client = $client;
    }

    /**
     * List objects in a bucket with prefix/delimiter.
     *
     * @return array{objects: array, prefixes: string[], isTruncated: bool, nextToken: ?string}
     */
    public function listObjects(string $bucket, string $prefix = '', int $maxKeys = 1000, ?string $continuationToken = null): array
    {
        $params = [
            'Bucket' => $bucket,
            'Prefix' => $prefix,
            'Delimiter' => '/',
            'MaxKeys' => $maxKeys,
        ];
        if (!empty($continuationToken)) {
            $params['ContinuationToken'] = $continuationToken;
        }

        $result = $this->client->listObjectsV2($params);

        $prefixes = [];
        if (isset($result['CommonPrefixes'])) {
            foreach ($result['CommonPrefixes'] as $cp) {
                $prefixes[] = $cp['Prefix'];
            }
        }

        $objects = [];
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $obj) {
                if ($obj['Key'] === $prefix) {
                    continue;
                }
                $objects[] = $obj;
            }
        }

        return [
            'objects' => $objects,
            'prefixes' => $prefixes,
            'isTruncated' => (bool) ($result['IsTruncated'] ?? false),
            'nextToken' => $result['NextContinuationToken'] ?? null,
        ];
    }

    /**
     * List ALL objects under a prefix, automatically paginating.
     *
     * @return array Flat array of S3 object entries
     */
    public function listAllObjects(string $bucket, string $prefix = ''): array
    {
        $allObjects = [];
        $continuationToken = null;

        do {
            $params = [
                'Bucket' => $bucket,
                'Prefix' => $prefix,
            ];
            if ($continuationToken !== null) {
                $params['ContinuationToken'] = $continuationToken;
            }

            $result = $this->client->listObjectsV2($params);

            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $obj) {
                    if ($obj['Key'] !== $prefix) {
                        $allObjects[] = $obj;
                    }
                }
            }

            $continuationToken = $result['NextContinuationToken'] ?? null;
        } while (!empty($result['IsTruncated']) && $continuationToken !== null);

        return $allObjects;
    }

    /**
     * Upload a file or stream to R2.
     *
     * @param string $bucket
     * @param string $key
     * @param string|resource $body File path, stream resource, or string content
     * @param string $contentType
     * @param string $fileName
     */
    public function putObject(string $bucket, string $key, $body, string $contentType, string $fileName): void
    {
        $openedHere = false;
        if (is_string($body) && is_file($body) && is_readable($body)) {
            $body = fopen($body, 'rb');
            $openedHere = true;
        }

        $options = [
            'params' => [
                'ContentType' => $contentType,
                'ContentDisposition' => "attachment; filename=\"{$fileName}\"",
            ]
        ];

        try {
            // The upload() helper automatically handles multipart uploads for large files/streams
            $this->client->upload($bucket, $key, $body, 'private', $options);
        } finally {
            if ($openedHere && is_resource($body)) {
                fclose($body);
            }
        }
    }

    /**
     * Delete an object from R2.
     */
    public function deleteObject(string $bucket, string $key): void
    {
        $this->client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }

    /**
     * Copy an object within the same bucket.
     */
    public function copyObject(string $bucket, string $sourceKey, string $newKey): void
    {
        $this->client->copyObject([
            'Bucket' => $bucket,
            'Key' => $newKey,
            'CopySource' => $bucket . '/' . rawurlencode($sourceKey),
        ]);
    }

    /**
     * Get storage statistics for a bucket.
     *
     * @return array{totalFiles: int, totalSize: int}
     */
    public function getStorageStats(string $bucket, string $prefix = ''): array
    {
        $objects = $this->listAllObjects($bucket, $prefix);
        $totalSize = 0;
        $fileTypes = [];
        $largestFiles = [];

        foreach ($objects as $obj) {
            $size = (int) ($obj['Size'] ?? 0);
            $totalSize += $size;

            $ext = strtolower(pathinfo($obj['Key'], PATHINFO_EXTENSION));
            if (empty($ext)) {
                $ext = 'unknown';
            }
            if (!isset($fileTypes[$ext])) {
                $fileTypes[$ext] = 0;
            }
            $fileTypes[$ext]++;

            $largestFiles[] = [
                'Key' => $obj['Key'],
                'Size' => $size,
            ];
        }

        // Sort for largest files (descending size)
        usort($largestFiles, fn($a, $b) => $b['Size'] <=> $a['Size']);
        $largestFiles = array_slice($largestFiles, 0, 5);

        // Sort file types by count descending
        arsort($fileTypes);

        return [
            'totalFiles' => count($objects),
            'totalSize' => $totalSize,
            'fileTypes' => $fileTypes,
            'largestFiles' => $largestFiles,
        ];
    }
}
