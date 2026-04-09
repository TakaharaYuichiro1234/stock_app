<?php

namespace App\Models;

use PDO;

class Account
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE user_id = ?'
        );

        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }


    public function findByContent(int $userId, string $content): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE user_id = ? AND content = ?'
        );

        $stmt->execute([$userId, $content]);
        $account = $stmt->fetch();

        return $account ?: null;
    }

}
