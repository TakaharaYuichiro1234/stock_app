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
        $totalRealize = 0.0;
        $totalDividend = 0.0;
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
            if ($datum['type'] == 1) {      // 買付
                if($totalQuantity == 0) continue;   // 買付後に合計株数がゼロのときは、あとで、データ不正としてなんらかの処理を追加する
                $averagePrice = ($prevTotalQuantity * $averagePrice + ((float)$datum['quantity'] * (float)$datum['price'])) / $totalQuantity;
            } else if ($datum['type'] == 2) {   // 売付
                // $averagePrice = $averagePrice;

                $realize = -(float)$datum['quantity'] * ((float)$datum['price'] - $averagePrice * $splitCoefficient);
                $totalRealize += $realize;
            } else if ($datum['type'] == 3) {   // 配当
                $dividend = (float)$datum['subtotal'];
                $totalDividend += $dividend;
            }

            $tradeDataWithAveragePrice[] = [
                'date' => $datum['date'],
                'total_quantity' => $totalQuantity,
                'average_price' => $averagePrice,
                'total_realize' => $totalRealize,
                'total_dividend' => $totalDividend,
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
                'total_quantity' => $matchedTrade ? $matchedTrade['total_quantity'] : null,
                'average_price' => $matchedTrade ? $matchedTrade['average_price'] : null,
                'total_realize' => $matchedTrade ? $matchedTrade['total_realize'] : null,
                'total_dividend' => $matchedTrade ? $matchedTrade['total_dividend'] : null,
            ];     
        }
        
        return $dailyTrades;    
    }




    public function dailyAssetDetail(int $userId, array $stockAccountPairs): array 
    {
        $dates = $this->createDateArray();

        $results = [];
        $latests = [];
        foreach($stockAccountPairs as $pair) {
            $stockId = $pair['stock_id'];
            $accountId = $pair['account_id'];

            $dailyPrices = $this->createDailyPriceArray($stockId, $dates); 
            $dailyTrades = $this->createDailyTradeArray($userId, $stockId, $accountId, $dates);

            $latest = null;
            foreach($dailyTrades as $index => $dailyTrade) {
                $totalQuantity = $dailyTrade['total_quantity'];
                $averagePrice = $dailyTrade['average_price'];
                $price = $dailyPrices[$index]['price'];

                $result = [
                    'date' => $dailyTrade['date'],
                    'stock_id' => $stockId,
                    'account_id' => $accountId,
                    'total_quantity' => $totalQuantity,
                    'average_price' => $averagePrice,
                    'total_realize' => $dailyTrade['total_realize'],
                    'total_dividend' => $dailyTrade['total_dividend'],
                    'price' => $price,
                    'asset_value' => ($totalQuantity !== null && $price !== null) ? ($totalQuantity * $price) : null,
                    'profit_loss' => ($totalQuantity !== null && $price !== null && $averagePrice !== null) ? (($price - $averagePrice) * $totalQuantity) : null,
                ];
                $results[] = $result;

                if (!$latest) {
                    $latest = $result;
                } else {
                    if ($latest['date'] < $result['date']) $latest = $result;
                }
            }
            $latests[] = $latest;
        }
        return [$results, $latests];
    }


    public function dailyAssets(): array
    {   
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if (!$userId) return [];

        $stockAccountPairs = $this->tradeModel->getPairOfStockAccount($userId);


        $objects = $this->  dailyAssetDetail($userId, $stockAccountPairs);
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

        ksort($result);
        $result = array_values($result);

        return $result;
    }


    // public function dailyAssetsIndividual(int $stockId, int $accountId): array
    // {   
    //     $uuid = $_SESSION['user']['uuid'];
    //     $userId = $this->userModel->getUserIdByUuid($uuid);
    //     if (!$userId) return [];

    //     $stockAccountPairs = [
    //         ['stock_id' => $stockId, 'account_id' => $accountId],
    //     ];


    //     $objects = $this->  dailyAssetDetail($userId, $stockAccountPairs);

    //     // return $objects;


    //     $result = [];

    //     foreach ($objects as $obj) {
    //         $date = $obj['date'];

    //         if (!isset($result[$date])) {
    //             $result[$date] = [
    //                 'date' => $date,
    //                 'total_asset_value' => 0,
    //                 'total_profit_loss' => 0,
    //             ];
    //         }

    //         $result[$date]['total_asset_value'] += $obj['asset_value'];
    //         $result[$date]['total_profit_loss'] += $obj['profit_loss'];
    //     }

    //     ksort($result);
    //     $result = array_values($result);

    //     return $result;
    // }





    public function dailyAssetsTotal(array $dailyAssetDetail): array
    {   
        $result = [];

        foreach ($dailyAssetDetail as $obj) {
            $date = $obj['date'];

            if (!isset($result[$date])) {
                $result[$date] = [
                    'date' => $date,
                    'total_asset_value' => 0,
                    'total_profit_loss' => 0,
                    'total_realize' => 0,
                    'total_dividend' => 0,
                ];
            }

            $result[$date]['total_asset_value'] += $obj['asset_value'];
            $result[$date]['total_profit_loss'] += $obj['profit_loss'];
            $result[$date]['total_realize'] += $obj['total_realize'];
            $result[$date]['total_dividend'] += $obj['total_dividend'];
        }

        ksort($result);
        $result = array_values($result);

        return $result;
    }


}