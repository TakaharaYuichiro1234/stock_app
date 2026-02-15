<?php
namespace App\Core;
abstract class BaseController
{
    protected function abort(int $status, string $message = '')
    {
        http_response_code($status);
        exit($message);
    }
}
