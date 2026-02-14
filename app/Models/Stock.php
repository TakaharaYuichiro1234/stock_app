<?php
namespace App\Models;
use PDO;

class Stock {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this -> pdo = $pdo;
    }

    public function all(): array {
        $sql = 'SELECT id, symbol, name, short_name, long_name FROM stocks ORDER BY symbol';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filter($keywords): array {
        $sql = 'SELECT id, symbol, name, short_name, long_name FROM stocks';

        $where = [];
        $params = [];

        foreach ($keywords as $i => $keyword) {
            $where[] = "(
                symbol     LIKE :kw{$i}
                OR name       LIKE :kw{$i}
                OR short_name LIKE :kw{$i}
                OR long_name  LIKE :kw{$i}
            )";

            $params["kw{$i}"] = '%' . $keyword . '%';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY symbol';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $stocks;
    }

    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM stocks WHERE id = ?'
        );

        $stmt->execute([$id]);
        $stock = $stmt->fetch();

        return $stock ?: null;
    }

    public function create(array $data): int{
        $stmt = $this->pdo->prepare(
            'INSERT INTO stocks (symbol, name, short_name, long_name, digit) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$data['symbol'], $data['name'], $data['shortName'], $data['longName'], $data['digit']]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->pdo->prepare(
            'UPDATE stocks SET name = ?, digit = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$data['name'], (int)$data['digit'], $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->pdo->prepare(
            'DELETE FROM stocks WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function allWithLatestPrice(?int $userId = null): array {
        $params = [];
        $joinUserStocks = '';
        $where = '';
        $orderBy = 'ORDER BY s.symbol';

        if ($userId !== null) {
            $joinUserStocks = 'JOIN user_stocks us ON s.id = us.stock_id';
            $where = 'WHERE us.user_id = ?';
            $orderBy = 'ORDER BY us.sort_order';
            $params[] = $userId;
        }

        $sql = "
            SELECT
                s.id,
                s.symbol,
                s.name,
                s.digit,
                " . ($userId !== null ? "us.is_visible," : "") . "

                sp_latest.date  AS latest_date,
                sp_latest.close AS latest_close,

                sp_prev.date  AS prev_date,
                sp_prev.close AS prev_close

            FROM stocks s
            $joinUserStocks

            LEFT JOIN stock_prices sp_latest
                ON sp_latest.stock_id = s.id
                AND sp_latest.date = (
                    SELECT MAX(date)
                    FROM stock_prices
                    WHERE stock_id = s.id
                )

            LEFT JOIN stock_prices sp_prev
                ON sp_prev.stock_id = s.id
                AND sp_prev.date = (
                    SELECT MAX(date)
                    FROM stock_prices
                    WHERE stock_id = s.id
                    AND date < (
                        SELECT MAX(date)
                        FROM stock_prices
                        WHERE stock_id = s.id
                    )
                )

            $where
            $orderBy
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($stock) {
            $latest = $stock['latest_close'] ?? null;
            $prev   = $stock['prev_close'] ?? null;

            if ($latest !== null && $prev !== null && $prev != 0) {
                $stock['diff'] = $latest - $prev;
                $stock['percent_diff'] = ($latest - $prev) / $prev * 100;
            } else {
                $stock['diff'] = null;
                $stock['percent_diff'] = null;
            }

            return $stock;
        }, $stocks);
    }


    public function findWithInfo(int $id): array {
        $stmt = $this->pdo->prepare(
            'SELECT 
                s.id,
                s.symbol,
                s.name,
                sp.date AS latest_date,
                sp.close AS latest_close
            FROM stocks s
            LEFT JOIN stock_prices sp
                ON sp.stock_id = s.id
            WHERE s.id = ?'
        );

        $stmt->execute([$id]);
        $stock = $stmt->fetch();

        return $stock ?: null;
    }

    public function existsBySymbol(string $symbol): bool {
        $sql = "SELECT 1 FROM stocks WHERE symbol = :symbol LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':symbol' => $symbol]);
        return (bool) $stmt->fetchColumn();
    }
}