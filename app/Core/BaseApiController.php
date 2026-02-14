<?php
namespace App\Core;

use App\Core\Auth;

abstract class BaseApiController {
    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    protected function requireAdmin(): bool
    {
        if (!Auth::isAdmin()) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['Forbidden'],
            ], 403);
            return false;
        }
        return true;
    }

    protected function requireLogin(): bool
    {
        if (!Auth::isLogged()) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['Forbidden'],
            ], 403);
            return false;
        }
        return true;
    }

    protected function verifyCsrf(): bool
    {
        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['Invalid CSRF token'],
            ], 403);
            return false;
        }
        return true;
    }
}
