<?php

namespace App\Services;

use PDO;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Models\Split;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\StockPrice;

class AssetService
{
    private PDO $pdo;
    private Split $splitModel;
    private Stock $stockModel;
    private Trade $tradeModel;
    private User $userModel;
    private StockPrice $stockPriceModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->splitModel = new Split($this->pdo);
        $this->tradeModel = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
    }

    private function createDateArray(): array 
    {
        $start = new DateTime('-1 year');
        $end = new DateTime(); // 今日
        $end->modify('+1 day'); // 今日を含めるために+1日

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;

    }

    private function createDailyPriceArray(int $stockId, array $dates): array {
        $stockPrices = $this->stockPriceModel->filterByStockId($stockId); 

        $dailyPrices = [];
        foreach($dates as $date) {
            $i = 0;
            $n = count($stockPrices);
            $matchedPrice = null;
            while (($i < $n) && ($stockPrices[$i]['date'] <= $date)) {
                $matchedPrice = $stockPrices[$i];
                $i++;
            }

            $dailyPrices[] = [
                'date' => $date,
                'stock_id' => $stockId,
                'price' => $matchedPrice ? $matchedPrice['close'] : null,
            ];     
        }
        
        return $dailyPrices;    
    }

    private function createDailyTradeArray(int $userId, int $stockId, int $accountId, array $dates): array {

        $splits = $this->splitModel->getByStockId($stockId);
        $tradeData = $this->tradeModel->getAllByUserStockAccount($userId, $stockId, $accountId);

        $totalQuantity = 0.0;
        $averagePrice = 0.0;
        $tradeDataWithAveragePrice = [];
        foreach($tradeData as $datum) {

            $effectiveSplits = array_filter($splits, function($split) use ($datum) {
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

            $tradeDataWithAveragePrice[] = [
                'date' => $datum['date'],
                'quantity' => $totalQuantity,
                'average_price' => $averagePrice,
            ];
        }

        $dailyTrades = [];
        foreach($dates as $date) {
            $i = 0;
            $n = count($tradeDataWithAveragePrice);
            $matchedTrade = null;
            while (($i < $n) && ($tradeDataWithAveragePrice[$i]['date'] <= $date)) {
                $matchedTrade = $tradeDataWithAveragePrice[$i];
                $i++;
            }

            $dailyTrades[] = [
                'date' => $date,
                'stock_id' => $stockId,
                'account_id' => $accountId,
                'quantity' => $matchedTrade ? $matchedTrade['quantity'] : null,
                'average_price' => $matchedTrade ? $matchedTrade['average_price'] : null,
            ];     
        }
        
        return $dailyTrades;    
    }




    private function calSummary(): array 
    {
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if (!$userId) return [];

        $stockAccountPairs = $this->tradeModel->getPairOfStockAccount($userId);
        $dates = $this->createDateArray();

        $summary = [];
        foreach($stockAccountPairs as $pair) {
            $stockId = $pair['stock_id'];
            $accountId = $pair['account_id'];

            if ($accountId == 2) continue;
            if ($accountId == 3) continue;

            $dailyPrices = $this->createDailyPriceArray($stockId, $dates); 
            $dailyTrades = $this->createDailyTradeArray($userId, $stockId, $accountId, $dates);

            foreach($dailyTrades as $index => $dailyTrade) {
                $date = $dailyTrade['date'];
                $quantity = $dailyTrade['quantity'];
                $averagePrice = $dailyTrade['average_price'];
                $price = $dailyPrices[$index]['price'];

                $summary[] = [
                    'date' => $date,
                    'stock_id' => $stockId,
                    'account_id' => $accountId,
                    'quantity' => $quantity,
                    'average_price' => $averagePrice,
                    'price' => $price,
                    'asset_value' => ($quantity !== null && $price !== null) ? ($quantity * $price) : null,
                    'profit_loss' => ($quantity !== null && $price !== null && $averagePrice !== null) ? (($price - $averagePrice) * $quantity) : null,
                ];
            }
        }

        return $summary;
    }


    public function dailyAssets(): array
    {   
        // $stockId = 238;
        // $userId = 1;
        // $accountId = 2;

        // $dates = $this->createDateArray();
        // $dailyPrices = $this->createDailyPriceArray($stockId, $dates); 
        // $dailyTrades = $this->createDailyTradeArray($userId, $stockId, $accountId, $dates);



        // $stockPrices = $this->stockPriceModel->filterByStockId($stockId); 


        $objects = $this->  calSummary();

        $result = [];

        foreach ($objects as $obj) {
            $date = $obj['date'];

            if (!isset($result[$date])) {
                $result[$date] = [
                    'date' => $date,
                    'total_asset_value' => 0,
                    'total_profit_loss' => 0,
                ];
            }

            $result[$date]['total_asset_value'] += $obj['asset_value'];
            $result[$date]['total_profit_loss'] += $obj['profit_loss'];
        }

        // ★ 日付キーで昇順ソート
        ksort($result);

        // 添字を振り直す
        $result = array_values($result);

        return $result;

    }
}