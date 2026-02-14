<?php
namespace App\Core;

use App\Core\Auth;
// require_once __DIR__ . '/Auth.php';

abstract class BaseWebController
{
    protected function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            // http_response_code(403);
            // exit('Forbidden');

            throw new \Exception('Forbidden', 403);
        }
    }

    protected function requireLogin(): void
    {
        if (!Auth::isLogged()) {
            // http_response_code(403);
            // exit('Forbidden');

            throw new \Exception('Forbidden', 403);
        }
    }

    protected function verifyCsrf(): void
    {        
        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            // http_response_code(403);
            // exit('Invalid CSRF token');

            throw new \Exception('Invalid CSRF token', 403);
        }
    }
}
