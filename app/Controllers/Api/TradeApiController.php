<?php
namespace App\Controllers\Api;

require_once __DIR__ . '/../../Core/Auth.php';
require_once __DIR__ . '/../../Models/Trade.php';
require_once __DIR__ . '/../../Models/User.php';

use PDO;
use App\Core\Auth;
use App\Models\Trade;
use App\Models\User;

class TradeApiController
{
    private PDO $pdo;
    private Trade $model;
    private User $userModel;

    public function __construct()
    {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->model = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    public function getForChart($uuid, $stockId): void
    {
        // ユーザーチェック
        if (!Auth::isLogged()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Forbidden'],
            ]);
            exit;
        }

        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if ($userId === null) {
            throw new RuntimeException('ユーザーが存在しません');
        }

        $daily = $this->model->getForChart($userId, $stockId, 'daily');
        $weekly = $this->model->getForChart($userId, $stockId, 'weekly');
        $monthly = $this->model->getForChart($userId, $stockId, 'monthly');

        $trades = ['daily'=>$daily, 'weekly'=>$weekly, 'monthly'=>$monthly];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $trades,
            'errors' => [],
        ]);
        exit;
    }
}
