<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Http\Exception\HttpException;
use R2Uploader\Security\Csrf;
use R2Uploader\Service\BucketResolver;
use R2Uploader\Service\R2Service;
use R2Uploader\Service\ActivityLogger;
use R2Uploader\Service\R2FileIndexService;
use R2Uploader\ViewData\ListViewData;

/**
 * Handles file listing, deletion, and renaming.
 */
class FileController extends BaseController
{
    private ?R2Service $r2;
    private Csrf $csrf;
    private BucketResolver $bucketResolver;
    private ActivityLogger $logger;
    private R2FileIndexService $fileIndex;

    public function __construct(
        ?R2Service $r2,
        Csrf $csrf,
        BucketResolver $bucketResolver,
        ActivityLogger $logger,
        R2FileIndexService $fileIndex
    ) {
        $this->r2             = $r2;
        $this->csrf           = $csrf;
        $this->bucketResolver = $bucketResolver;
        $this->logger         = $logger;
        $this->fileIndex      = $fileIndex;
    }

    private function getFilteredAndSortedObjects(string $bucketName, string $prefix, string $search, bool $flat, string $sort, string $order, int $page, int $limit): array
    {
        // Auto-sync if database index is empty for this bucket
        if ($this->fileIndex->isEmpty($bucketName)) {
            $this->fileIndex->syncBucket($bucketName, $this->r2);
        }

        if ($search !== '' || $flat) {
            $allObjects = $this->fileIndex->listAll($bucketName, $prefix);
            if ($search !== '') {
                $allObjects = array_values(array_filter($allObjects, function($obj) use ($search) {
                    return stripos($obj['Key'], $search) !== false;
                }));
            }
            
            $this->sortObjects($allObjects, $sort, $order);
            
            $offset = ($page - 1) * $limit;
            $sliced = array_slice($allObjects, $offset, $limit);
            
            return [
                'objects' => $sliced,
                'prefixes' => [],
                'isTruncated' => ($offset + $limit) < count($allObjects),
                'nextToken' => null,
            ];
        } else {
            $allLevel = $this->fileIndex->listDirectory($bucketName, $prefix);
            $this->sortObjects($allLevel['objects'], $sort, $order);
            
            $offset = ($page - 1) * $limit;
            $slicedObjects = array_slice($allLevel['objects'], $offset, $limit);
            $slicedPrefixes = array_slice($allLevel['prefixes'], $offset, $limit);
            
            return [
                'objects' => $slicedObjects,
                'prefixes' => $slicedPrefixes,
                'isTruncated' => (($offset + $limit) < count($allLevel['objects'])) || (($offset + $limit) < count($allLevel['prefixes'])),
                'nextToken' => null,
            ];
        }
    }

    private function sortObjects(array &$objects, string $sort, string $order): void
    {
        usort($objects, function($a, $b) use ($sort, $order) {
            if ($sort === 'date') {
                $valA = isset($a['LastModified']) ? $a['LastModified']->getTimestamp() : 0;
                $valB = isset($b['LastModified']) ? $b['LastModified']->getTimestamp() : 0;
            } elseif ($sort === 'size') {
                $valA = $a['Size'] ?? 0;
                $valB = $b['Size'] ?? 0;
            } else {
                $valA = strtolower($a['Key']);
                $valB = strtolower($b['Key']);
                if ($order === 'asc') return strcmp($valA, $valB);
                if ($order === 'desc') return strcmp($valB, $valA);
            }
            
            if ($valA == $valB) return 0;
            if ($order === 'asc') return $valA > $valB ? 1 : -1;
            return $valA < $valB ? 1 : -1;
        });
    }

    private function resolveBucketAndPrefix(Request $request): array|string
    {
        $type   = $request->query('type');
        $prefix = (string) $request->query('prefix', '');

        $buckets = $this->bucketResolver->all();
        if (empty($type) && !empty($buckets)) {
            $type = $this->bucketResolver->firstType();
        }

        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || empty($buckets)) {
            return 'err_r2_not_configured';
        }

        if (!$bucket || empty($bucket['name'])) {
            return 'err_bucket_not_found';
        }

        if (str_contains($prefix, '..')) {
            return 'err_path_traversal';
        }

        return [
            'type'    => $type,
            'prefix'  => ltrim($prefix, '/'),
            'bucket'  => $bucket,
            'buckets' => $buckets
        ];
    }

    /**
     * List files in a bucket with prefix support.
     * GET /?action=list&type=...&prefix=...
     */
    public function list(Request $request): Response
    {
        $resultData = $this->resolveBucketAndPrefix($request);
        if (is_string($resultData)) {
            if ($resultData === 'err_bucket_not_found') {
                throw HttpException::notFound(__($resultData));
            }
            throw HttpException::badRequest(__($resultData));
        }

        $type    = $resultData['type'];
        $prefix  = $resultData['prefix'];
        $bucket  = $resultData['bucket'];
        $buckets = $resultData['buckets'];

        $ct     = $request->query('ct');
        $search = (string) $request->query('q', '');
        $flat   = $request->query('flat') === '1';
        $sort   = (string) $request->query('sort', 'name');
        $order  = (string) $request->query('order', 'asc');

        $result = $this->getFilteredAndSortedObjects($bucket['name'], $prefix, $search, $flat, $sort, $order, 1, 25);

        $viewData = new ListViewData(
            $this->csrf->getToken(),
            is_string($type) ? $type : null,
            $buckets,
            $prefix,
            $result['objects'],
            $result['prefixes'],
            $result['isTruncated'],
            $result['nextToken'],
            $bucket['publicUrl']
        );

        return $this->renderPage(__('nav_file_manager'), 'partials/list-content', $viewData, $this->csrf->getToken(), ['file-list']);
    }

    /**
     * API endpoint to list files via AJAX.
     * GET /?action=api_list&type=...&prefix=...&ct=...&limit=...&q=...&page=...
     */
    public function apiList(Request $request): Response
    {
        $resultData = $this->resolveBucketAndPrefix($request);
        if (is_string($resultData)) {
            $status = ($resultData === 'err_bucket_not_found') ? 404 : 400;
            return Response::json(['error' => __($resultData)], $status);
        }

        $type    = $resultData['type'];
        $prefix  = $resultData['prefix'];
        $bucket  = $resultData['bucket'];

        $ct     = $request->query('ct');
        $search = (string) $request->query('q', '');
        $flat   = $request->query('flat') === '1';
        $sort   = (string) $request->query('sort', 'name');
        $order  = (string) $request->query('order', 'asc');
        $limit  = (int) $request->query('limit', 25);
        if ($limit < 1 || $limit > 1000) $limit = 25;
        $page   = (int) $request->query('page', 1);
        if ($page < 1) $page = 1;

        $result = $this->getFilteredAndSortedObjects($bucket['name'], $prefix, $search, $flat, $sort, $order, $page, $limit);

        // Format size and append publicUrl
        $formattedObjects = [];
        foreach ($result['objects'] as $obj) {
            $formattedObjects[] = [
                'Key' => $obj['Key'],
                'Size' => $obj['Size'],
                'SizeMB' => number_format(($obj['Size'] ?? 0) / 1024 / 1024, 2),
                'LastModified' => isset($obj['LastModified']) ? $obj['LastModified']->format('c') : null,
            ];
        }

        return Response::json([
            'objects' => $formattedObjects,
            'prefixes' => $result['prefixes'],
            'isTruncated' => $result['isTruncated'],
            'nextToken' => $result['nextToken'],
            'publicUrl' => $bucket['publicUrl'],
            'csrfToken' => $this->csrf->getToken(),
            'type' => $type
        ]);
    }

    /**
     * Handle file deletion.
     * POST /?action=delete
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function delete(Request $request): Response
    {
        $type = $request->query('type');
        $key  = (string) $request->query('key', '');

        if (str_contains($key, '..')) {
            throw HttpException::badRequest(__('err_path_traversal'));
        }

        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name']) || empty($key)) {
            throw HttpException::badRequest(__('err_invalid_request'));
        }

        $this->r2->deleteObject($bucket['name'], $key);
        $this->fileIndex->deleteObject($bucket['name'], $key);
        $this->logger->log('delete', $bucket['name'], $key, null, 'Deleted file');

        // Regenerate CSRF to prevent replay
        $this->csrf->regenerate();

        if ($request->isAjax()) {
            return Response::json([
                'success' => true,
                'csrfToken' => $this->csrf->getToken(),
                'message' => 'File deleted successfully'
            ]);
        }

        $prefix = dirname($key);
        if ($prefix === '.') {
            $prefix = '';
        }
        $redirectUrl = '/?action=list&type=' . urlencode((string) $type);
        if ($prefix !== '') {
            $redirectUrl .= '&prefix=' . urlencode($prefix . '/');
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * Handle file rename (copy + delete).
     * POST /?action=rename
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function rename(Request $request): Response
    {
        $type   = $request->query('type');
        $oldKey = $request->query('key');
        $newKey = $request->post('newKey');
        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name']) || empty($oldKey) || empty($newKey)) {
            throw HttpException::badRequest(__('err_invalid_request'));
        }

        // Basic sanitize
        if (str_contains((string) $newKey, '..') || str_contains((string) $oldKey, '..')) {
            throw HttpException::badRequest(__('err_path_traversal'));
        }
        $newKey = ltrim(trim((string) $newKey), '/');

        if ($newKey !== $oldKey) {
            $this->r2->copyObject($bucket['name'], (string) $oldKey, $newKey);
            $this->r2->deleteObject($bucket['name'], (string) $oldKey);
            $this->fileIndex->renameObject($bucket['name'], (string) $oldKey, $newKey);
            $this->logger->log('rename', $bucket['name'], $newKey, null, "Renamed from $oldKey");
        }

        $this->csrf->regenerate();

        $prefix = dirname((string) $oldKey);
        if ($prefix === '.') {
            $prefix = '';
        }
        $redirectUrl = '/?action=list&type=' . urlencode((string) $type);
        if ($prefix !== '') {
            $redirectUrl .= '&prefix=' . urlencode($prefix . '/');
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * Handle bulk deletion.
     * POST /?action=bulk_delete
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function bulkDelete(Request $request): Response
    {
        $type = $request->query('type');
        $keys = $request->post('keys');
        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name']) || empty($keys) || !is_array($keys)) {
            throw HttpException::badRequest(__('err_invalid_request'));
        }

        foreach ($keys as $key) {
            if (str_contains($key, '..')) {
                throw HttpException::badRequest(__('err_path_traversal'));
            }
        }

        $this->r2->deleteObjects($bucket['name'], $keys);
        $this->fileIndex->deleteObjects($bucket['name'], $keys);

        foreach ($keys as $key) {
            $this->logger->log('delete', $bucket['name'], $key, null, 'Bulk deleted file');
        }

        $this->csrf->regenerate();

        if ($request->isAjax()) {
            return Response::json([
                'success' => true,
                'csrfToken' => $this->csrf->getToken(),
                'message' => 'Files deleted successfully'
            ]);
        }

        $prefix = '';
        if (!empty($keys[0])) {
            $prefix = dirname($keys[0]);
            if ($prefix === '.') {
                $prefix = '';
            }
        }

        $redirectUrl = '/?action=list&type=' . urlencode((string) $type);
        if ($prefix !== '') {
            $redirectUrl .= '&prefix=' . urlencode($prefix . '/');
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * Handle bulk download (Zip on the fly).
     * POST /?action=bulk_download
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function bulkDownload(Request $request): Response
    {
        $type = $request->query('type');
        $keys = $request->post('keys');
        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name']) || empty($keys) || !is_array($keys)) {
            throw HttpException::badRequest(__('err_invalid_request'));
        }

        foreach ($keys as $key) {
            if (str_contains($key, '..')) {
                throw HttpException::badRequest(__('err_path_traversal'));
            }
        }

        $tempDir = dirname(__DIR__, 2) . '/data';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipFile = tempnam($tempDir, 'r2_bulk_');
        if ($zipFile === false) {
            throw new \RuntimeException('Failed to create temporary zip file.');
        }
        unlink($zipFile);
        $zipFile .= '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to open ZIP file.');
        }

        foreach ($keys as $key) {
            try {
                $object = $this->r2->getObject($bucket['name'], $key);
                $body = $object['Body'];
                $content = (string)$body;
                
                $zip->addFromString(basename($key), $content);
            } catch (\Exception $e) {
                $zip->addFromString(basename($key) . '_error.txt', 'Failed to download this file from R2: ' . $e->getMessage());
            }
        }

        $zip->close();

        $zipContent = file_get_contents($zipFile);
        unlink($zipFile);

        return new Response(200, $zipContent, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="r2_download_' . date('Ymd_His') . '.zip"',
            'Content-Length' => (string)strlen($zipContent),
        ]);
    }

    /**
     * Handle folder creation.
     * POST /?action=create_folder
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function createFolder(Request $request): Response
    {
        $type   = $request->query('type');
        $prefix = (string) $request->query('prefix', '');
        $folderName = (string) $request->post('folderName', '');

        $buckets = $this->bucketResolver->all();
        if (empty($type) && !empty($buckets)) {
            $type = $this->bucketResolver->firstType();
        }

        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name'])) {
            throw HttpException::badRequest(__('err_invalid_request'));
        }

        // Sanitize folder name
        $folderName = preg_replace('/\.\.+/', '.', $folderName) ?? $folderName;
        $folderName = str_replace(['\\', ':', '*', '?', '"', '<', '>', '|'], '', $folderName);
        $folderName = trim($folderName, '/ ');

        if (empty($folderName)) {
            throw HttpException::badRequest(__('err_invalid_request'));
        }

        // Path of new folder: prefix + folderName
        $newPrefix = !empty($prefix) ? rtrim($prefix, '/') . '/' . $folderName : $folderName;
        $newPrefix = rtrim($newPrefix, '/') . '/';

        // Check path traversal
        if (str_contains($newPrefix, '..')) {
            throw HttpException::badRequest(__('err_path_traversal'));
        }

        $this->r2->createFolder($bucket['name'], $newPrefix);
        $this->fileIndex->addObject($bucket['name'], $newPrefix, 0, date('Y-m-d H:i:s'), true);
        $this->logger->log('create_folder', $bucket['name'], $newPrefix, null, 'Created empty folder');

        $this->csrf->regenerate();

        $redirectUrl = '/?action=list&type=' . urlencode((string) $type);
        if (!empty($prefix)) {
            $redirectUrl .= '&prefix=' . urlencode(rtrim($prefix, '/') . '/');
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * Trigger manual synchronization of the index database.
     * POST /?action=sync_index
     *
     * CSRF validated by CsrfMiddleware.
     */
    public function syncIndex(Request $request): Response
    {
        $type = $request->query('type');
        $buckets = $this->bucketResolver->all();
        if (empty($type) && !empty($buckets)) {
            $type = $this->bucketResolver->firstType();
        }

        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || !$bucket || empty($bucket['name'])) {
            return Response::json(['error' => __('err_invalid_request')], 400);
        }

        try {
            $this->fileIndex->syncBucket($bucket['name'], $this->r2);
            return Response::json(['success' => true]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}

