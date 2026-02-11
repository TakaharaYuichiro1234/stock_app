<?php
namespace App\Controllers\Api;

use PDO;
use App\Core\Auth;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\User;
use App\Services\StockPriceService;
use App\Validations\StockValidator;

require_once __DIR__ . '/../../Core/Auth.php';
require_once __DIR__ . '/../../Models/Stock.php';
require_once __DIR__ . '/../../Models/StockPrice.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Services/StockPriceService.php';
require_once __DIR__ . '/../../Validations/StockValidator.php';

class StockApiController
{
    private PDO $pdo;
    private Stock $stockModel;
    private StockPrice $stockPriceModel;
    private User $userModel;
    private StockPriceService $stockPriceService;

    public function __construct()
    {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
    }

    public function getForChart($stockId): void
    {
        $daily = $this->stockPriceModel->getForChart($stockId, 'daily');
        $weekly = $this->stockPriceModel->getForChart($stockId, 'weekly');
        $monthly = $this->stockPriceModel->getForChart($stockId, 'monthly');

        $prices = ['daily'=>$daily, 'weekly'=>$weekly, 'monthly'=>$monthly];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $prices,
            'errors' => [],
        ]);
        exit;
    }

    public function getUserStocks(): void
    {
        // ユーザーチェック
        if (!Auth::isLogged()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Forbidden'],
            ]);
            exit;
        }

        $uuid = $_SESSION['user']['uuid'];
        $userId = $this->userModel->getUserIdByUuid($uuid);
        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => ['invalid request'],
            ]);
            return;
        }

        $stocks = $this->stockModel->allWithLatestPriceByUserId($userId);

        header('Content-Type: application/json; charset=utf-8');
        // echo json_encode($stocks);
        echo json_encode([
            'success' => true,
            'data' => $stocks,
            'errors' => [],
        ]);
        exit;
    }


    public function getFiltered(): void
    {
        $input = $_GET['keywords'] ?? '';
        if (!is_string($input)) {
            http_response_code(400);
            // echo json_encode([]);
            echo json_encode([
                'success' => false,
                'errors' => ['invalid request'],
            ]);
            exit;
        }

        if (mb_strlen($input) > 100) {
            http_response_code(400);
            // echo json_encode([]);
            echo json_encode([
                'success' => false,
                'errors' => ['invalid request'],
            ]);
            exit;
        }

        if ($input === '') {
            $stocks = $this->stockModel->all();
            header('Content-Type: application/json; charset=utf-8');
            // echo json_encode($stocks);
            echo json_encode([
                'success' => true,
                'errors' => [],
                'data' => $stocks,
            ]);
            exit;
        }

        $keywords = preg_split('/\s+/', trim($input));
        
        $keywords = array_values(array_filter($keywords, fn($k) => $k !== ''));
        $keywords = array_slice($keywords, 0, 5);
        if (empty($keywords)) {
            // echo json_encode([]);
            echo json_encode([
                'success' => false,
                'errors' => ['invalid request'],
            ]);
            exit;
        }

        $stocks = $this->stockModel->filter($keywords);
        header('Content-Type: application/json; charset=utf-8');
        // echo json_encode($stocks);
        echo json_encode([
                'success' => true,
                'errors' => [],
                'data' => $stocks,
            ]);
        exit;
    }

    public function store()
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Forbidden'],
            ]);
            exit;
        }

        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Invalid CSRF token'],
            ]);
            exit;
        }

        // unset($_SESSION['csrf_token']);

        $data = [
            'name' => $_POST['name'] ?? '',
            'digit' => $_POST['digit'] ?? '',
        ];

        $errors = StockValidator::validate($data);

        if ($errors) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => $errors,
            ]);
            exit;
        }

        $symbol = $_POST['symbol'] ?? '';
        $shortName   = $_POST['short_name'] ?? '';
        $longName   = $_POST['long_name'] ?? '';
        $name   = $_POST['name'] ?? '';
        $digit = (int)$_POST['digit'];

        $stockId = null;
        $this->pdo->beginTransaction();
        try {
            $stockId = $this->stockModel->create($symbol, $name, $shortName, $longName, $digit);
            $this->stockPriceService->updateLatestPrices($stockId, $symbol);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'data' => null,
                'errors' => ['書き込みエラー'],
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => ['stockId'=>$stockId],
            'errors' => [],
        ]);
        exit;
    }

    public function updateStockPrices()
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Forbidden'],
            ]);
            exit;
        }

        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Invalid CSRF token'],
            ]);
            exit;
        }
        
        // unset($_SESSION['csrf_token']);

        $stockId = $_POST['stockId'] ?? '';

        $success = false;
        $errors = [];

        $stock = $this->stockModel->find($stockId);
        if ($stock) {
            $success = $this->stockPriceService ->updateLatestPrices($stock['id'], $stock['symbol'], false);
        }

        if (!$success) {
            http_response_code(400);
            $errors = ['データベースエラー'];
        }

        echo json_encode([
            'success' => $success,
            'errors' => $errors,
        ]);
        exit;
    }


    public function delete()
    {
        // 管理者チェック
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Forbidden'],
            ]);
            exit;
        }

        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'errors' => ['Invalid CSRF token'],
            ]);
            exit;
        }
        
        // unset($_SESSION['csrf_token']);

        $stockId = $_POST['stockId'] ?? '';

        $success = false;
        $errors = [];

        try {
            $this->stockModel->delete($stockId);   
            $success = true; 
        } catch (\Throwable $e) {
            http_response_code(400);
            $errors = ['データベースエラー'];
        }
        
        echo json_encode([
            'success' => $success,
            'errors' => $errors,
        ]);
        exit;
    }
}
