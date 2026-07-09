<?php

class Flash
{
    public function success(string $message): void
    {
        $_SESSION['message'] = $message;
    }

    public function error(string $message): void
    {
        $_SESSION['error'] = $message;
    }

    public function getSuccess(): string
    {
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        return $message;
    }

    public function getError(): string
    {
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        return $error;
    }
}
