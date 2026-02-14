<?php
namespace App\Models;
use PDO;

class StockPrice {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function filterByStockId(int $stockId): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM stock_prices WHERE stock_id = ? ORDER BY date ASC'
        );

        $stmt->execute([$stockId]);
        $stockPrices = $stmt->fetchAll();

        return $stockPrices;
    }

    public function upsertPrices(int $stockId, array $prices): void {
        $sql = "
            INSERT INTO stock_prices
            (stock_id, date, open, high, low, close, volume)
            VALUES
            (:stock_id, :date, :open, :high, :low, :close, :volume)
            ON DUPLICATE KEY UPDATE
                open = VALUES(open),
                high = VALUES(high),
                low = VALUES(low),
                close = VALUES(close),
                volume = VALUES(volume)
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($prices as $row) {
            $stmt->execute([
                ':stock_id' => $stockId,
                ':date'     => $row['date'],
                ':open'     => $row['open'],
                ':high'     => $row['high'],
                ':low'      => $row['low'],
                ':close'    => $row['close'],
                ':volume'   => $row['volume'],
            ]);
        }
    }

    public function getLatestDate(int $stockId): ?string {
        $stmt = $this->pdo->prepare(
            'SELECT MAX(date) FROM stock_prices WHERE stock_id = ?'
        );

        $stmt->execute([$stockId]);
        $latestDate = $stmt->fetchColumn();

        return $latestDate ?: null;
    }

    public function getForChart(int $stockId, $granularity = "daily"): array {
        $stmt = null;
        switch ($granularity) {
            case 'daily':
                $stmt = $this->pdo->prepare($this->dailySql());
                break;
            case 'weekly':
                $stmt = $this->pdo->prepare($this->weeklySql());
                break;
            case 'monthly':
                $stmt = $this->pdo->prepare($this->monthlySql());
                break;
        }
        
        if ($stmt) {
            $stmt->execute([$stockId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    }

    function dailySql(): string {
        $partition = 'date';
        return "
            SELECT
                DATE(date) AS time,
                open, high, low, close
            FROM stock_prices
            WHERE stock_id = ?
            ORDER BY date
        ";
    }

    function weeklySql(): string {
        return "
            WITH data AS (
                SELECT
                    DATE_SUB(
                        MIN(date) OVER (PARTITION BY stock_id, YEARWEEK(date, 1)),
                        INTERVAL WEEKDAY(
                            MIN(date) OVER (PARTITION BY stock_id, YEARWEEK(date, 1))
                        ) DAY
                    ) AS time,

                    FIRST_VALUE(open) OVER (
                        PARTITION BY stock_id, YEARWEEK(date, 1)
                        ORDER BY date
                    ) AS open,

                    MAX(high) OVER (
                        PARTITION BY stock_id, YEARWEEK(date, 1)
                    ) AS high,

                    MIN(low) OVER (
                        PARTITION BY stock_id, YEARWEEK(date, 1)
                    ) AS low,

                    LAST_VALUE(close) OVER (
                        PARTITION BY stock_id, YEARWEEK(date, 1)
                        ORDER BY date
                        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
                    ) AS close
                FROM stock_prices
                WHERE stock_id = ?
            )
            SELECT DISTINCT
                time,
                open,
                high,
                low,
                close
            FROM data
            ORDER BY time;
        ";
    }

    function monthlySql(): string {
        return "
            WITH data AS (
                SELECT
                    -- ★ 月初日を安全に算出
                    DATE_SUB(
                        date,
                        INTERVAL DAY(date) - 1 DAY
                    ) AS time,

                    FIRST_VALUE(open) OVER (
                        PARTITION BY stock_id, YEAR(date), MONTH(date)
                        ORDER BY date
                    ) AS open,

                    MAX(high) OVER (
                        PARTITION BY stock_id, YEAR(date), MONTH(date)
                    ) AS high,

                    MIN(low) OVER (
                        PARTITION BY stock_id, YEAR(date), MONTH(date)
                    ) AS low,

                    LAST_VALUE(close) OVER (
                        PARTITION BY stock_id, YEAR(date), MONTH(date)
                        ORDER BY date
                        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
                    ) AS close
                FROM stock_prices
                WHERE stock_id = ?
            )
            SELECT DISTINCT
                time,
                open,
                high,
                low,
                close
            FROM data
            ORDER BY time;
        ";
    }
}
