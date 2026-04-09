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
use App\Services\StockService;

class StockApiController extends BaseApiController {
    private PDO $pdo;
    private Split $splitModel;
    private Stock $stockModel;
    private StockPrice $stockPriceModel;
    private User $userModel;
    private StockPriceService $stockPriceService;
    private YfinanceService $yfinanceService;
    private StockService $stockService;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->splitModel = new Split($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->yfinanceService = new YfinanceService($pdo);
        $this->stockService = new StockService($pdo);   
    }

    public function show($stockId) {
        try {
            $stock = $this->stockModel->find($stockId);   

            $this->jsonResponse([
                'stock' => $stock,
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

    public function store() {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'digit' => (int)$_POST['digit'],
            'symbol' => trim($_POST['symbol'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? ''),
            'long_name' => trim($_POST['long_name'] ?? ''),
            'tentative' => 0,
        ];

        $errors = StockValidator::validate($data);
        if ($errors) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $errors,
            ], 400);
            return;
        }

        $stockId = null;
        $this->pdo->beginTransaction();
        try {
            $stockId = $this->stockModel->create($data);
            $this->stockPriceService->updateLatestPrices($stockId, $data['symbol']);
            $this->pdo->commit();

            $this->jsonResponse([
                'success' => true,
                'data' => ['stockId'=>$stockId],
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['書き込みエラー'],
            ], 400);
        }
    }


    public function tentativeStore() {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'digit' => 0,
            'symbol' => trim($_POST['symbol'] ?? ''),
            'tentative' => 1,
        ];

        // $errors = StockValidator::validate($data);
        // if ($errors) {
        //     $this->jsonResponse([
        //         'success' => false,
        //         'errors'  => $errors,
        //     ], 400);
        //     return;
        // }

        if ($this->stockModel->findBySymbol($data['symbol'])) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['この証券コードは既に登録されています'],
            ], 200);
            return;
        }

        $stockId = null;
        $this->pdo->beginTransaction();
        try {
            $stockId = $this->stockModel->create($data);
            // $this->stockPriceService->updateLatestPrices($stockId, $data['symbol']);
            $this->pdo->commit();

            $this->jsonResponse([
                'success' => true,
                'data' => ['stockId'=>$stockId],
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['書き込みエラー'],
            ], 400);
        }
    }

    public function update() {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'digit' => (int)$_POST['digit']
        ];

        $errors = StockValidator::validate($data);
        if ($errors) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $errors,
            ], 400);
            return;
        }

        $id = $_POST['stock_id'] ?? '';
        try {
            $this->stockModel->update($id, $data);
                $this->jsonResponse([
                'success' => true,
                'errors'  => [],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['書き込みエラー'],
            ], 400);
        }
    }

    public function delete() {
        $stockId = $_POST['stockId'] ?? '';

        try {
            $this->stockModel->delete($stockId);   
            $this->jsonResponse([
                'success' => true,
                'errors'  => [],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー'],
            ], 400);
            return;
        }
    }

    public function getForChart($stockId): void {
        try {
            $prices = [
                'daily' => $this->stockPriceModel->getForChart($stockId, 'daily'),
                'weekly' => $this->stockPriceModel->getForChart($stockId, 'weekly'),
                'monthly' => $this->stockPriceModel->getForChart($stockId, 'monthly'),
            ];

            $this->jsonResponse([
                'success' => true,
                'data' => $prices,
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー'],
            ], 400);
        }
    }

    public function getUserStocks(): void {
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if (!$userId) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['invalid request'],
            ], 400);
            return;
        }

        try {
            $stocks = $this->stockModel->allWithLatestPrice($userId);

            $this->jsonResponse([
                'success' => true,
                'data' => $stocks,
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー'],
            ], 400);
        }
    }

    public function getFiltered(): void {
        $input = $_GET['keywords'] ?? '';
        if (!is_string($input) || mb_strlen($input) > 100) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['invalid request'],
            ], 400);
            return;
        }

        if ($input === '') {
            try {
                $stocks = $this->stockModel->all();

                $this->jsonResponse([
                    'success' => true,
                    'errors'  => [],
                    'data' => $stocks,
                ]);
            } catch (\Throwable $e) {
                $this->jsonResponse([
                    'success' => false,
                    'errors'  => ['データベースエラー'],
                ], 400);
            }      
            return;      
        }

        $keywords = preg_split('/\s+/', trim($input));
        $keywords = array_values(array_filter($keywords, fn($k) => $k !== ''));
        $keywords = array_slice($keywords, 0, 5);
        if (empty($keywords)) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['invalid request'],
            ], 400);
        }

        try {
            $stocks = $this->stockModel->filter($keywords);
            $this->jsonResponse([
                'success' => true,
                'errors'  => [],
                'data' => $stocks,
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー'],
            ], 400);
        }       
    }


    public function updateStockPrices() {
        $stockId = $_POST['stock_id'] ?? '';
        [$errors, $success] = $this->stockService->updateStockPrices($stockId);
        if (!$success) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $errors,
            ], 400);
            return;
        } else {
            $this->jsonResponse([
                'success' => true,
                'errors'  => $errors,
            ]);
        }
    }

    // public function updateStockPrices() {
    //     $stockId = $_POST['stock_id'] ?? '';
    //     $errors = [];

    //     try {
    //         $stock = $this->stockModel->find($stockId);
    //         if ($stock) {
    //             // 株式分割データを更新
    //             $symbol = $stock['symbol'];

    //             [$error, $splitData] = $this->yfinanceService->getSplits($symbol);
    //             if ($error) $errors[] = $error;
    //             if ($splitData) {
    //                 foreach ($splitData as $split) {
    //                     $data = [
    //                         'stock_id' => $stockId,
    //                         'date' => $split['date'],
    //                         'numerator' => $split['numerator'],
    //                         'denominator' => $split['denominator'],
    //                     ];
    //                     $this->splitModel->create($data);
    //                 }
    //             }

    //             // 株価データ更新
    //             $success = $this->stockPriceService ->updateLatestPrices($stock['id'], $stock['symbol'], false);
    //             if (!$success) {
    //                 throw new RuntimeException('更新失敗');
    //             }

    //             // Stockデータのtentativeをリセット
    //             $updateStockData = [
    //                 'tentative' => 0,
    //             ];
    //             $this->stockModel ->update($stock['id'], $updateStockData);
    //         }

    //         $this->jsonResponse([
    //             'success' => true,
    //             'errors'  => [],
    //             'data' => $stock,
    //         ]);
    //     } catch (\Throwable $e) {
    //         $this->jsonResponse([
    //             'success' => false,
    //             'errors'  => ['データベースエラー'],
    //         ], 400);
    //     }   
    // }


    public function findBySymbol($symbol) {
        // $symbol = $_GET['symbol'] ?? '';
        if (!is_string($symbol) || mb_strlen($symbol) > 20) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['invalid request'],
            ], 400);
            return;
        }

        try {
            $stock = $this->stockModel->findBySymbol($symbol. ".T");

            $this->jsonResponse([
                'success' => true,
                'data' => $stock,
                'errors' => [$symbol. ".T"],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー'],
            ], 400);
        }       
    }
}
