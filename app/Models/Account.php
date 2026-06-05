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

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (user_id, content) VALUES (?, ?)'
            // 'INSERT INTO accounts (user_id, type, content) VALUES (?, ?, ?)'
        );

        $stmt->execute([
            $data['user_id'],
            // $data['type'],
            $data['content'],
        ]);

        return (int)$this->pdo->lastInsertId();
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
