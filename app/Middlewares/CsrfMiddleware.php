<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;

class CsrfMiddleware implements MiddlewareInterface {
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            exit('Invalid CSRF token');
        }
    }
}
