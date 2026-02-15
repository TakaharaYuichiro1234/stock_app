<?php
namespace App\Controllers;

use PDO;
use App\Core\Auth;
use App\Core\BaseWebController;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\Trade;
use App\Models\User;
use App\Services\StockPriceService;
use App\Services\StockService;
use App\Validations\StockValidator;
use App\Data\TradeData;

class StockController extends BaseWebController {
    private PDO $pdo;
    private Stock $stockModel;
    private StockPriceService $stockPriceService;
    private StockService $stockService;
    private Trade $tradeModel;
    private User $userModel;

    public function __construct() {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->stockService = new StockService($pdo);
        $this->tradeModel = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
    }

    public function index() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $isSort = $_GET['is_sort'] === 'true';
        
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $stocks = null;
        $stocks = $this->stockModel->allWithLatestPrice($userId);   //userId=nullのときは、DBに登録されているすべての銘柄を取得

        // require __DIR__ . '/../Views/index.php';
        $this->view('index', [
                'isAdmin' => $isAdmin,
                'user'    => $user,
                'stocks' => $stocks,
            ]);
    }

    public function showDetail($id) {
        $redirect = $_GET['redirect'];
        $user = $_SESSION['user'];
        $isAdmin = Auth::isAdmin();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $stock = $this->stockModel->find($id);
        $stockPrices = $this->stockPriceModel->filterByStockId($id);

        $chartPrices = [
            'daily' => $this->stockPriceModel->getForChart($id, 'daily'),
            'weekly' => $this->stockPriceModel->getForChart($id, 'weekly'),
            'monthly' => $this->stockPriceModel->getForChart($id, 'monthly'),
        ];

        $trades = null;
        $tradeAmounts = null;
        $chartTrades = [];
        if (Auth::isLogged()) {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId) {
                $trades = $this->tradeModel->getByUserIdAndStockId($userId, $id);
                $tradeAmounts = $this->tradeModel->getAmounts($userId, $id);

                $chartTrades = [
                    'daily' => $this->tradeModel->getForChart($userId, $id, 'daily'),
                    'weekly' =>  $this->tradeModel->getForChart($userId, $id, 'weekly'),
                    'monthly'=> $this->tradeModel->getForChart($userId, $id, 'monthly'),
                ];
            }   
        }
        // require __DIR__ . '/../Views/show-detail.php';
        $this->view('show-detail', [
                'isAdmin' => $isAdmin,
                'user'    => $user,
                'redirect' => $redirect,
                'stock' => $stock,
                'stockPrices' => $stockPrices,
                'chartPrices' => $chartPrices,
                'trades' => $trades,
                'tradeAmounts' => $tradeAmounts,
                'chartTrades' => $chartTrades,
        ]);

    }
}
