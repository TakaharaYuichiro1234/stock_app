<?php
namespace App\Controllers;

use PDO;
use App\Core\Auth;
use App\Core\BaseWebController;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserStock;

class UserStockController extends BaseWebController {
    private PDO $pdo;
    private Stock $stockModel;
    private User $userModel;

    public function __construct() {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    public function index() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $stocks = $this->stockModel->allWithLatestPrice($userId);   //userId=nullのときは、DBに登録されているすべての銘柄を取得

        $this->view('user-stocks', [
            'isAdmin' => $isAdmin,
            'user'    => $user,
            'redirect' => $redirect,
            'stocks' => $stocks,
        ]);
    }
}
