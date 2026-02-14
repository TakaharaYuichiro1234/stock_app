<?php
namespace App\Services;
use PDO;
use DateTime;
use App\Models\StockPrice;

class StockPriceService {
    private PDO $pdo;
    private StockPrice $stockPriceModel;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->stockPriceModel = new StockPrice($this->pdo);
    }

    public function updateLatestPrices(int $stockId, string $symbol, bool $onlyLatest = true): bool {
        $latestDate = $this->stockPriceModel->getLatestDate($stockId);

        // 初回登録時のみ1年分
        if ($latestDate === null || !$onlyLatest) {
            $start = (new DateTime("-1 year"))->format("Y-m-d");
        } else {
            $start = (new DateTime($latestDate))
                // ->modify("+1 day")
                ->format("Y-m-d");
        }

        $url = "http://127.0.0.1:5000/stock/history"
            . "?symbol=" . urlencode($symbol)
            . "&start=" . $start;

        $json = @file_get_contents($url);
        if ($json === false) {
            return false;
        }

        $prices = json_decode($json, true);
        if (empty($prices)) {
            return true; // 更新なし＝成功
        }

        $this->stockPriceModel->upsertPrices($stockId, $prices);
        return true;
    }
}