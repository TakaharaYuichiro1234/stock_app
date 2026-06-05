<?php

namespace App\Models;

use PDO;

class Dividend
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM dividends');
        return $stmt->fetchAll();
    }

    public function findByStockId(int $stockId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM dividends WHERE stock_id = ? ORDER BY record_date ASC');
        $stmt->execute([$stockId]);
        return $stmt->fetchAll();
    }

    public function getExpectedDividends(): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                stock_id, 
                SUM(dps) as expected_dividend
            FROM dividends
            WHERE record_date >= CURDATE() 
            AND record_date < CURDATE() + INTERVAL 1 YEAR
            GROUP BY stock_id
            ORDER BY stock_id ASC;
        ');
        return $stmt->fetchAll();
    }
    // public function getExpectedDividends(): array
    // {
    //     $stmt = $this->pdo->query('
    //         SELECT 
    //             stock_id, 
    //             SUM(dps) as expected_dividend
    //         FROM dividends
    //         WHERE payment_date >= DATE_FORMAT(CURDATE(), "%Y-01-01")
    //         AND payment_date < DATE_FORMAT(CURDATE() + INTERVAL 1 YEAR, "%Y-01-01")
    //         GROUP BY stock_id
    //         ORDER BY stock_id ASC
    //     ');

    //     return $stmt->fetchAll();
    // }

    

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE dividends 
            SET record_date = ?, status = ?, dps = ?, payment_date = ? 
            WHERE id = ? ;
        ');
        $stmt->execute([
            $data['record_date'],
            $data['status'],
            $data['dps'],
            $data['payment_date'],
            $id
        ]);
    }

    // public function create(int $stockId, array $data): int
    // {
    //     $stmt = $this->pdo->prepare('
    //         INSERT INTO dividends (stock_id, record_date, status, dps, payment_date) VALUES (?, ?, ?, ?, ?);
    //     ');

    //     $stmt->execute([
    //         $stockId,
    //         $data['record_date'],
    //         $data['status'],
    //         $data['dps'],
    //         $data['payment_date'],
    //     ]);

    //     return (int)$this->pdo->lastInsertId();
    // }
 
    public function upsert(array $data): int
    {
        $sql = '
            INSERT INTO dividends
            (stock_id, record_date, status, dps, payment_date)
            VALUES
            (:stock_id, :record_date, :status, :dps, :payment_date)
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                record_date  = VALUES(record_date),
                status       = VALUES(status),
                dps          = VALUES(dps),
                payment_date = VALUES(payment_date)
        ';

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':stock_id'    => $data['stock_id'],
            ':record_date' => $data['record_date'],
            ':status'      => $data['status'],
            ':dps'         => $data['dps'],
            ':payment_date'=> $data['payment_date'],
        ]);

        return (int)$this->pdo->lastInsertId();

        // $affectedRows = $stmt->rowCount();

        // if ($affectedRows === 1) {
        //     $action = 'insert';
        // } elseif ($affectedRows === 2) {
        //     $action = 'update';
        // } else {
        //     $action = 'no_change';
        // }

        // return [
        //     'id'     => (int)$this->pdo->lastInsertId(),
        //     'action' => $action,
        // ];
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM dividends WHERE id = ?'
        );
        $stmt->execute([$id]);
    }



    // public function getByUserIdAndStockId($user_id, $stock_id): array
    // {
    //     $stmt = $this->pdo->prepare(
    //         'SELECT * FROM trades WHERE (user_id = ?) AND (stock_id = ?)'
    //     );
    //     $stmt->execute([$user_id, $stock_id]);
    //     return $stmt->fetchAll();
    // }

    // public function getAllByUserId($user_id): array
    // {
    //     $stmt = $this->pdo->prepare(
    //         'SELECT 
    //             t.id as id, t.user_id as user_id, t.date as date, t.price as price, t.quantity as quantity , t.type as type, t.subtotal as subtotal,
    //             s.symbol as symbol, s.name as name, s.digit as digit, a.content as content 
    //         FROM trades t 
    //         JOIN stocks s ON t.stock_id = s.id 
    //         JOIN accounts a ON t.account_id = a.id 
    //         WHERE t.user_id = ? 
    //         ORDER BY t.date desc'
    //     );
    //     $stmt->execute([$user_id]);
    //     return $stmt->fetchAll();
    // }

    // public function getAllByUserStockAccount($user_id, $stock_id, $account_id): array
    // {
    //     $stmt = $this->pdo->prepare(
    //         'SELECT *
    //          FROM trades
    //          WHERE user_id = ? AND stock_id = ? AND account_id = ? 
    //          ORDER BY date'
    //     );

    //     $stmt->execute([$user_id, $stock_id, $account_id]);
    //     return $stmt->fetchAll();
    // }


    // public function getPairOfStockAccount($user_id): array
    // {
    //     $stmt = $this->pdo->prepare(
    //         'SELECT DISTINCT stock_id, account_id
    //          FROM trades
    //          WHERE user_id = ? AND (type = 1 OR type = 2)
    //          ORDER BY stock_id, account_id;'
    //     );
    //     $stmt->execute([$user_id]);
    //     return $stmt->fetchAll();
    // }

    // public function create(int $userId, TradeData $trade): int
    // {
    //     $stmt = $this->pdo->prepare('
    //         INSERT INTO trades (user_id, stock_id, account_id, date, price, quantity, type, content, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
    //     ');

    //     $stmt->execute([
    //         $userId,
    //         $trade->stock_id,
    //         $trade->account_id,
    //         $trade->date,
    //         $trade->price,
    //         $trade->quantity,
    //         $trade->type,
    //         $trade->content,
    //         $trade->subtotal,
    //     ]);

    //     return (int)$this->pdo->lastInsertId();
    // }



    // public function update(int $id, TradeData $trade): void
    // {
    //     $stmt = $this->pdo->prepare(
    //         'UPDATE trades SET date = ?, price = ?, quantity = ?, type = ?, content = ?, subtotal = ?, updated_at = NOW() WHERE id = ?'
    //     );
    //     $stmt->execute([
    //         $trade->date,
    //         $trade->price,
    //         $trade->quantity,
    //         $trade->type,
    //         $trade->content,
    //         $trade->subtotal,
    //         $id
    //     ]);
    // }

    // public function delete(int $id): void
    // {
    //     $stmt = $this->pdo->prepare(
    //         'DELETE FROM trades WHERE id = ?'
    //     );
    //     $stmt->execute([$id]);
    // }

    // public function getAmounts(int $userId, int $stockId): array
    // {
    //     $stmt = $this->pdo->prepare("
    //         SELECT
    //             SUM(CASE WHEN type = 1 THEN quantity * price ELSE 0 END)
    //             -SUM(CASE WHEN type = 2 THEN quantity * price ELSE 0 END) 
    //             +SUM(
    //                 CASE
    //                     WHEN type NOT IN (1, 2) OR type IS NULL
    //                     THEN quantity * price
    //                     ELSE 0
    //                 END
    //             ) AS total,

    //             SUM(CASE WHEN type = 1 THEN quantity ELSE 0 END)
    //             -SUM(CASE WHEN type = 2 THEN quantity ELSE 0 END) 
    //             +SUM(
    //                 CASE
    //                     WHEN type NOT IN (1, 2) OR type IS NULL
    //                     THEN quantity
    //                     ELSE 0
    //                 END
    //             ) AS quantity

    //         FROM trades
    //         WHERE user_id = ? AND stock_id = ?
    //         LIMIT 1
    //     ");

    //     $stmt->execute([$userId, $stockId]);
    //     return $stmt->fetch(PDO::FETCH_ASSOC);
    // }

    // public function getForChart(int $userId, int $stockId, $granularity = "daily"): array
    // {
    //     $stmt = null;
    //     switch ($granularity) {
    //         case 'daily':
    //             $stmt = $this->pdo->prepare($this->dailySql());
    //             break;
    //         case 'weekly':
    //             $stmt = $this->pdo->prepare($this->weeklySql());
    //             break;
    //         case 'monthly':
    //             $stmt = $this->pdo->prepare($this->monthlySql());
    //             break;
    //     }

    //     if ($stmt) {
    //         $stmt->execute([$userId, $stockId]);
    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } else {
    //         return [];
    //     }
    // }
    // function dailySql(): string
    // {
    //     return "
    //         SELECT
    //             type,
    //             date AS time,
    //             SUM(quantity) AS total_quantity,
    //             SUM(quantity * price) / NULLIF(SUM(quantity), 0) AS avg_price
    //         FROM trades
    //         WHERE user_id = ? AND stock_id = ? AND date IS NOT NULL
    //         GROUP BY date, type
    //         ORDER BY date;
    //     ";
    // }

    // function weeklySql(): string
    // {
    //     return "
    //         SELECT
    //             type,
    //             DATE_SUB(MIN(date), INTERVAL WEEKDAY(MIN(date)) DAY) AS time,
    //             SUM(quantity) AS total_quantity,
    //             SUM(quantity * price) / NULLIF(SUM(quantity), 0) AS avg_price
    //         FROM trades
    //         WHERE user_id = ? AND stock_id = ? AND date IS NOT NULL
    //         GROUP BY YEARWEEK(date, 1), type
    //         ORDER BY YEARWEEK(date, 1);
    //     ";
    // }

    // function monthlySql(): string
    // {
    //     return "
    //         SELECT
    //             type,
    //             STR_TO_DATE(
    //                 CONCAT(y, '-', LPAD(m, 2, '0'), '-01'),
    //                 '%Y-%m-%d'
    //             ) AS time,

    //             SUM(quantity) AS total_quantity,
    //             SUM(quantity * price) / NULLIF(SUM(quantity), 0) AS avg_price
    //         FROM (
    //             SELECT
    //                 YEAR(date)  AS y,
    //                 MONTH(date) AS m,
    //                 quantity,
    //                 price,
    //                 type
    //             FROM trades
    //             WHERE user_id = ? AND stock_id = ? AND date IS NOT NULL
    //         ) t
    //         GROUP BY y, m, type
    //         ORDER BY y, m;
    //     ";
    // }


    // public function getStockIdsByUserId(int $userId) : array
    // {
    //     $stmt = $this->pdo->prepare(
    //         'SELECT DISTINCT t.stock_id AS stock_id, s.name AS name, s.symbol AS symbol 
    //          FROM trades t
    //          JOIN stocks s ON t.stock_id = s.id
    //          WHERE t.user_id = ? 
    //          ORDER BY t.stock_id;'
    //     );
    //     $stmt->execute([$userId]);
    //     return $stmt->fetchAll();
    // }
}
