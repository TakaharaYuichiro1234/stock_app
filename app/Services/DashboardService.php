<?php

namespace App\Services;

use PDO;
use App\Models\Split;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;

class DashboardService
{
    private PDO $pdo;
    private Split $splitModel;
    private Stock $stockModel;
    private Trade $tradeModel;
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->splitModel = new Split($this->pdo);
        $this->tradeModel = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockModel = new Stock($this->pdo);
    }

    public function calTradeSummary(): array 
    {
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if (!$userId) return [];



        $stockAccountPairs = $this->tradeModel->getPairOfStockAccount($userId);

        $summary = [];
        foreach($stockAccountPairs as $pair) {
            $stockId = $pair['stock_id'];
            $accountId = $pair['account_id'];

            $splits = $this->splitModel->getByStockId($stockId);
            $tradeData = $this->tradeModel->getAllByUserStockAccount($userId, $stockId, $accountId);

            $totalQuantity = 0.0;
            $averagePrice = 0.0;
            foreach($tradeData as $datum) {

                $effectiveSplits = array_filter($splits, function($split) use ($datum) {
                    // return date('Y-m-d', strtotime($split['date'])) > date('Y-m-d', strtotime($datum['date']));
                    return $split['date'] > $datum['date'];
                });

                $splitCoefficient = 1.0;
                foreach($effectiveSplits as $split) {
                    $splitCoefficient *= ((float)$split['numerator'] / (float)$split['denominator']);
                }

                $prevTotalQuantity = $totalQuantity;
                $totalQuantity += (float)$datum['quantity']* $splitCoefficient;
                if ($datum['type'] == 1) {
                    if($totalQuantity == 0) continue;   // 買付後に合計株数がゼロのときは、あとで、データ不正としてなんらかの処理を追加する
                    $averagePrice = ($prevTotalQuantity * $averagePrice + ((float)$datum['quantity'] * (float)$datum['price'])) / $totalQuantity;
                } else {
                    $averagePrice = $averagePrice;
                }
            }

            $summaryDatum = [
                'stock_id' => $stockId,
                'account_id' => $accountId,
                'total_quantity' => $totalQuantity,
                'average_price' => $averagePrice,
            ];
            $summary[] = $summaryDatum;
        }

        return $summary;
    }

}
