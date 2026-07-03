<?php
declare(strict_types=1);

namespace R2Uploader\Config;

use R2Uploader\Service\SettingManager;

/**
 * Loads application configuration from the database with .env fallback.
 *
 * Extracted from App::loadConfig() to keep configuration logic separate
 * from bootstrapping.
 */
class ConfigLoader
{
    private SettingManager $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    /**
     * Load and return the full application configuration.
     *
     * @return array<string, mixed>
     */
    public function load(): array
    {
        $appDebug = $this->loadDebugFlag();
        $this->applyDebugSettings($appDebug);

        $folderMaxFiles = $this->loadFolderMaxFiles();
        $buckets        = $this->loadBuckets();
        $allowedExts    = $this->loadAllowedExtensions();

        return [
            'debug'             => $appDebug,
            'folderMaxFiles'    => $folderMaxFiles,
            'allowedExtensions' => $allowedExts,
            'accountId'         => $this->settingManager->get('R2_ACCOUNT_ID', $_ENV['R2_ACCOUNT_ID'] ?? ''),
            'accessKeyId'       => $this->settingManager->get('R2_ACCESS_KEY_ID', $_ENV['R2_ACCESS_KEY_ID'] ?? ''),
            'secretAccessKey'   => $this->settingManager->get('R2_SECRET_ACCESS_KEY', $_ENV['R2_SECRET_ACCESS_KEY'] ?? ''),
            'buckets'           => $buckets,
            'auth' => [
                'user' => $_ENV['AUTH_USER'] ?? '',
                'pass' => $_ENV['AUTH_PASS'] ?? '',
            ],
        ];
    }

    private function loadDebugFlag(): bool
    {
        $dbDebug = $this->settingManager->get('APP_DEBUG');
        return $dbDebug !== null
            ? filter_var($dbDebug, FILTER_VALIDATE_BOOLEAN)
            : filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    }

    private function applyDebugSettings(bool $appDebug): void
    {
        ini_set('display_errors', $appDebug ? '1' : '0');
        ini_set('display_startup_errors', $appDebug ? '1' : '0');
        error_reporting(E_ALL);
    }

    private function loadFolderMaxFiles(): int
    {
        $dbFolderMax = $this->settingManager->get('FOLDER_MAX_FILES');
        $folderMaxFiles = $dbFolderMax !== null
            ? filter_var($dbFolderMax, FILTER_VALIDATE_INT)
            : filter_var($_ENV['FOLDER_MAX_FILES'] ?? '0', FILTER_VALIDATE_INT);

        if ($folderMaxFiles === false) {
            $folderMaxFiles = 0;
        }

        return max(0, min(1000, $folderMaxFiles));
    }

    /**
     * @return array<string, array{name: string, publicUrl: string}>
     */
    private function loadBuckets(): array
    {
        $bucketsJson = $this->settingManager->get('R2_BUCKETS');
        if ($bucketsJson !== null) {
            $decoded = json_decode($bucketsJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * @return string[]
     */
    private function loadAllowedExtensions(): array
    {
        $rawExts = $this->settingManager->get('ALLOWED_EXTENSIONS', $_ENV['ALLOWED_EXTENSIONS'] ?? '');
        if (trim((string) $rawExts) !== '') {
            return array_filter(array_map('trim', explode(',', strtolower((string) $rawExts))));
        }
        return [];
    }
}
