<?php
namespace App\Controllers\Api;

use PDO;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Models\Trade;
use App\Models\User;
use App\Models\UserStock;

class UserStockApiController extends BaseApiController {
    private PDO $pdo;
    private Trade $model;
    private User $userModel;
    private UserStock $userStockModel;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->model = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->userStockModel = new UserStock($this->pdo);
    }
    
    public function update() {
        try {
            // $this->requireLogin();
            // $this->verifyCsrf();

            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);

            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません', 400);
            }

            $jsonstr = $_POST['users-stocks'] ?? '';
            $stockIds = json_decode($jsonstr, true);

            if (!$userId || !is_array($stockIds)) {
                // http_response_code(400);
                // $_SESSION['errors'] = ['invalid request'];
                // return;
                throw new RuntimeException('invalid request', 400);
            }

            // try {
                $this->pdo->beginTransaction();
                $this->userStockModel->replace($userId, $stockIds);
                $this->pdo->commit();
                // $_SESSION['flash'] = '登録しました';

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

                // http_response_code(400);
                // $_SESSION['errors'] = ['登録に失敗しました'];
            }

            // header('Location: '. BASE_PATH. '/user-stocks');

        // } catch (\Exception $e) {
        //     http_response_code($e->getCode() ?: 500);
        //     exit($e->getMessage());
        // } finally {
        //     unset($_SESSION['csrf_token']);
        // }
    }

}
