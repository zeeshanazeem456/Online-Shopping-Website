<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to($path)
{
    header('Location: ' . $path);
    exit;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        redirect_to('login.php');
    }
}

function require_user()
{
    require_login();

    if ($_SESSION['role'] !== 'user') {
        redirect_to('admin-panel.php');
    }
}

function require_admin()
{
    require_login();

    if ($_SESSION['role'] !== 'admin') {
        redirect_to('shop.php');
    }
}

function flash_success($message)
{
    $_SESSION['message'] = $message;
}

function flash_error($message)
{
    $_SESSION['error'] = $message;
}

function get_flash_message()
{
    $message = $_SESSION['message'] ?? '';
    unset($_SESSION['message']);

    return $message;
}

function get_flash_error()
{
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['error']);

    return $error;
}
