<?php

namespace App\Controllers;

use PDO;
use App\Core\BaseWebController;
use App\Services\StockPriceService;
use App\Core\Auth;

class AdminController extends BaseWebController
{
    private PDO $pdo;
    private StockPriceService $stockPriceService;

    public function __construct()
    {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockPriceService = new StockPriceService($pdo);
    }

    public function index()
    {
        try {
            $isAdmin = Auth::isAdmin();
            $user = $_SESSION['user'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $this->view('admin', [
                'isAdmin' => $isAdmin,
                'user'    => $user,
            ]);
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            exit($e->getMessage());
        }
    }

    public function updateStockPricesAll()
    {
        try {
            // 銘柄一覧取得
            $stmt = $this->pdo->query("SELECT id, symbol FROM stocks");
            $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $successCount = 0;
            $errorCount = 0;

            foreach ($stocks as $stock) {
                $ok = $this->stockPriceService->updateLatestPrices($stock['id'], $stock['symbol']);
                $ok ? $successCount++ : $errorCount++;
            }

            // フラッシュメッセージ
            $_SESSION['flash'] = "株価更新完了：成功 {$successCount} 件 / 失敗 {$errorCount} 件";

            // 一覧画面へ戻る
            header('Location: ' . BASE_PATH . '/admins');
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            exit($e->getMessage());
        }
    }
}
