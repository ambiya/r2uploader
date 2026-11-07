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

    public function __construct(
        string $csrfToken,
        ?string $type,
        array $buckets,
        string $prefix
    ) {
        $this->csrfToken = $csrfToken;
        $this->type = $type;
        $this->buckets = $buckets;
        $this->prefix = $prefix;
    }
}
