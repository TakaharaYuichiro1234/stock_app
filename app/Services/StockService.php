<?php

namespace App\Services;

use PDO;
use RuntimeException;
use App\Models\Split;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Services\StockPriceService;
use App\Services\YfinanceService;

class StockService
{
    private PDO $pdo;
    private Split $splitModel;
    private Stock $stockModel;
    private StockPrice $stockPriceModel;
    private StockPriceService $stockPriceService;
    private YfinanceService $yfinanceService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->splitModel = new Split($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->yfinanceService = new YfinanceService($pdo);
    }

    // public function initCreate(?string $symbol): array
    // {
    //     $data = null;
    //     $error = null;

    //     if ($symbol !== null && $symbol !== '') {
    //         $url = "http://127.0.0.1:5000/stock?symbol=" . urlencode($symbol);

    //         $context = stream_context_create([
    //             'http' => [
    //                 'timeout' => 3
    //             ]
    //         ]);

    //         $json = @file_get_contents($url, false, $context);

    //         if ($json === false) {
    //             $error = "Python APIに接続できません";
    //         } else {
    //             $data = json_decode($json, true);
    //             if (isset($data["error"])) {
    //                 $error = $data["error"];
    //             }
    //         }
    //     }

    //     return [$error, $data];
    // }


    public function updateStockPrices($stockId): array {
        $errors = [];
        $success = false;

        try {
            $stock = $this->stockModel->find($stockId);
            if ($stock) {
                // 株式分割データを更新
                $symbol = $stock['symbol'];

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
                        $this->splitModel->create($data);
                    }
                }

                // 株価データ更新
                $success = $this->stockPriceService ->updateLatestPrices($stock['id'], $stock['symbol'], false);
                if (!$success) {
                    throw new RuntimeException('更新失敗');
                }

                // Stockデータのtentativeをリセット
                $updateStockData = [
                    'tentative' => 0,
                ];
                $this->stockModel ->update($stock['id'], $updateStockData);
            }
            $success = true;

        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }  

        return [$errors, $success];
    }

    public function isSymbolRegistered(?string $symbol): bool
    {
        return $symbol ? $this->stockModel->existsBySymbol($symbol) : false;
    }
}
