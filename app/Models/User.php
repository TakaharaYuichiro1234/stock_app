<?php
namespace App\Models;
use PDO;

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this -> pdo = $pdo;
    }

    public function getUserIdByUuid($uuid): ?int {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM users WHERE uuid = ?'
        );
        $stmt->execute([$uuid]);
        $user_id = $stmt->fetchColumn();
        return $user_id !== false ? (int)$user_id : null;
    }

}