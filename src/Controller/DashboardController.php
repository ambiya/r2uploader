<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Response;
use R2Uploader\Security\Csrf;
use R2Uploader\Service\ActivityLogger;
use R2Uploader\Service\BucketResolver;
use R2Uploader\Service\R2Service;
use R2Uploader\Service\R2FileIndexService;
use R2Uploader\ViewData\DashboardViewData;

class DashboardController extends BaseController
{
    private Csrf $csrf;
    private ActivityLogger $logger;
    private BucketResolver $bucketResolver;
    private ?R2Service $r2;
    private R2FileIndexService $fileIndex;

    public function __construct(
        Csrf $csrf,
        ActivityLogger $logger,
        BucketResolver $bucketResolver,
        ?R2Service $r2,
        R2FileIndexService $fileIndex
    ) {
        $this->csrf           = $csrf;
        $this->logger         = $logger;
        $this->bucketResolver = $bucketResolver;
        $this->r2             = $r2;
        $this->fileIndex      = $fileIndex;
    }

    /**
     * Auth + role check handled by AuthMiddleware('admin').
     */
    public function index(): Response
    {
        $activities = $this->logger->getRecentActivity(50);
        $userStats  = $this->logger->getUserActivityStats();
        $buckets    = $this->bucketResolver->all();

        // Fetch stats for configured buckets from index database (with R2 sync fallback if empty)
        $r2Stats = [];
        if ($this->r2) {
            foreach ($buckets as $type => $bucket) {
                if (!empty($bucket['name'])) {
                    try {
                        // Auto-sync if database index is empty for this bucket
                        if ($this->fileIndex->isEmpty($bucket['name'])) {
                            $this->fileIndex->syncBucket($bucket['name'], $this->r2);
                        }

                        $stats = $this->fileIndex->getStorageStats($bucket['name']);
                        $r2Stats[$bucket['name']] = [
                            'type'         => $type,
                            'totalFiles'   => $stats['totalFiles'],
                            'totalSize'    => $this->formatBytes($stats['totalSize']),
                            'fileTypes'    => $stats['fileTypes'] ?? [],
                            'largestFiles' => array_map(function($f) {
                                $f['FormattedSize'] = $this->formatBytes((int)$f['Size']);
                                return $f;
                            }, $stats['largestFiles'] ?? []),
                        ];
                    } catch (\Exception $e) {
                        $r2Stats[$bucket['name']] = ['error' => $e->getMessage()];
                    }
                }
            }
        }

        $rawDailyStats = $this->logger->getDailyActivityStats(7);
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyStats[$date] = [
                'log_date' => $date,
                'uploads' => 0,
                'deletions' => 0,
            ];
        }
        foreach ($rawDailyStats as $row) {
            $date = $row['log_date'];
            if (isset($dailyStats[$date])) {
                $dailyStats[$date]['uploads'] = (int)$row['uploads'];
                $dailyStats[$date]['deletions'] = (int)$row['deletions'];
            }
        }
        $dailyStats = array_values($dailyStats);

        $viewData = new DashboardViewData(
            $activities,
            $userStats,
            $r2Stats,
            $dailyStats,
            $this->r2 !== null,
            $this->csrf->getToken()
        );

        return $this->renderPage(__('nav_dashboard'), 'dashboard', $viewData, $this->csrf->getToken());
    }


    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    public function clearLogs(\R2Uploader\Http\Request $request): Response
    {
        if (!$this->csrf->validate($request->post('csrf_token'))) {
            return Response::error('Invalid CSRF token', 403, $request);
        }

        $this->logger->clearAllActivity();
        
        return Response::redirect('/?action=dashboard');
    }
}

