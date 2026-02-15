<?php
namespace App\Core;

use App\Core\Auth;
use App\Core\BaseController;

abstract class BaseWebController extends BaseController {
    protected function view(string $path, array $data = [])
    {
        extract($data);
        require dirname(__DIR__) . '/Views/' . $path . '.php';
    }
}
