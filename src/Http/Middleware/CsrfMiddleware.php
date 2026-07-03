<?php
declare(strict_types=1);

namespace R2Uploader\Http\Middleware;

use R2Uploader\Http\MiddlewareInterface;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Security\Csrf;

/**
 * Middleware that validates CSRF tokens on POST requests.
 *
 * GET requests pass through without validation.
 * POST requests with invalid tokens receive a 403 response.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private Csrf $csrf;

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($request->isPost() && !$this->csrf->validate()) {
            return Response::error('Invalid CSRF token.', 403, $request);
        }

        return $next($request);
    }
}
