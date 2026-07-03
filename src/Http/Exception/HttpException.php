<?php
declare(strict_types=1);

namespace R2Uploader\Http\Exception;

/**
 * HTTP exception with a status code.
 *
 * Throw this from controllers/services to trigger an appropriate HTTP error response.
 * The ErrorHandler catches these and formats them into Response objects.
 */
class HttpException extends \RuntimeException
{
    private int $statusCode;
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param int        $statusCode HTTP status code
     * @param string     $message    Error message
     * @param array<string, string> $headers Additional HTTP headers
     * @param \Throwable|null $previous
     */
    public function __construct(
        int $statusCode = 500,
        string $message = '',
        array $headers = [],
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->headers    = $headers;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    // --- Named constructors for common HTTP errors ---

    public static function badRequest(string $message = 'Bad Request'): self
    {
        return new self(400, $message);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return new self(404, $message);
    }

    public static function internalError(string $message = 'Internal Server Error'): self
    {
        return new self(500, $message);
    }
}
