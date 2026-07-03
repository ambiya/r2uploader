<?php
declare(strict_types=1);

namespace R2Uploader\Http;

/**
 * Immutable HTTP response value object.
 *
 * Controllers return Response instances instead of calling echo/header/exit.
 * The application loop calls send() exactly once.
 */
class Response
{
    private int $statusCode;
    private string $body;
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param int    $statusCode HTTP status code
     * @param string $body       Response body content
     * @param array<string, string> $headers HTTP headers
     */
    public function __construct(int $statusCode = 200, string $body = '', array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body       = $body;
        $this->headers    = $headers;
    }

    // --- Static factories ---

    /**
     * Create an HTML response.
     */
    public static function html(string $body, int $statusCode = 200): self
    {
        return new self($statusCode, $body, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Create a JSON response.
     *
     * @param array<string, mixed>|object $data
     */
    public static function json(array|object $data, int $statusCode = 200): self
    {
        return new self($statusCode, json_encode($data, JSON_UNESCAPED_UNICODE) ?: '{}', [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self($statusCode, '', [
            'Location' => $url,
        ]);
    }

    /**
     * Create an error response (format auto-detected from Request).
     *
     * @param string       $message Error message
     * @param int          $statusCode HTTP status code
     * @param Request|null $request If provided, format is chosen based on isAjax()
     */
    public static function error(string $message, int $statusCode = 500, ?Request $request = null): self
    {
        if ($request !== null && $request->isAjax()) {
            return self::json(['error' => $message], $statusCode);
        }

        return self::html(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $statusCode);
    }

    // --- Accessors ---

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    // --- Mutators (return new instance) ---

    /**
     * Return a new Response with an additional header.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Return a new Response with a different status code.
     */
    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    // --- Output ---

    /**
     * Send the response to the client.
     *
     * This should be called exactly once per request by the application bootstrap.
     */
    public function send(): void
    {
        // Status line
        http_response_code($this->statusCode);

        // Headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Body
        echo $this->body;
    }
}
