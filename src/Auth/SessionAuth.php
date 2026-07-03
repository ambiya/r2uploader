<?php
declare(strict_types=1);

namespace R2Uploader\Auth;

/**
 * Handles session-based authentication and authorization.
 */
class SessionAuth
{
    private UserManager $userManager;
    private ?array $currentUser = null;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->userManager->findById((int) $_SESSION['user_id']);
            if (!$this->currentUser) {
                $this->logout(); // Invalid session
            }
        }
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->userManager->findByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $this->currentUser = $user;
            $this->userManager->updateLastLogin((int) $user['id']);
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->currentUser = null;
    }

    public function check(): bool
    {
        return $this->currentUser !== null;
    }

    public function user(): ?array
    {
        return $this->currentUser;
    }

    public function hasRole(string $requiredRole): bool
    {
        if (!$this->check()) {
            return false;
        }
        
        $role = $this->currentUser['role'];
        if ($role === 'admin') {
            return true;
        }
        
        if ($requiredRole === 'editor' && $role === 'editor') {
            return true;
        }
        
        if ($requiredRole === 'viewer' && ($role === 'editor' || $role === 'viewer')) {
            return true;
        }
        
        return false;
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            header('Location: /?action=login');
            exit;
        }
    }
    
    public function requireRole(string $role): void
    {
        $this->requireAuth();
        if (!$this->hasRole($role)) {
            header('HTTP/1.0 403 Forbidden');
            echo "Akses ditolak. Membutuhkan hak akses: $role";
            exit;
        }
    }
}
