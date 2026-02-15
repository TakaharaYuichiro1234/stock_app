<?php
namespace App\Core;

use App\Core\Auth;
use App\Core\BaseController;

abstract class BaseApiController extends BaseController {
    protected function jsonResponse(array $data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
