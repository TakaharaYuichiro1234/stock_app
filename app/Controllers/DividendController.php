<?php

namespace App\Controllers;

use PDO;
use RuntimeException;
use App\Core\Auth;
use App\Core\BaseWebController;
use App\Models\Account;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\Dividend;
use App\Validations\TradeValidator;
use App\Data\TradeData;

class DividendController extends BaseWebController
{
    private PDO $pdo;
    private Account $accountModel;
    private Stock $stockModel;
    private Trade $tradeModel;
    private User $userModel;
    private Dividend $dividendModel;

    public function __construct()
    {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->accountModel = new Account($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->tradeModel = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->dividendModel = new Dividend($this->pdo);
    }

    public function index()
    {
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        try {
            $stocks = $this->stockModel->all();  
            $dividends = $this->dividendModel->getAll();
            
            $this->view('dividend', [
                'isAdmin' => $isAdmin,
                'user'    => $user,
                'stocks' => $stocks,
                'dividends' => $dividends,
            ]);
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            exit($e->getMessage());
        }
    }



    // public function store()
    // {
    //     try {
    //         $uuid = $_SESSION['user']['uuid'];
    //         $userId = $this->userModel->getUserIdByUuid($uuid);
    //         if ($userId === null) {
    //             throw new RuntimeException('ユーザーが存在しません', 400);
    //         }

    //         $redirect = $_POST['redirect'] ?? BASE_PATH;

    //         $data = new TradeData(
    //             $_POST['stock_id'] ?? '',
    //             empty($_POST['date']) ? null : $_POST['date'],
    //             (float)$_POST['price'],
    //             (int)$_POST['quantity'],
    //             (int)$_POST['type'],
    //             0,
    //             $_POST['content'] ?? '',
    //             (float)$_POST['subtotal'],
    //         );

    //         $errors = TradeValidator::validate($data);

    //         if ($errors) {
    //             $_SESSION['errors'] = $errors;
    //             $_SESSION['old'] = $data;
    //             header('Location: ' . $redirect);
    //             exit;
    //         }

    //         $tradeId = $this->tradeModel->create($userId, $data);
    //         $_SESSION['flash'] = '取引情報を登録しました';
    //         header('Location: ' . $redirect);
    //     } catch (\Exception $e) {
    //         http_response_code($e->getCode() ?: 500);
    //         exit($e->getMessage());
    //     }
    // }

    // public function update()
    // {
    //     try {
    //         $uuid = $_SESSION['user']['uuid'];
    //         $userId = $this->userModel->getUserIdByUuid($uuid);
    //         if ($userId === null) {
    //             throw new RuntimeException('ユーザーが存在しません', 400);
    //         }

    //         $id = $_POST['trade_id'];
    //         $data = new TradeData(
    //             $_POST['stock_id'] ?? '',
    //             empty($_POST['date']) ? null : $_POST['date'],
    //             (float)$_POST['price'],
    //             (int)$_POST['quantity'],
    //             (int)$_POST['type'],
    //             0,
    //             $_POST['content'] ?? '',
    //             (float)$_POST['subtotal'],
    //         );

    //         $redirect = $_POST['redirect'] ?? BASE_PATH;
    //         $errors = TradeValidator::validate($data);
    //         if ($errors) {
    //             $_SESSION['errors'] = $errors;
    //             $_SESSION['old'] = $data;
    //             header('Location: ' . $redirect);
    //             exit;
    //         }

    //         $tradeId = $this->tradeModel->update($id, $data);
    //         $_SESSION['flash'] = '取引情報を更新しました';
    //         header('Location: ' . $redirect);
    //     } catch (\Exception $e) {
    //         http_response_code($e->getCode() ?: 500);
    //         exit($e->getMessage());
    //     }
    // }

    // public function delete()
    // {
    //     try {
    //         $uuid  = $_SESSION['user']['uuid'];
    //         $userId = $this->userModel->getUserIdByUuid($uuid);
    //         if ($userId === null) {
    //             throw new RuntimeException('ユーザーが存在しません');
    //         }

    //         $id = $_POST['trade_id'];

    //         $tradeId = $this->tradeModel->delete($id);

    //         $redirect = $_POST['redirect'] ?? BASE_PATH;

    //         $_SESSION['flash'] = '取引情報を更新しました';
    //         header('Location: ' . $redirect);
    //     } catch (\Exception $e) {
    //         http_response_code($e->getCode() ?: 500);
    //         exit($e->getMessage());
    //     }
    // }
}
