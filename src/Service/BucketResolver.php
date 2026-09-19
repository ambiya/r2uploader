<?php
declare(strict_types=1);

namespace R2Uploader\Service;

/**
 * Resolves bucket configuration from a type string.
 *
 * Extracted from App::resolveBucket() so controllers can depend on this
 * small service instead of the entire App instance.
 */
class BucketResolver
{
    /** @var array<string, array{name: string, publicUrl: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}> */
    private array $buckets;

    /**
     * @param array<string, array{name: string, publicUrl: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}> $buckets
     */
    public function __construct(array $buckets)
    {
        $this->buckets = $buckets;
    }

    /**
     * Resolve bucket config for a given type.
     *
     * @return array{name: string, publicUrl: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}|null
     */
    public function resolve(?string $type): ?array
    {
        if ($type !== null && isset($this->buckets[$type])) {
            return $this->buckets[$type];
        }
        return null;
    }

    /**
     * Resolve bucket config by physical bucket name.
     *
     * @return array{name: string, publicUrl: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}|null
     */
    public function resolveByName(string $name): ?array
    {
        foreach ($this->buckets as $bucket) {
            if (($bucket['name'] ?? '') === $name) {
                return $bucket;
            }
        }
        return null;
    }

    /**
     * Get all configured buckets.
     *
     * @return array<string, array{name: string, publicUrl: string, accountId?: string, accessKeyId?: string, secretAccessKey?: string}>
     */
    public function all(): array
    {
        return $this->buckets;
    }

    /**
     * Get the first bucket type key, or null if none configured.
     */
    public function firstType(): ?string
    {
        if (empty($this->buckets)) {
            return null;
        }
        return (string) array_key_first($this->buckets);
    }

    /**
     * Check if any buckets are configured.
     */
    public function hasBuckets(): bool
    {
        return !empty($this->buckets);
    }
}
