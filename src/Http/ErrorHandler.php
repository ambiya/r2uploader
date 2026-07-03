<?php
declare(strict_types=1);

namespace R2Uploader\Http;

use R2Uploader\Http\Exception\HttpException;
use R2Uploader\Http\Exception\ValidationException;

/**
 * Centralized error handler.
 *
 * Wraps route dispatch to catch exceptions and convert them into appropriate
 * Response objects (JSON for AJAX, HTML for browser requests).
 */
class ErrorHandler
{
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = true;
    }

    /**
     * Execute a callable and catch any exceptions, returning a Response.
     *
     * @param callable $handler  The route handler: fn(): Response
     * @param Request  $request  The current request (for format detection)
     * @return Response
     */
    public function handle(callable $handler, Request $request): Response
    {
        try {
            return $handler();
        } catch (ValidationException $e) {
            return $this->handleValidationException($e, $request);
        } catch (HttpException $e) {
            return $this->handleHttpException($e, $request);
        } catch (\Throwable $e) {
            return $this->handleGenericException($e, $request);
        }
    }

    private function handleValidationException(ValidationException $e, Request $request): Response
    {
        $statusCode = $e->getStatusCode();

        if ($request->isAjax()) {
            $data = ['error' => $e->getMessage()];
            if (!empty($e->getErrors())) {
                $data['errors'] = $e->getErrors();
            }
            return Response::json($data, $statusCode);
        }

        return Response::html(
            htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            $statusCode
        );
    }

    private function handleHttpException(HttpException $e, Request $request): Response
    {
        $statusCode = $e->getStatusCode();
        $message    = $e->getMessage();

        if ($request->isAjax()) {
            return Response::json(['error' => $message], $statusCode);
        }

        $response = Response::html(
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            $statusCode
        );

        // Apply any custom headers from the exception
        foreach ($e->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function handleGenericException(\Throwable $e, Request $request): Response
    {
        $message = $this->debug
            ? $e->getMessage() . "\n\n" . $e->getTraceAsString()
            : 'Terjadi kesalahan internal.';

        if ($request->isAjax()) {
            $data = ['error' => $this->debug ? $e->getMessage() : 'Terjadi kesalahan internal.'];
            if ($this->debug) {
                $data['trace'] = $e->getTraceAsString();
            }
            return Response::json($data, 500);
        }

        return Response::html(
            '<pre>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</pre>',
            500
        );
    }
}
