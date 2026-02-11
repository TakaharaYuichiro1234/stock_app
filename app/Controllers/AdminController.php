<?php
namespace App\Controllers;

use PDO;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\User;
use App\Services\StockPriceService;
use App\Services\StockService;
use App\Core\Auth;
use App\Validations\StockValidator;
use App\Models\Trade;
use App\Data\TradeData;

require_once __DIR__ . '/../Models/Stock.php';
require_once __DIR__ . '/../Models/StockPrice.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Validations/StockValidator.php';
require_once __DIR__ . '/../Services/StockPriceService.php';
require_once __DIR__ . '/../Services/StockService.php';
require_once __DIR__ . '/../Models/Trade.php';
require_once __DIR__ . '/../Data/TradeData.php';

class AdminController {
    private PDO $pdo;
    private Stock $stockModel;
    private User $userModel;
    private StockPriceService $stockPriceService;
    private StockService $stockService;
    

    public function __construct() {
        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->stockService = new StockService($pdo);
    }

    public function index()
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Forbidden');
        }

        $isAdmin = Auth::isAdmin();
        $user = $_SESSION['user'];
        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $symbol = $_GET['symbol'] ?? null;

        $errors = [];
        [$error, $data] = $this->stockService->initCreate($symbol);
        if ($error) $errors[] = $error;

        if ($symbol !== '' && $this->stockService->isSymbolRegistered($symbol)) {
            $errors[] = 'この銘柄はすでに登録されています';
        }
        require __DIR__ . '/../Views/admins/index.php';
    }

    public function updateStockPricesAll()
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

        // 銘柄一覧取得
        $stmt = $this->pdo->query("SELECT id, symbol FROM stocks");
        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $successCount = 0;
        $errorCount = 0;

        foreach ($stocks as $stock) {
            $ok = $this->stockPriceService ->updateLatestPrices($stock['id'], $stock['symbol']);
            $ok ? $successCount++ : $errorCount++;
        }

        // フラッシュメッセージ
        $_SESSION['flash'] = "株価更新完了：成功 {$successCount} 件 / 失敗 {$errorCount} 件";

        // 一覧画面へ戻る
        header('Location: '. BASE_PATH. '/admins');
        exit;
    }
}
