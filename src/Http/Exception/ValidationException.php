<?php
declare(strict_types=1);

namespace R2Uploader\Http\Exception;

/**
 * Validation exception (HTTP 422 Unprocessable Entity).
 *
 * Throw this when user input fails validation.
 */
class ValidationException extends HttpException
{
    /** @var array<string, string[]> Field-level error messages */
    private array $errors;

    /**
     * @param string $message   Summary error message
     * @param array<string, string[]> $errors Field-level errors
     */
    public function __construct(string $message = 'Validation failed', array $errors = [])
    {
        $this->errors = $errors;
        parent::__construct(422, $message);
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
