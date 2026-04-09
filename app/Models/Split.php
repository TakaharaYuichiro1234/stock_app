<?php

namespace App\Models;

use PDO;

class Split
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByStockId(int $stockId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM splits WHERE stock_id = ?'
        );

        $stmt->execute([$stockId]);
        return $stmt->fetchAll();
    }


    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO splits (stock_id, date, numerator, denominator)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                numerator = VALUES(numerator),
                denominator = VALUES(denominator)'
        );

        $stmt->execute([
            $data['stock_id'],
            $data['date'],
            $data['numerator'],
            $data['denominator']
        ]);

        return (int)$this->pdo->lastInsertId();
    }


}
