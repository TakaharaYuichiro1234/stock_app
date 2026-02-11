<?php
namespace App\Models;
use PDO;

class UserStock {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this -> pdo = $pdo;
    }

    public function replace(int $userId, array $stockIds) {
        $stmtDelete = $this->pdo->prepare(
            'DELETE FROM user_stocks WHERE user_id = ?'
        );
        $stmtDelete->execute([$userId]);

        $stmtInsert = $this->pdo->prepare(
            'INSERT INTO user_stocks (user_id, stock_id, sort_order) VALUES (?,?,?)'
        );

        $sortOrder = 10;
        foreach ($stockIds as $stockId) {
            $stmtInsert->execute([$userId, $stockId, $sortOrder]);
            $sortOrder+=10;
        }
    }
}