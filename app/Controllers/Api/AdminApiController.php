<?php
namespace App\Controllers\Api;

use PDO;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Services\StockService;

class AdminApiController extends BaseApiController {
    private PDO $pdo;
    private StockService $stockService;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->stockService = new StockService($pdo);
    }

    public function show(): void {
        $input = $_GET['keywords'] ?? '';
        if (!is_string($input) || mb_strlen($input) > 20) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['不正な入力です'],
            ], 400);
            return;
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

        if (!empty($errors)) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $errors,
            ], 400);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $data,
            'isRegistered' => $isRegistered,
            'errors' => [],
        ]);
    }
}
