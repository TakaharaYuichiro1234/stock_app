<?php

namespace App\Controllers\Api;

use PDO;
use RuntimeException;
use App\Core\BaseApiController;
use App\Models\Dividend;
use App\Models\Stock;
use App\Models\User;
use App\Services\YfinanceService;


class DividendApiController extends BaseApiController
{
    private PDO $pdo;
    private Dividend $dividendModel;
    private Stock $stockModel;
    private User $userModel;
    private YfinanceService $yfinanceService;

    public function __construct()
    {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->dividendModel = new Dividend($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->yfinanceService = new YfinanceService($pdo);
    }
    
    public function index(): void
    {
        $stockId = $_GET['stock_id'] ?? null;

        try {
            $dividends = [];
            if ($stockId) {
                $dividends = $this->dividendModel->findByStockId($stockId);
            } 

            $this->jsonResponse([
                'dividends' => $dividends,
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

    public function getExpectDividend(int $stock_id): void
    {        
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if (!$userId) throw new RuntimeException('ユーザーが存在しません');



            $this->jsonResponse([
                'success' => true,
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

    public function store(): void
    {
        $data = json_decode($_POST['input_dividends'], true);
        // try {
            if (!$data['stock_id'] || !$data['record_date'] || !$data['status'] || !$data['dps'] || !$data['payment_date']) {
                throw new RuntimeException('必須項目が不足しています');
            }

            // $id =$this->dividendModel->create($data['stock_id'], $data);
            $id = $this->dividendModel->upsert($data);

            $this->jsonResponse([
                'success' => true,
                'id' => $id,
                'errors' => [],
            ]);
        // } catch (\Throwable $e) {
        //     $this->jsonResponse([
        //         'success' => false,
        //         'errors'  => ['データベースエラー']
        //     ], 400);
        // }
    }

    public function update(int $id): void
    {
        $data = json_decode($_POST['input_dividends'], true);

        try {
            if (!$data['stock_id'] || !$data['record_date'] || !$data['status'] || !$data['dps'] || !$data['payment_date']) {
                throw new RuntimeException('必須項目が不足しています');
            }

            $this->dividendModel->update($id, $data);

            $this->jsonResponse([
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

    public function upsert(): void
    {
        $dataList = json_decode($_POST['input_dividends_list'], true);
        $results = [];
        $this->pdo->beginTransaction();
        try {
            foreach($dataList as $data) {
                if (!$data['stock_id'] || !$data['record_date'] || !$data['status'] || !$data['dps'] || !$data['payment_date']) {
                    continue;
                }
                $results[] = $this->dividendModel->upsert($data);
            }
            $this->pdo->commit();
            $this->jsonResponse([
                'success' => true,
                'errors' => [],
                'results' => $results,
            ]);

        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー']
            ], 400);
        }

    }



    public function delete(int $id): void
    {
        try {
            $this->dividendModel->delete($id);

            $this->jsonResponse([
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

    public function getYfinanceDividends()
    {
        $stockId = (int)$_GET['stock_id'] ?? 0;
        $stock = $this->stockModel->find($stockId);
        if (!$stock) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['株が見つかりません'],
            ], 400);
            return;
        }
        $symbol = $stock['symbol'];

        [$error, $dividends] = $this->yfinanceService->getDividends($symbol);
        if ($error) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => [$error],
            ], 400);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'dividends' => $dividends,
            'errors' => [],
        ]);




        // $this->jsonResponse([
        //     'success' => true,
        //     'dividends' => 'test1',
        //     'errors' => [],
        // ]);

    }
}
