<?php
namespace App\Controllers;

use PDO;
use App\Core\Auth;
use App\Core\BaseWebController;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserStock;

// require_once __DIR__ . '/../Core/BaseWebController.php';
// require_once __DIR__ . '/../Models/Stock.php';
// require_once __DIR__ . '/../Models/User.php';
// require_once __DIR__ . '/../Models/UserStock.php';

class UserStockController extends BaseWebController {
    private PDO $pdo;
    private Stock $stockModel;
    private User $userModel;
    private UserStock $userStockModel;

    public function __construct() {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->userStockModel = new UserStock($this->pdo);
    }

    public function index()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // $isSort = $_GET['is_sort'] === 'true';
        
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $stocks = null;
        // if ($isSort && $userId) {
        if ($userId) {
            $stocks = $this->stockModel->allWithLatestPriceByUserId($userId);
        } else {
            $stocks = $this->stockModel->allWithLatestPrice();
        }

        require __DIR__ . '/../Views/user-stocks.php';
    }

    public function update()
    {
        try {
            $this->requireLogin();
            $this->verifyCsrf();

            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);

            $jsonstr = $_POST['users-stocks'] ?? '';
            $stockIds = json_decode($jsonstr, true);

            if (!$userId || !is_array($stockIds)) {
                http_response_code(400);
                $_SESSION['errors'] = ['invalid request'];
                return;
            }

            try {
                $this->pdo->beginTransaction();
                $this->userStockModel->replace($userId, $stockIds);
                $this->pdo->commit();
                $_SESSION['flash'] = '登録しました';

            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                http_response_code(400);
                $_SESSION['errors'] = ['登録に失敗しました'];
            }

            header('Location: '. BASE_PATH. '/user-stocks');

        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            exit($e->getMessage());
        } finally {
            unset($_SESSION['csrf_token']);
        }
    }
}
