<?php
namespace App\Controllers\Api;

use PDO;
use App\Core\Auth;
use App\Services\StockService;

require_once __DIR__ . '/../../Core/Auth.php';
require_once __DIR__ . '/../../Services/StockService.php';

class AdminApiController
{
    private PDO $pdo;
    private StockService $stockService;


    public function __construct()
    {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->stockService = new StockService($pdo);
    }

    public function show(): void
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Forbidden'],
            ]);
            exit;
        }

        $input = $_GET['keywords'] ?? '';
        if (!is_string($input) || mb_strlen($input) > 20) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => ['不正な入力です'],
            ]);
            exit;
        }

        $symbol = trim($input);
        $errors = [];
        $isRegistered = false;
        $data = null;

        [$error, $data] = $this->stockService->initCreate($symbol);
        if ($error) $errors[] = $error;

        if ($symbol !== '' && $this->stockService->isSymbolRegistered($symbol)) {
            $isRegistered = true;
        }

        header('Content-Type: application/json; charset=utf-8');

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'data' => null,
                'errors' => $errors,
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $data,
            'isRegistered' => $isRegistered,
            'errors' => [],
        ]);
        exit;
    }
}
