<?php
namespace App\Controllers\Api;

use PDO;
use RuntimeException;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Models\Account;
use App\Models\Split;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\User;
use App\Services\StockPriceService;
use App\Validations\StockValidator;
use App\Services\YfinanceService;
use App\Services\StockService;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard\Expression;

class AccountApiController extends BaseApiController {
    private PDO $pdo;
    private Account $accountModel;
    private Split $splitModel;
    private Stock $stockModel;
    private StockPrice $stockPriceModel;
    private User $userModel;
    private StockPriceService $stockPriceService;
    private YfinanceService $yfinanceService;
    private StockService $stockService;

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->accountModel = new Account($this->pdo);
        $this->splitModel = new Split($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->stockPriceModel = new StockPrice($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockPriceService = new StockPriceService($pdo);
        $this->yfinanceService = new YfinanceService($pdo);
        $this->stockService = new StockService($pdo);   
    }

    public function index() {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if (!$userId) throw new RuntimeException('ユーザーが見つからない');

            $account = $this->accountModel->getByUserId($userId); 

            $this->jsonResponse([
                'account' => $account,
                'success' => true,
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => [$e->getMessage()],
            ], 400);
        }
    }

}
