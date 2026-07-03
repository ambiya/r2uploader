<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Auth\SessionAuth;
use R2Uploader\Auth\UserManager;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Security\Csrf;
use R2Uploader\ViewData\UsersViewData;

class UserController extends BaseController
{
    private SessionAuth $auth;
    private UserManager $userManager;
    private Csrf $csrf;

    public function __construct(SessionAuth $auth, UserManager $userManager, Csrf $csrf)
    {
        $this->auth        = $auth;
        $this->userManager = $userManager;
        $this->csrf        = $csrf;
    }

    public function listUsers(): Response
    {
        $users = $this->userManager->listAll();

        $viewData = new UsersViewData(
            $users,
            $this->csrf->getToken(),
            is_string($_SESSION['user_error'] ?? null) ? $_SESSION['user_error'] : null,
            is_string($_SESSION['user_success'] ?? null) ? $_SESSION['user_success'] : null
        );

        $response = $this->renderPage(__('nav_users'), 'users', $viewData, $this->csrf->getToken());

        unset($_SESSION['user_error'], $_SESSION['user_success']);
        return $response;
    }


    /**
     * CSRF validated by CsrfMiddleware.
     */
    public function createUser(Request $request): Response
    {
        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');
        $role     = (string) $request->post('role', 'editor');
        if (!in_array($role, ['admin', 'editor'], true)) {
            $role = 'editor';
        }

        if (empty($username) || empty($password)) {
            $_SESSION['user_error'] = __('err_username_pwd_required');
        } elseif ($this->userManager->findByUsername($username)) {
            $_SESSION['user_error'] = __('err_username_exists');
        } else {
            $this->userManager->createUser($username, $password, $role);
            $_SESSION['user_success'] = __('success_user_added', ['username' => $username]);
        }

        return $this->redirect('/?action=users');
    }

    /**
     * CSRF validated by CsrfMiddleware.
     */
    public function updateUser(Request $request): Response
    {
        $id   = (int) ($request->post('user_id', 0));
        $role = (string) $request->post('role', 'editor');
        if (!in_array($role, ['admin', 'editor'], true)) {
            $role = 'editor';
        }
        $password = !empty($request->post('password')) ? (string) $request->post('password') : null;

        try {
            $this->userManager->updateUser($id, $role, $password);
            $_SESSION['user_success'] = __('success_user_updated');
        } catch (\Exception $e) {
            $_SESSION['user_error'] = $e->getMessage();
        }

        return $this->redirect('/?action=users');
    }

    /**
     * CSRF validated by CsrfMiddleware.
     */
    public function deleteUser(Request $request): Response
    {
        $id = (int) ($request->post('user_id', 0));

        if ($id === (int) $this->auth->user()['id']) {
            $_SESSION['user_error'] = __('err_cannot_delete_self');
        } else {
            try {
                $this->userManager->deleteUser($id);
                $_SESSION['user_success'] = __('success_user_deleted');
            } catch (\Exception $e) {
                $_SESSION['user_error'] = $e->getMessage();
            }
        }

        return $this->redirect('/?action=users');
    }
}

