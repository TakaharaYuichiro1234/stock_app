<?php
namespace App\Controllers;

use PDO;
use App\Models\Trade;
use App\Models\User;
use App\Core\Auth;
use App\Validations\TradeValidator;
use App\Data\TradeData;

require_once __DIR__ . '/../Models/Trade.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Validations/TradeValidator.php';
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

        $redirect = $_POST['redirect'] ?? BASE_PATH;

        $data = new TradeData(
            $_POST['stock_id'] ?? '',
            empty($_POST['date']) ? null: $_POST['date'],
            (float)$_POST['price'],
            (int)$_POST['quantity'],
            (int)$_POST['type'],
            $_POST['content'] ?? '',
        );

        $errors = TradeValidator::validate($data);

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            header('Location: '. $redirect);
            exit;
        }

        $tradeId = $this->tradeModel->create($userId, $data);
        $_SESSION['flash'] = '取引情報を登録しました';
        header('Location: ' . $redirect);
    }

    public function update() {
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

        $id = $_POST['trade_id'];
        $data = new TradeData(
            $_POST['stock_id'] ?? '',
            empty($_POST['date']) ? null: $_POST['date'],
            (float)$_POST['price'],
            (int)$_POST['quantity'],
            (int)$_POST['type'],
            $_POST['content'] ?? '',
        );

        $redirect = $_POST['redirect'] ?? BASE_PATH;
        $errors = TradeValidator::validate($data);
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            header('Location: '. $redirect);
            exit;
        }

        $tradeId = $this->tradeModel->update($id, $data);
        $_SESSION['flash'] = '取引情報を更新しました';
        header('Location: ' . $redirect);
    }

    public function delete() {
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

        $id = $_POST['trade_id'];

        $tradeId = $this->tradeModel->delete($id);

        $redirect = $_POST['redirect'] ?? BASE_PATH;

        $_SESSION['flash'] = '取引情報を更新しました';
        header('Location: ' . $redirect);
    }
}
