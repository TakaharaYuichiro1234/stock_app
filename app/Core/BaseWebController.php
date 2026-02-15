<?php
namespace App\Core;

use App\Core\Auth;
use App\Core\BaseController;

abstract class BaseWebController extends BaseController {
    protected function view(string $path, array $data = [])
    {
        extract($data);
        // require BASE_PATH . '/app/Views/' . $path . '.php';
        require dirname(__DIR__) . '/Views/' . $path . '.php';
    }

    // protected function requireAdmin(): void
    // {
    //     if (!Auth::isAdmin()) {
    //         throw new \Exception('Forbidden', 403);
    //     }
    // }

    // protected function requireLogin(): void
    // {
    //     if (!Auth::isLogged()) {
    //         throw new \Exception('Forbidden', 403);
    //     }
    // }

    // protected function verifyCsrf(): void
    // {        
    //     if (
    //         !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    //         !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    //     ) {
    //         throw new \Exception('Invalid CSRF token', 403);
    //     }
    // }
}
