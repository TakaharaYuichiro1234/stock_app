<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;
use App\Core\Auth;

class AdminMiddleware implements MiddlewareInterface {
    public function handle(): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
