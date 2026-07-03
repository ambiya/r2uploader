<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Helpers\FileHelper;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Http\Exception\HttpException;
use R2Uploader\Security\Csrf;
use R2Uploader\Service\AutoRetention;
use R2Uploader\Service\BucketResolver;
use R2Uploader\Service\R2Service;
use R2Uploader\Service\RemoteDownloader;
use R2Uploader\Service\ActivityLogger;
use R2Uploader\ViewData\SuccessViewData;
use R2Uploader\ViewData\UploadViewData;

/**
 * Handles file upload form display and upload processing.
 */
class UploadController extends BaseController
{
    private ?R2Service $r2;
    private Csrf $csrf;
    private BucketResolver $bucketResolver;
    private ActivityLogger $logger;
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        ?R2Service $r2,
        Csrf $csrf,
        BucketResolver $bucketResolver,
        ActivityLogger $logger,
        array $config
    ) {
        $this->r2             = $r2;
        $this->csrf           = $csrf;
        $this->bucketResolver = $bucketResolver;
        $this->logger         = $logger;
        $this->config         = $config;
    }

    /**
     * Show the upload form.
     * GET /?action=upload
     */
    public function showForm(): Response
    {
        $buckets = $this->bucketResolver->all();

        $folderRetentionNote = '';
        if ($this->config['folderMaxFiles'] > 0) {
            $folderRetentionNote = __('note_folder_retention', ['max' => $this->config['folderMaxFiles']]);
        }

        $viewData = new UploadViewData(
            $this->csrf->getToken(),
            $this->config['configError'] ?? '',
            $this->r2 !== null,
            $buckets,
            $folderRetentionNote
        );

        return $this->renderPage(__('app_title'), 'upload', $viewData, $this->csrf->getToken(), ['upload']);
    }

    /**
     * Handle file upload (remote URL or manual file).
     * POST /?action=upload
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function handleUpload(Request $request): Response
    {
        $type   = $request->post('type');
        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name'])) {
            throw HttpException::badRequest(__('err_r2_bucket_not_found'));
        }

        $mode     = $request->post('mode');
        $filename = (string) $request->post('filename', '');
        $folder   = (string) $request->post('folder', '');

        // Sanitize folder
        $folder = preg_replace('/\.\.+/', '.', $folder) ?? $folder;
        $folder = str_replace(['\\', ':', '*', '?', '"', '<', '>', '|'], '', $folder);
        $folder = trim($folder, '/ ');

        $uploadedFiles = [];

        try {
            if ($mode === 'remote') {
                $uploadedFiles = $this->handleRemoteUpload($request, $filename);
            } elseif ($mode === 'manual') {
                $uploadedFiles = $this->handleManualUpload($request, $filename);
            } else {
                throw HttpException::badRequest(__('err_invalid_upload_mode'));
            }

            // Upload to R2
            $successFiles = [];
            foreach ($uploadedFiles as $fileData) {
                $objectKey = !empty($folder) ? rtrim($folder, '/') . '/' . $fileData['fileName'] : $fileData['fileName'];
                $this->r2->putObject($bucket['name'], $objectKey, $fileData['body'], $fileData['contentType'], $fileData['fileName']);

                $publicUrl = rtrim($bucket['publicUrl'], '/') . '/' . ltrim($objectKey, '/');
                $successFiles[] = [
                    'publicUrl' => $publicUrl,
                    'fileSizeMB' => $fileData['fileSizeMB']
                ];

                $this->logger->log('upload', $bucket['name'], $objectKey, 0, "Uploaded via $mode");

                if (is_resource($fileData['body'])) {
                    fclose($fileData['body']);
                }
            }

            // Auto-retention
            $pruneDeletedKeys = [];
            $pruneKeptCount = null;

            if ($this->config['folderMaxFiles'] > 0 && !empty($folder)) {
                $retention = new AutoRetention($this->r2, $this->config['folderMaxFiles']);
                $folderPrefix = rtrim($folder, '/') . '/';
                $pruneDeletedKeys = $retention->prune($bucket['name'], $folderPrefix);
                $pruneKeptCount = $this->config['folderMaxFiles'];
            }



            $viewData = new SuccessViewData(
                $successFiles,
                is_string($type) ? $type : null,
                $pruneDeletedKeys,
                $pruneKeptCount,
                $this->config['folderMaxFiles']
            );

            // Render success
            return $this->renderPage(__('upload_success_title'), 'success', $viewData, $this->csrf->getToken());

        } catch (\Exception $e) {
            foreach ($uploadedFiles as $fileData) {
                if (isset($fileData['body']) && is_resource($fileData['body'])) {
                    fclose($fileData['body']);
                }
            }
            // Re-throw HttpExceptions as-is; wrap others
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw HttpException::internalError(__('err_upload_failed', ['msg' => $e->getMessage()]));
        }
    }


    /**
     * Handle remote URL download.
     *
     * @return array<int, array{fileName: string, body: resource|string, contentType: string, fileSizeMB: string}>
     */
    private function handleRemoteUpload(Request $request, string $customFilename): array
    {
        $fileUrl = (string) $request->post('fileUrl', '');

        $downloader = new RemoteDownloader();
        $result = $downloader->download($fileUrl);

        $fileName = !empty($customFilename)
            ? $customFilename
            : $result['originalName'];

        $fileName = FileHelper::sanitizeFilename($fileName);
        $fileName = FileHelper::ensureExtension($fileName, $result['originalName']);
        $this->validateExtension($fileName);

        return [[
            'fileName'    => $fileName,
            'body'        => $result['stream'],
            'contentType' => $result['contentType'],
            'fileSizeMB'  => $result['size'] !== null ? FileHelper::formatFileSize($result['size']) : 'Unknown',
        ]];
    }

    /**
     * Handle manual file upload from device (supports multiple files).
     *
     * @return array<int, array{fileName: string, body: resource|string, contentType: string, fileSizeMB: string}>
     */
    private function handleManualUpload(Request $request, string $customFilename): array
    {
        $files = $request->files('manualFile');
        if (!$files) {
            throw new \RuntimeException(__('err_no_file_uploaded'));
        }

        $uploadedFiles = [];

        // Normalize single file upload to array structure
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']],
            ];
        }

        $fileCount = count($files['name']);
        if ($fileCount === 0 || empty($files['name'][0])) {
             throw new \RuntimeException(__('err_no_file_uploaded'));
        }

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE   => __('err_upload_max_filesize', ['size' => ini_get('upload_max_filesize')]),
                    UPLOAD_ERR_FORM_SIZE  => __('err_upload_form_size'),
                    UPLOAD_ERR_PARTIAL    => __('err_upload_partial'),
                    UPLOAD_ERR_NO_FILE    => __('err_no_file_uploaded'),
                    UPLOAD_ERR_NO_TMP_DIR => __('err_upload_no_tmp_dir'),
                    UPLOAD_ERR_CANT_WRITE => __('err_upload_cant_write'),
                    UPLOAD_ERR_EXTENSION  => __('err_upload_extension'),
                ];
                $errCode = $files['error'][$i];
                $errMsg  = $uploadErrors[$errCode] ?? __('err_upload_unknown', ['code' => $errCode]);
                throw new \RuntimeException(__('err_upload_file_failed', ['filename' => $files['name'][$i], 'msg' => $errMsg]));
            }

            $originalName = $files['name'][$i];

            // Apply custom filename only if single file uploaded, otherwise keep original
            $fileName = ($fileCount === 1 && !empty($customFilename)) ? $customFilename : $originalName;

            $fileName = FileHelper::sanitizeFilename($fileName);
            $fileName = FileHelper::ensureExtension($fileName, $originalName);
            $this->validateExtension($fileName);

            $tmpPath = $files['tmp_name'][$i];
            if (!is_uploaded_file($tmpPath)) {
                throw new \RuntimeException(__('err_invalid_uploaded_file', ['filename' => $files['name'][$i]]));
            }

            // Server-side MIME validation
            $contentType = mime_content_type($tmpPath) ?: 'application/octet-stream';

            $uploadedFiles[] = [
                'fileName'    => $fileName,
                'body'        => $tmpPath, // String path, handled by R2Service
                'contentType' => $contentType,
                'fileSizeMB'  => FileHelper::formatFileSize((int) $files['size'][$i]),
            ];
        }

        return $uploadedFiles;
    }

    /**
     * Validate file extension against whitelist.
     *
     * @throws \RuntimeException If extension is not allowed
     */
    private function validateExtension(string $fileName): void
    {
        $allowed = $this->config['allowedExtensions'] ?? [];
        if (empty($allowed)) {
            return; // No restrictions
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException(__('err_extension_not_allowed', ['ext' => $ext, 'allowed' => implode(', ', $allowed)]));
        }
    }
}

