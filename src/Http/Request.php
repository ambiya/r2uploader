<?php
declare(strict_types=1);

namespace R2Uploader\Http;

/**
 * Wraps PHP superglobals into a clean, testable request object.
 *
 * Created once at bootstrap and passed through middleware/controllers.
 */
class Request
{
    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $post;

    /** @var array<string, mixed> */
    private array $files;

    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, mixed> */
    private array $session;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<string, mixed> $query   Usually $_GET
     * @param array<string, mixed> $post    Usually $_POST
     * @param array<string, mixed> $files   Usually $_FILES
     * @param array<string, mixed> $server  Usually $_SERVER
     * @param array<string, mixed> $session Usually $_SESSION (passed by reference externally)
     */
    public function __construct(
        array $query = [],
        array $post = [],
        array $files = [],
        array $server = [],
        array $session = []
    ) {
        $this->query   = $query;
        $this->post    = $post;
        $this->files   = $files;
        $this->server  = $server;
        $this->session = $session;
    }

    /**
     * Create a Request from the current PHP superglobals.
     */
    public static function createFromGlobals(): self
    {
        return new self(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $_SESSION ?? []
        );
    }

    // --- Query (GET) parameters ---

    /**
     * Get a value from the query string ($_GET).
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     *
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    // --- POST parameters ---

    /**
     * Get a value from POST data.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all POST parameters.
     *
     * @return array<string, mixed>
     */
    public function allPost(): array
    {
        return $this->post;
    }

    // --- Files ---

    /**
     * Get uploaded file(s) data by input name.
     */
    public function files(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get all uploaded files.
     *
     * @return array<string, mixed>
     */
    public function allFiles(): array
    {
        return $this->files;
    }

    // --- Server ---

    /**
     * Get a $_SERVER variable.
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    // --- Convenience methods ---

    /**
     * Get the HTTP method (GET, POST, etc.)
     */
    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    /**
     * Check if the request is a POST request.
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if the client accepts JSON (AJAX request).
     */
    public function isAjax(): bool
    {
        $accept = (string) ($this->server['HTTP_ACCEPT'] ?? '');
        return str_contains($accept, 'application/json');
    }

    /**
     * Get the current ?action= parameter.
     */
    public function action(): ?string
    {
        $action = $this->query('action');
        return $action !== null ? (string) $action : null;
    }

    // --- Attributes (for middleware to pass data downstream) ---

    /**
     * Set a request attribute (e.g. resolved user, parsed body).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a request attribute.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
