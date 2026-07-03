<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Service\SettingManager;
use R2Uploader\Security\Csrf;
use R2Uploader\Service\ActivityLogger;
use R2Uploader\ViewData\SettingsViewData;

class SettingsController extends BaseController
{
    private SettingManager $settings;
    private Csrf $csrf;
    private ActivityLogger $logger;
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        SettingManager $settings,
        Csrf $csrf,
        ActivityLogger $logger,
        array $config
    ) {
        $this->settings = $settings;
        $this->csrf     = $csrf;
        $this->logger   = $logger;
        $this->config   = $config;
    }

    /**
     * Show settings configuration page.
     * GET /?action=settings
     *
     * Auth + role check handled by AuthMiddleware('admin').
     */
    public function showSettings(): Response
    {
        $viewData = new SettingsViewData(
            $this->csrf->getToken(),
            $this->config,
            is_string($_SESSION['settings_error'] ?? null) ? $_SESSION['settings_error'] : null,
            is_string($_SESSION['settings_success'] ?? null) ? $_SESSION['settings_success'] : null
        );

        $response = $this->renderPage(__('system_settings'), 'settings', $viewData, $this->csrf->getToken());

        unset($_SESSION['settings_error'], $_SESSION['settings_success']);
        return $response;
    }


    /**
     * Handle saving settings.
     * POST /?action=settings_update
     *
     * Auth + role check handled by AuthMiddleware('admin').
     * CSRF validated by CsrfMiddleware.
     */
    public function updateSettings(Request $request): Response
    {
        try {
            // General Settings
            $appDebug = $request->post('app_debug') !== null ? 'true' : 'false';
            $folderMaxFilesInput = (string) $request->post('folder_max_files', '0');
            $folderMaxFiles = filter_var($folderMaxFilesInput, FILTER_VALIDATE_INT);
            if ($folderMaxFiles === false || $folderMaxFiles < 0) {
                throw new \InvalidArgumentException(__('err_invalid_retention'));
            }

            $allowedExtensions = trim((string) $request->post('allowed_extensions', ''));

            // Cloudflare R2 Credentials
            $accountId = trim((string) $request->post('r2_account_id', ''));
            $accessKeyId = trim((string) $request->post('r2_access_key_id', ''));
            $secretAccessKey = (string) $request->post('r2_secret_access_key', ''); // do not trim to support special characters

            // R2 Buckets Dynamic Configuration
            $postedBuckets = $request->post('buckets', []);
            $buckets = [];
            if (is_array($postedBuckets)) {
                foreach ($postedBuckets as $item) {
                    $key = trim($item['key'] ?? '');
                    $name = trim($item['name'] ?? '');
                    $publicUrl = trim($item['publicUrl'] ?? '');

                    if ($key !== '' && $name !== '') {
                        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
                        if ($key !== '') {
                            $buckets[$key] = [
                                'name'      => $name,
                                'publicUrl' => $publicUrl,
                            ];
                        }
                    }
                }
            }

            // Save to database
            $this->settings->set('APP_DEBUG', $appDebug);
            $this->settings->set('FOLDER_MAX_FILES', (string) $folderMaxFiles);
            $this->settings->set('ALLOWED_EXTENSIONS', $allowedExtensions);
            $this->settings->set('R2_ACCOUNT_ID', $accountId);
            $this->settings->set('R2_ACCESS_KEY_ID', $accessKeyId);

            // Save secret key only if not empty
            if ($secretAccessKey !== '') {
                $this->settings->set('R2_SECRET_ACCESS_KEY', $secretAccessKey);
            }

            $this->settings->set('R2_BUCKETS', json_encode($buckets));

            $this->logger->log('settings_update', null, null, null, 'Mengubah pengaturan sistem.');

            $_SESSION['settings_success'] = __('success_settings_saved');
        } catch (\Exception $e) {
            $_SESSION['settings_error'] = __('err_settings_failed', ['msg' => $e->getMessage()]);
        }

        return $this->redirect('/?action=settings');
    }
}

