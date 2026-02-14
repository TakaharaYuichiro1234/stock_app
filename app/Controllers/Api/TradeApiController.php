<?php
namespace App\Controllers\Api;

use PDO;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Models\Trade;
use App\Models\User;

class TradeApiController extends BaseApiController {
    private PDO $pdo;
    private Trade $model;
    private User $userModel;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->model = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    public function getForChart($uuid, $stockId): void {
        if (!$this->requireLogin()) return;
                
        try {
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
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['書き込みエラー'],
            ], 400);
        }
    }
}
