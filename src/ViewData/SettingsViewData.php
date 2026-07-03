<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class SettingsViewData extends ViewData
{
    public string $csrfToken;
    /** @var array<string, mixed> */
    public array $config;
    public ?string $error;
    public ?string $success;

    public function __construct(string $csrfToken, array $config, ?string $error = null, ?string $success = null)
    {
        $this->csrfToken = $csrfToken;
        $this->config = $config;
        $this->error = $error;
        $this->success = $success;
    }
}
