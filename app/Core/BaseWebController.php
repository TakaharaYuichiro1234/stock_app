<?php
namespace App\Core;

use App\Core\Auth;

abstract class BaseWebController {
    protected function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            throw new \Exception('Forbidden', 403);
        }
    }

    protected function requireLogin(): void
    {
        if (!Auth::isLogged()) {
            throw new \Exception('Forbidden', 403);
        }
    }

    protected function verifyCsrf(): void
    {        
        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            throw new \Exception('Invalid CSRF token', 403);
        }
    }
}
