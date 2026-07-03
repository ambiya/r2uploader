<?php
declare(strict_types=1);

namespace R2Uploader\Http\Middleware;

use R2Uploader\Auth\SessionAuth;
use R2Uploader\Http\MiddlewareInterface;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;

/**
 * Middleware that enforces authentication and optional role requirements.
 *
 * If the user is not authenticated, redirects to the login page.
 * If a role is required and the user lacks it, returns 403.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private SessionAuth $auth;
    private ?string $requiredRole;

    /**
     * @param SessionAuth $auth         The session auth service
     * @param string|null $requiredRole Optional role to enforce (e.g. 'admin')
     */
    public function __construct(SessionAuth $auth, ?string $requiredRole = null)
    {
        $this->auth         = $auth;
        $this->requiredRole = $requiredRole;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            return Response::redirect('/?action=login');
        }

        if ($this->requiredRole !== null && !$this->auth->hasRole($this->requiredRole)) {
            return Response::error(
                "Akses ditolak. Membutuhkan hak akses: {$this->requiredRole}",
                403,
                $request
            );
        }

        return $next($request);
    }
}
