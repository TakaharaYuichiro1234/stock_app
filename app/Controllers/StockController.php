<?php
namespace App\Controllers;

use PDO;
use App\Core\Auth;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\Trade;
use App\Models\User;
use App\Services\StockPriceService;
use App\Services\StockService;
use App\Validations\StockValidator;
use App\Data\TradeData;

require_once __DIR__ . '/../Models/Stock.php';
require_once __DIR__ . '/../Models/StockPrice.php';
require_once __DIR__ . '/../Models/Trade.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Validations/StockValidator.php';
require_once __DIR__ . '/../Services/StockPriceService.php';
require_once __DIR__ . '/../Services/StockService.php';
require_once __DIR__ . '/../Data/TradeData.php';

class StockController {
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

    public function index()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $isSort = $_GET['is_sort'] === 'true';
        
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $stocks = null;
        if ($userId) {
            $stocks = $this->stockModel->allWithLatestPriceByUserId($userId);
        } else {
            $stocks = $this->stockModel->allWithLatestPrice();
        }

        require __DIR__ . '/../Views/stocks/index.php';
    }

    public function store()
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
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

        $data = [
            'name' => $_POST['name'] ?? '',
            'digit' => $_POST['digit'] ?? '',
        ];

        $errors = StockValidator::validate($data);

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            header('Location: '. BASE_PATH. '/stocks/create');
            exit;
        }

        $symbol = $_POST['symbol'] ?? '';
        $shortName   = $_POST['short_name'] ?? '';
        $longName   = $_POST['long_name'] ?? '';
        $name   = $_POST['name'] ?? '';
        $digit = (int)$_POST['digit'];

        $this->pdo->beginTransaction();
        try {
            $stockId = $this->stockModel->create($symbol, $name, $shortName, $longName, $digit);
            $this->stockPriceService->updateLatestPrices($stockId, $symbol);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $_SESSION['flash'] = '銘柄情報を作成しました';
        header('Location: '. BASE_PATH. '/admins');
        exit;
    }

    public function edit($id) {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Forbidden');
        }

        $stock = $this->stockModel->find($id);
        if(!$stock) {
            http_response_code(404);
            exit('銘柄情報が見つかりません');
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        require __DIR__ . '/../Views/stocks/edit.php';
    }

    public function update($id) {
        // 管理者チェック
        if (!Auth::isAdmin()) {
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

        $data = [
            'name' => $_POST['name'] ?? '',
            'digit' => $_POST['digit'] ?? '',
        ];

        $errors = StockValidator::validate($data);

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            header('Location: '. BASE_PATH. '/stocks/show/' . $id);
            exit;
        }

        $this->stockModel->update($id, $data['name']);
        $_SESSION['flash'] = '銘柄情報を更新しました';
        header('Location: '. BASE_PATH. '/stocks/show/' . $id);
        exit;
    }

    public function delete($id) {
        // 管理者チェック
        if (!Auth::isAdmin()) {
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

        $this->stockModel->delete($id);
        $_SESSION['flash'] = '銘柄情報を削除しました';
        header('Location: '. BASE_PATH);
        exit;
    }

    public function show($id) {
        $redirect = $_GET['redirect'];

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];

        $stock = $this->stockModel->find($id);
        $stockPrices = $this->stockPriceModel->filterByStockId($id);

        $latest = $stockPrices[count($stockPrices) - 1];  // 最新日
        $previous = $stockPrices[count($stockPrices) - 2];    // 最新の1日前（＝1つ前）
        $diff = $latest['close'] - $previous['close'];
        $percent_diff = ($latest['close'] - $previous['close'])/ $previous['close'] * 100;

        $trades = null;
        $tradeAmounts = null;
        if (Auth::isLogged()) {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId) {
                $trades = $this->tradeModel->getByUserIdAndStockId($userId, $id);
                $tradeAmounts = $this->tradeModel->getAmounts($userId, $id);
            }   
        }
        require __DIR__ . '/../Views/stocks/show.php';
    }

    public function updateStockPrices($stockId)
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Forbidden');
        }

        // CSRFチェック
        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            exit('Invalid CSRF token');
        }

        unset($_SESSION['csrf_token']);

        $stock = $this->stockModel->find($stockId);
        if ($stock) {
            $ok = $this->stockPriceService ->updateLatestPrices($stock['id'], $stock['symbol'], false);
        }

        header('Location: '. BASE_PATH. '/stocks/show/' . $stock['id']);
        exit;
    }
}
