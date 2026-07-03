<?php
declare(strict_types=1);

namespace R2Uploader;

use R2Uploader\Http\MiddlewareInterface;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;

/**
 * Simple action-based HTTP router with middleware support.
 */
class Router
{
    /**
     * @var array<string, array<array{action: ?string, handler: callable, middleware: MiddlewareInterface[]}>>
     */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string|null           $action     The ?action= value to match (null = default/home)
     * @param callable              $handler    The route handler: fn(Request): Response
     * @param MiddlewareInterface[] $middleware Middleware to apply to this route
     */
    public function get(?string $action, callable $handler, array $middleware = []): void
    {
        $this->routes['GET'][] = [
            'action'     => $action,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Register a POST route.
     *
     * @param string|null           $action     The ?action= value to match
     * @param callable              $handler    The route handler: fn(Request): Response
     * @param MiddlewareInterface[] $middleware Middleware to apply to this route
     */
    public function post(?string $action, callable $handler, array $middleware = []): void
    {
        $this->routes['POST'][] = [
            'action'     => $action,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Dispatch the current request to the matching route handler.
     *
     * Routes are matched by ?action= query parameter.
     * A null action matches the default (home) route.
     * Middleware runs in order before the handler.
     *
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $action = $request->action();
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if ($route['action'] === $action) {
                return $this->runMiddlewareStack(
                    $route['middleware'],
                    $request,
                    $route['handler']
                );
            }
        }

        // No route matched
        return Response::html('Halaman tidak ditemukan.', 404);
    }

    /**
     * Execute middleware stack then the final handler.
     *
     * @param MiddlewareInterface[] $middleware
     * @param Request               $request
     * @param callable              $handler  fn(Request): Response
     * @return Response
     */
    private function runMiddlewareStack(array $middleware, Request $request, callable $handler): Response
    {
        // Build the handler chain from inside out
        $next = fn(Request $req): Response => $handler($req);

        foreach (array_reverse($middleware) as $mw) {
            $currentNext = $next;
            $next = fn(Request $req): Response => $mw->handle($req, $currentNext);
        }

        return $next($request);
    }
}

