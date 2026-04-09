<?php
namespace App\Controllers\Api;

use PDO;
use RuntimeException;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Models\Split;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\User;
use App\Services\StockPriceService;
use App\Validations\StockValidator;
use App\Services\YfinanceService;

class SplitApiController extends BaseApiController {
    private PDO $pdo;
    private Stock $stockModel;
    private Split $splitModel;
    private StockPrice $stockPriceModel;
    private User $userModel;
    private StockPriceService $stockPriceService;
    private YfinanceService $yfinanceService;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->splitModel = new Split($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->yfinanceService = new YfinanceService($pdo);
    }

    public function show($stockId) {
        try {
            $split = $this->splitModel->getByStockId($stockId);   

            $this->jsonResponse([
                'split' => $split,
                'success' => true,
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー']
            ], 400);
        }
    }

    // public function store() {
    //     $data = [
    //         'stock_id' => (int)$_POST['stock_id'],
    //         'date' => trim($_POST['date'] ?? ''),
    //         'numerator' => (int)$_POST['numerator'],
    //         'denominator' => (int)$_POST['denominator'],
    //     ];

    //     $splitId = null;
    //     $this->pdo->beginTransaction();
    //     try {
    //         $splitId = $this->splitModel->create($data);
    //         $this->stockPriceService->updateLatestPrices($splitId, $data['symbol']);
    //         $this->pdo->commit();

    //         $this->jsonResponse([
    //             'success' => true,
    //             'data' => ['splitId'=>$splitId],
    //             'errors' => [],
    //         ]);
    //     } catch (\Throwable $e) {
    //         $this->pdo->rollBack();
    //         $this->jsonResponse([
    //             'success' => false,
    //             'errors'  => ['書き込みエラー'],
    //         ], 400);
    //     }
    // }


    public function store() {
        $stockId = (int)$_POST['stock_id'] ?? 0;
        $stock = $this->stockModel->find($stockId);
        if (!$stock) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['株が見つかりません'],
            ], 400);
            return;
        }
        $symbol = $stock['symbol'];

        $errors = [];

        [$error, $splitData] = $this->yfinanceService->getSplits($symbol);
        if ($error) $errors[] = $error;
        if ($splitData) {
            foreach ($splitData as $split) {
                $data = [
                    'stock_id' => $stockId,
                    'date' => $split['date'],
                    'numerator' => $split['numerator'],
                    'denominator' => $split['denominator'],
                ];

                try {
                    $this->pdo->beginTransaction();
                    $splitId = $this->splitModel->create($data);
                    // $this->stockPriceService->updateLatestPrices($splitId, $symbol);
                    $this->pdo->commit();
                } catch (\Throwable $e) {
                    $this->pdo->rollBack();
                    $errors[] = "分割情報の保存に失敗: " . $split['date'];
                }
            }
  
        }
          $this->jsonResponse([
            'success' => true,
            'errors' => $errors,
        ]);
    }
}
