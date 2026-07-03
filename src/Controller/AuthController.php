<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Auth\SessionAuth;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Security\Csrf;
use R2Uploader\ViewData\LoginViewData;

class AuthController extends BaseController
{
    private SessionAuth $auth;
    private Csrf $csrf;

    public function __construct(SessionAuth $auth, Csrf $csrf)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
    }

    public function showLogin(): Response
    {
        if ($this->auth->check()) {
            return $this->redirect('/');
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $viewData = new LoginViewData(
            $this->csrf->getToken(),
            is_string($error) ? $error : null
        );

        return $this->renderPage(__('login_title'), 'login', $viewData, $this->csrf->getToken());
    }


    public function handleLogin(Request $request): Response
    {
        // CSRF validated inline here because login is a public route (no CsrfMiddleware)
        if (!$this->csrf->validate()) {
            $_SESSION['login_error'] = __('session_expired');
            return $this->redirect('/?action=login');
        }

        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');

        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_login_attempt'] = time();
        }

        if (time() - $_SESSION['last_login_attempt'] >= 900) {
            $_SESSION['login_attempts'] = 0;
        }

        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_error'] = __('too_many_attempts');
            return $this->redirect('/?action=login');
        }

        if ($this->auth->attempt($username, $password)) {
            $_SESSION['login_attempts'] = 0;
            return $this->redirect('/');
        }

        $_SESSION['login_attempts']++;
        $_SESSION['last_login_attempt'] = time();

        $_SESSION['login_error'] = __('login_failed');
        return $this->redirect('/?action=login');
    }

    public function logout(): Response
    {
        $this->auth->logout();
        return $this->redirect('/?action=login');
    }
}

