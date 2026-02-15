<?php
namespace App\Controllers\Api;

use PDO;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Models\User;
use App\Models\UserStock;

class UserStockApiController extends BaseApiController {
    private PDO $pdo;
    private User $userModel;
    private UserStock $userStockModel;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->userModel = new User($this->pdo);
        $this->userStockModel = new UserStock($this->pdo);
    }
    
    public function update() {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);

            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません', 400);
            }

            $jsonstr = $_POST['users-stocks'] ?? '';
            $stockIds = json_decode($jsonstr, true);

            if (!$userId || !is_array($stockIds)) {
                throw new RuntimeException('invalid request', 400);
            }

            $this->pdo->beginTransaction();
            $this->userStockModel->replace($userId, $stockIds);
            $this->pdo->commit();

            $this->jsonResponse([
                'success' => true,
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
