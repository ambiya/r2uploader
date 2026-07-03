<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class DashboardViewData extends ViewData
{
    /** @var array<int, array{id: int, user_id: int|null, username: string|null, action: string, bucket: string|null, object_key: string|null, size_bytes: int, ip_address: string, details: string|null, created_at: string}> */
    public array $activities;
    /** @var array<int, array{username: string|null, total_actions: int, total_uploads: int, total_bytes: int, last_active: string}> */
    public array $userStats;
    /** @var array<string, array{type?: string, totalFiles?: int, totalSize?: string, error?: string}> */
    public array $r2Stats;
    public bool $isConfigured;
    public string $csrfToken;

    public function __construct(
        array $activities,
        array $userStats,
        array $r2Stats,
        bool $isConfigured,
        string $csrfToken = ''
    ) {
        $this->activities = $activities;
        $this->userStats = $userStats;
        $this->r2Stats = $r2Stats;
        $this->isConfigured = $isConfigured;
        $this->csrfToken = $csrfToken;
    }
}
