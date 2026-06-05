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
use App\Models\Dividend;

class DividendService
{
    private PDO $pdo;
    private Split $splitModel;
    private Stock $stockModel;
    private Trade $tradeModel;
    private User $userModel;
    private StockPrice $stockPriceModel;
    private Dividend $dividendModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->splitModel = new Split($this->pdo);
        $this->tradeModel = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->dividendModel = new Dividend($this->pdo);
    }

    // private function createDateArray(): array 
    // {
    //     $start = new DateTime('-1 year');
    //     $end = new DateTime(); // 今日
    //     $end->modify('+1 day'); // 今日を含めるために+1日

    //     $interval = new DateInterval('P1D');
    //     $period = new DatePeriod($start, $interval, $end);

    //     $dates = [];

    //     foreach ($period as $date) {
    //         $dates[] = $date->format('Y-m-d');
    //     }

    //     return $dates;

    // }

    private function createDateArray(): array
    {
        $start = new DateTime('first day of january this year');
        $end = new DateTime('first day of january next year');

        // payment_date基準で今年のデータを取得するには、record_dateとしては少なくとも3ヶ月前のデータが必要
        $start->modify('-6 month');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
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


    public function getExpectedDividends(int $userId, array $stockAccountPairs) : array{
        $dates = $this->createDateArray();
        $start = new DateTime('first day of january this year');
        $end = new DateTime('first day of january next year');

        $results = [];
        foreach($stockAccountPairs as $pair) {
            $stockId = $pair['stock_id'];
            $accountId = $pair['account_id'];

            $dailyTrades = $this->createDailyTradeArray($userId, $stockId, $accountId, $dates);
            $dividends = $this->dividendModel->findByStockId($stockId); 
            $expected_dividend = 0;
            foreach ($dividends as $dividend) {
                $paymentDt = new DateTime($dividend['payment_date']);

                if (!($paymentDt >= $start && $paymentDt < $end)) {
                    continue;
                }

                $targetDate = $dividend['record_date'];
                $matchedDailyTrade = null;
                foreach ($dailyTrades as $dailyTrade) {
                    if ($dailyTrade['date'] === $targetDate) {
                        $matchedDailyTrade = $dailyTrade;
                        break;
                    }
                }

                if ($matchedDailyTrade === null) {
                    continue;
                }

                $expected_dividend += (float)$dividend['dps'] * $matchedDailyTrade['total_quantity'] ;
            }

            $result = [
                'stock_id' => $stockId,
                'account_id' => $accountId,
                'expected_dividend' => $expected_dividend,
            ];

            $results[] = $result;
        }
        return $results;
    } 

}