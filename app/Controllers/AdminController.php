<?php

namespace App\Controllers;

use PDO;
use App\Core\BaseWebController;
use App\Models\Stock;
use App\Services\StockPriceService;
use App\Services\StockService;
use App\Core\Auth;

class AdminController extends BaseWebController
{
    private PDO $pdo;
    private Stock $stockModel;
    private StockPriceService $stockPriceService;
    private StockService $stockService;

    public function __construct()
    {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->stockService = new StockService($pdo);
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

    // public function updateStockPricesAll()
    // {
    //     try {
    //         // 銘柄一覧取得
    //         $stmt = $this->pdo->query("SELECT id, symbol FROM stocks");
    //         $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         $successCount = 0;
    //         $errorCount = 0;

    //         foreach ($stocks as $stock) {
    //             $ok = $this->stockPriceService->updateLatestPrices($stock['id'], $stock['symbol']);
    //             $ok ? $successCount++ : $errorCount++;
    //             if ($ok) {
    //                 $successCount++;
    //                 $data = ['tentative' => 0];
    //                 $this->stockModel->update($stock['id'], $data);
    //             }
    //         }

    //         // フラッシュメッセージ
    //         $_SESSION['flash'] = "株価更新完了：成功 {$successCount} 件 / 失敗 {$errorCount} 件";

    //         // 一覧画面へ戻る
    //         header('Location: ' . BASE_PATH . '/admins');
    //     } catch (\Exception $e) {
    //         http_response_code($e->getCode() ?: 500);
    //         exit($e->getMessage());
    //     }
    // }

    public function updateStockPricesAll()
    {
        try {
            // 銘柄一覧取得
            $stmt = $this->pdo->query("SELECT id, symbol FROM stocks");
            $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $successCount = 0;
            $errorCount = 0;
            $errors_all = [];

            foreach ($stocks as $stock) {
                [$errors, $success] = $this->stockService->updateStockPrices($stock['id']);
                $errors_all += $errors;
                if ($success) {
                    $successCount++;
                    $data = ['tentative' => 0];
                    $this->stockModel->update($stock['id'], $data);
                } else {
                    $errorCount++;
                }
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
