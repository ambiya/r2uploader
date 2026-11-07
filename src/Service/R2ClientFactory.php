<?php
declare(strict_types=1);

namespace R2Uploader\Service;

use Aws\S3\S3Client;

/**
 * Factory for creating and caching R2Service instances per bucket or using global fallback credentials.
 */
class R2ClientFactory
{
    /** @var array{accountId: string, accessKeyId: string, secretAccessKey: string} */
    private array $globalCredentials;
    private BucketResolver $bucketResolver;
    /** @var array<string, R2Service> Cached R2Service instances */
    private array $instances = [];

    /**
     * @param array{accountId?: string, accessKeyId?: string, secretAccessKey?: string} $globalCredentials
     * @param BucketResolver $bucketResolver
     */
    public function __construct(array $globalCredentials, BucketResolver $bucketResolver)
    {
        $this->globalCredentials = [
            'accountId'       => (string) ($globalCredentials['accountId'] ?? ''),
            'accessKeyId'     => (string) ($globalCredentials['accessKeyId'] ?? ''),
            'secretAccessKey' => (string) ($globalCredentials['secretAccessKey'] ?? ''),
        ];
        $this->bucketResolver = $bucketResolver;
    }

    /**
     * Resolve the effective credentials for a given bucket configuration array.
     * Falls back to global credentials for any missing or empty fields.
     *
     * @param array{name?: string, publicUrl?: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}|null $bucket
     * @return array{accountId: string, accessKeyId: string, secretAccessKey: string}|null
     */
    public function getCredentialsForBucket(?array $bucket): ?array
    {
        $accountId = !empty($bucket['accountId']) ? trim((string) $bucket['accountId']) : $this->globalCredentials['accountId'];
        $accessKeyId = !empty($bucket['accessKeyId']) ? trim((string) $bucket['accessKeyId']) : $this->globalCredentials['accessKeyId'];
        $secretAccessKey = !empty($bucket['secretAccessKey']) ? (string) $bucket['secretAccessKey'] : $this->globalCredentials['secretAccessKey'];

        if ($accountId === '' || $accessKeyId === '' || $secretAccessKey === '') {
            return null;
        }

        return [
            'accountId'       => $accountId,
            'accessKeyId'     => $accessKeyId,
            'secretAccessKey' => $secretAccessKey,
        ];
    }

    /**
     * Get or create an R2Service instance for a specific bucket configuration.
     *
     * @param array{name?: string, publicUrl?: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}|null $bucket
     */
    public function getForBucket(?array $bucket): ?R2Service
    {
        $creds = $this->getCredentialsForBucket($bucket);
        if ($creds === null) {
            return null;
        }

        $cacheKey = md5($creds['accountId'] . ':' . $creds['accessKeyId'] . ':' . $creds['secretAccessKey']);
        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        try {
            $s3Client = new S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'    => "https://{$creds['accountId']}.r2.cloudflarestorage.com",
                'credentials' => [
                    'key'    => $creds['accessKeyId'],
                    'secret' => $creds['secretAccessKey'],
                ],
            ]);
            $service = new R2Service($s3Client);
            $this->instances[$cacheKey] = $service;
            return $service;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get default R2Service using global credentials.
     */
    public function getDefault(): ?R2Service
    {
        return $this->getForBucket(null);
    }

    /**
     * Check if at least one bucket or the global configuration is valid.
     */
    public function hasAnyValidConfiguration(): bool
    {
        if ($this->getDefault() !== null) {
            return true;
        }
        foreach ($this->bucketResolver->all() as $bucket) {
            if ($this->getForBucket($bucket) !== null) {
                return true;
            }
        }
        return false;
    }
}
