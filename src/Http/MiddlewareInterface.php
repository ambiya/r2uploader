<?php
declare(strict_types=1);

namespace R2Uploader\Http;

/**
 * Middleware contract.
 *
 * Middleware receives the current Request and a callable $next that
 * represents the remainder of the middleware stack (or the final handler).
 * It must return a Response.
 */
interface MiddlewareInterface
{
    /**
     * Process the request through this middleware.
     *
     * @param Request  $request The incoming request
     * @param callable $next    The next handler: fn(Request): Response
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
