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
     * Get an object from R2.
     *
     * @return array S3 result containing Body, ContentType, etc.
     */
    public function getObject(string $bucket, string $key): array
    {
        return $this->client->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ])->toArray();
    }

    /**
     * Delete multiple objects from R2.
     */
    public function deleteObjects(string $bucket, array $keys): void
    {
        $objects = array_map(fn($key) => ['Key' => $key], $keys);
        $this->client->deleteObjects([
            'Bucket' => $bucket,
            'Delete' => [
                'Objects' => $objects,
            ],
        ]);
    }

    /**
     * Create an empty directory placeholder in R2.
     */
    public function createFolder(string $bucket, string $key): void
    {
        $this->client->putObject([
            'Bucket' => $bucket,
            'Key' => rtrim($key, '/') . '/',
            'Body' => '',
            'ContentType' => 'application/x-directory',
        ]);
    }
}
