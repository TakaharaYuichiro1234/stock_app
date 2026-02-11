<?php
namespace App\Controllers;

use PDO;
use App\Core\Auth;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserStock;

require_once __DIR__ . '/../Models/Stock.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/UserStock.php';

class UserStockController {
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
        $isSort = $_GET['is_sort'] === 'true';
        
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $stocks = null;
        if ($isSort && $userId) {
            $stocks = $this->stockModel->allWithLatestPriceByUserId($userId);
        } else {
            $stocks = $this->stockModel->allWithLatestPrice();
        }

        require __DIR__ . '/../Views/userStocks/index.php';
    }

    public function update()
    {
        // ログインチェック
        if (!Auth::isLogged()) {
            http_response_code(403);
            exit('Forbidden');
        }

        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            exit('Invalid CSRF token');
        }

        // var_dump("test");
        // exit;

        unset($_SESSION['csrf_token']);

        // $data = [
        //     'users-stocks' => $_POST['users-stocks'] ?? '',
        // ];

        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $jsonstr = $_POST['users-stocks'] ?? '';
        $stockIds = json_decode($jsonstr, true);


        // $errors = StockValidator::validate($data);

        // if ($errors) {
        //     $_SESSION['errors'] = $errors;
        //     $_SESSION['old'] = $data;
        //     header('Location: '. BASE_PATH. '/stocks/create');
        //     exit;
        // }

        // $symbol = $_POST['symbol'] ?? '';
        // $shortName   = $_POST['short_name'] ?? '';
        // $longName   = $_POST['long_name'] ?? '';
        // $name   = $_POST['name'] ?? '';
        // $digit = (int)$_POST['digit'];


        if (!$userId || !is_array($stockIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid request']);
            return;
        }


        try {
            $this->pdo->beginTransaction();
            $this->userStockModel->replace($userId, $stockIds);
            $this->pdo->commit();
            $_SESSION['flash'] = '登録しました';

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            // echo json_encode(['error' => 'failed to update order']);
            $_SESSION['flash'] = '登録に失敗しました';
        }
        header('Location: '. BASE_PATH. '/user-stocks');
        exit;
    }
}
