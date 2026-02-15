<?php
namespace App\Middlewares;

use App\Core\MiddlewareInterface;
use App\Core\Auth;

class UserMiddleware implements MiddlewareInterface {
    public function handle(): void
    {
        if (!Auth::isLogged()) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
