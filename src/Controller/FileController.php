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

    public function __construct(
        ?R2Service $r2,
        Csrf $csrf,
        BucketResolver $bucketResolver,
        ActivityLogger $logger
    ) {
        $this->r2             = $r2;
        $this->csrf           = $csrf;
        $this->bucketResolver = $bucketResolver;
        $this->logger         = $logger;
    }

    /**
     * List files in a bucket with prefix support.
     * GET /?action=list&type=...&prefix=...
     */
    public function list(Request $request): Response
    {
        $type   = $request->query('type');
        $prefix = (string) $request->query('prefix', '');
        $ct     = $request->query('ct');
        $search = (string) $request->query('q', '');

        $buckets = $this->bucketResolver->all();
        if (empty($type) && !empty($buckets)) {
            $type = $this->bucketResolver->firstType();
        }

        $bucket = $this->bucketResolver->resolve($type);

        if (!$this->r2 || empty($buckets)) {
            throw HttpException::badRequest(__('err_r2_not_configured'));
        }

        if (!$bucket || empty($bucket['name'])) {
            throw HttpException::notFound(__('err_bucket_not_found'));
        }

        // Handle path traversal attempts
        if (str_contains($prefix, '..')) {
            throw HttpException::badRequest(__('err_path_traversal'));
        }
        $prefix = ltrim($prefix, '/');

        if ($search !== '') {
            $allObjects = $this->r2->listAllObjects($bucket['name'], $prefix);
            $filteredObjects = array_values(array_filter($allObjects, function($obj) use ($search) {
                return stripos($obj['Key'], $search) !== false;
            }));
            $result = [
                'objects' => $filteredObjects,
                'prefixes' => [],
                'isTruncated' => false,
                'nextToken' => null,
            ];
        } else {
            $result = $this->r2->listObjects($bucket['name'], $prefix, 1000, $ct);
        }

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
        $this->logger->log('delete', $bucket['name'], $key, null, 'Deleted file');

        // Regenerate CSRF to prevent replay
        $this->csrf->regenerate();

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
}

