<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class ListViewData extends ViewData
{
    public string $csrfToken;
    public ?string $type;
    /** @var array<string, array{name: string, publicUrl: string}> */
    public array $buckets;
    public string $prefix;
    /** @var array<int, array{Key: string, Size: int, LastModified: \DateTimeInterface|string, IsDirectory: bool}> */
    public array $objects;
    /** @var string[] */
    public array $prefixes;
    public bool $isTruncated;
    public ?string $nextToken;
    public string $publicUrl;

    public function __construct(
        string $csrfToken,
        ?string $type,
        array $buckets,
        string $prefix,
        array $objects,
        array $prefixes,
        bool $isTruncated,
        ?string $nextToken,
        string $publicUrl
    ) {
        $this->csrfToken = $csrfToken;
        $this->type = $type;
        $this->buckets = $buckets;
        $this->prefix = $prefix;
        $this->objects = $objects;
        $this->prefixes = $prefixes;
        $this->isTruncated = $isTruncated;
        $this->nextToken = $nextToken;
        $this->publicUrl = $publicUrl;
    }
}
