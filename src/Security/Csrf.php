<?php
declare(strict_types=1);

namespace R2Uploader\Security;

/**
 * Session-based CSRF token management.
 */
class Csrf
{
    /**
     * Generate or retrieve the current CSRF token from the session.
     */
    public function getToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate the CSRF token from POST data against the session.
     */
    public function validate(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerate the CSRF token.
     */
    public function regenerate(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
