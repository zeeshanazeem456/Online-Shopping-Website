<?php

class Auth
{
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            redirect_to('login.php');
        }
    }

    public function requireUser(): void
    {
        $this->requireLogin();

        if ($_SESSION['role'] !== 'user') {
            redirect_to('admin-panel.php');
        }
    }

    public function requireAdmin(): void
    {
        $this->requireLogin();

        if ($_SESSION['role'] !== 'admin') {
            redirect_to('shop.php');
        }
    }
}
