<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class UploadViewData extends ViewData
{
    public string $csrfToken;
    public string $configError;
    public bool $isConfigured;
    /** @var array<string, array{name: string, publicUrl: string}> */
    public array $buckets;
    public string $folderRetentionNote;

    public function __construct(
        string $csrfToken,
        string $configError,
        bool $isConfigured,
        array $buckets,
        string $folderRetentionNote
    ) {
        $this->csrfToken = $csrfToken;
        $this->configError = $configError;
        $this->isConfigured = $isConfigured;
        $this->buckets = $buckets;
        $this->folderRetentionNote = $folderRetentionNote;
    }
}
