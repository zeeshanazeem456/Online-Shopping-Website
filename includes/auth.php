<?php

require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Flash.php';
require_once __DIR__ . '/classes/UserRepository.php';
require_once __DIR__ . '/classes/ProductRepository.php';
require_once __DIR__ . '/classes/ProductImageUploader.php';
require_once __DIR__ . '/classes/CartService.php';
require_once __DIR__ . '/classes/OrderRepository.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
$flash = new Flash();

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
    global $auth;

    return $auth->isLoggedIn();
}

function require_login()
{
    global $auth;

    $auth->requireLogin();
}

function require_user()
{
    global $auth;

    $auth->requireUser();
}

function require_admin()
{
    global $auth;

    $auth->requireAdmin();
}

function flash_success($message)
{
    global $flash;

    $flash->success($message);
}

function flash_error($message)
{
    global $flash;

    $flash->error($message);
}

function get_flash_message()
{
    global $flash;

    return $flash->getSuccess();
}

function get_flash_error()
{
    global $flash;

    return $flash->getError();
}
