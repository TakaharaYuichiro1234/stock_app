<?php
namespace App\Models;
use PDO;
use App\Data\TradeData;

// require_once __DIR__ . '/../Data/TradeData.php';

class Trade {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this -> pdo = $pdo;
    }

    public function getByUserIdAndStockId($user_id, $stock_id): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM trades WHERE (user_id = ?) AND (stock_id = ?)'
        );
        $stmt->execute([$user_id, $stock_id]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, TradeData $trade): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO trades (user_id, stock_id, date, price, quantity, type, content) VALUES (?, ?, ?, ?, ?, ?, ?);
        ');

        $stmt->execute([
            $userId,
            $trade->stock_id,
            $trade->date,
            $trade->price,
            $trade->quantity,
            $trade->type,
            $trade->content
        ]);

        return (int)$this->pdo->lastInsertId();
    }



    public function update(int $id, TradeData $trade): void {
        $stmt = $this->pdo->prepare(
            'UPDATE trades SET date = ?, price = ?, quantity = ?, type = ?, content = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([
            $trade->date,
            $trade->price,
            $trade->quantity,
            $trade->type,
            $trade->content,
            $id
        ]);
    }

    public function delete(int $id): void {
        $stmt = $this->pdo->prepare(
            'DELETE FROM trades WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function getAmounts(int $userId, int $stockId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                SUM(CASE WHEN type = 1 THEN quantity * price ELSE 0 END)
                -SUM(CASE WHEN type = 2 THEN quantity * price ELSE 0 END) 
                +SUM(
                    CASE
                        WHEN type NOT IN (1, 2) OR type IS NULL
                        THEN quantity * price
                        ELSE 0
                    END
                ) AS total,

                SUM(CASE WHEN type = 1 THEN quantity ELSE 0 END)
                -SUM(CASE WHEN type = 2 THEN quantity ELSE 0 END) 
                +SUM(
                    CASE
                        WHEN type NOT IN (1, 2) OR type IS NULL
                        THEN quantity
                        ELSE 0
                    END
                ) AS quantity

            FROM trades
            WHERE user_id = ? AND stock_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId, $stockId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getForChart(int $userId, int $stockId, $granularity = "daily"): array
    {
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
            $stmt->execute([$userId, $stockId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
        
    }
    function dailySql(): string{
        return "
            SELECT
                type,
                date AS time,
                SUM(quantity) AS total_quantity,
                SUM(quantity * price) / NULLIF(SUM(quantity), 0) AS avg_price
            FROM trades
            WHERE user_id = ? AND stock_id = ? AND date IS NOT NULL
            GROUP BY date, type
            ORDER BY date;
        ";
    }

    function weeklySql(): string{
        return "
            SELECT
                type,
                DATE_SUB(MIN(date), INTERVAL WEEKDAY(MIN(date)) DAY) AS time,
                SUM(quantity) AS total_quantity,
                SUM(quantity * price) / NULLIF(SUM(quantity), 0) AS avg_price
            FROM trades
            WHERE user_id = ? AND stock_id = ? AND date IS NOT NULL
            GROUP BY YEARWEEK(date, 1), type
            ORDER BY YEARWEEK(date, 1);
        ";
    }
    
    function monthlySql(): string{
        return "
            SELECT
                type,
                STR_TO_DATE(
                    CONCAT(y, '-', LPAD(m, 2, '0'), '-01'),
                    '%Y-%m-%d'
                ) AS time,

                SUM(quantity) AS total_quantity,
                SUM(quantity * price) / NULLIF(SUM(quantity), 0) AS avg_price
            FROM (
                SELECT
                    YEAR(date)  AS y,
                    MONTH(date) AS m,
                    quantity,
                    price,
                    type
                FROM trades
                WHERE user_id = ? AND stock_id = ? AND date IS NOT NULL
            ) t
            GROUP BY y, m, type
            ORDER BY y, m;
        ";
    }
}