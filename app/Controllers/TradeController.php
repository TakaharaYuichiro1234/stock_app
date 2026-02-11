<?php
namespace App\Controllers;

use PDO;
use App\Models\Trade;
use App\Models\User;
use App\Core\Auth;
use App\Validations\StockValidator;
use App\Data\TradeData;

require_once __DIR__ . '/../Models/Trade.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Validations/StockValidator.php';
require_once __DIR__ . '/../Data/TradeData.php';

class TradeController {
    private PDO $pdo;
    private Trade $tradeModel;
    private User $userModel;

    public function __construct() {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->tradeModel = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    public function store()
    {
        // ユーザーチェック
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

        unset($_SESSION['csrf_token']);

        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if ($userId === null) {
            throw new RuntimeException('ユーザーが存在しません');
        }

        // ここでバリデーションチェック予定

        $data = new TradeData(
            $_POST['stock_id'] ?? '',
            empty($_POST['date']) ? null: $_POST['date'],
            (float)$_POST['price'],
            (int)$_POST['quantity'],
            (int)$_POST['type'],
            $_POST['content'] ?? '',
        );

        $tradeId = $this->tradeModel->create($userId, $data);

        $redirect = $_POST['redirect'] ?? BASE_PATH;

        $_SESSION['flash'] = '作成しました';
        header('Location: ' . $redirect);
    }
}
