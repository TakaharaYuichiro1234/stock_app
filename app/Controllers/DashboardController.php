<?php

namespace App\Controllers;

use PDO;
use App\Core\Auth;
use App\Core\BaseWebController;
use App\Models\Account;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\Trade;
use App\Models\User;

use App\Services\DashboardService;

class DashboardController extends BaseWebController
{
    private PDO $pdo;
    private Stock $stockModel;
    private StockPrice $stockPriceModel;
    private Trade $tradeModel;
    private Account $accountModel;
    private User $userModel;
    private DashboardService $dashBoardService;

    public function __construct()
    {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->tradeModel = new Trade($this->pdo);
        $this->accountModel = new Account($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->dashBoardService = new DashboardService($this->pdo);
    }

    public function index()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        // $uuid = $_SESSION['user']['uuid'];
        // $userId = $this->userModel->getUserIdByUuid($uuid);

        $tradeSummary = null;
        $acounts = [];
        if (Auth::isLogged()) {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId) {
                $tradeSummary = $this->dashBoardService->calTradeSummary();
                $acounts = $this->accountModel->getByUserId($userId);
            }
        }

        // $stocks = $this->stockModel->all();
        $stocks = $this->stockModel->allWithLatestPrice(null);

        $this->view('dashboard', [
            'isAdmin' => $isAdmin,
            'user'    => $user,
            'tradeSummary' => $tradeSummary,
            'stocks' => $stocks,
            'accounts' => $acounts
        ]);
    }

}
