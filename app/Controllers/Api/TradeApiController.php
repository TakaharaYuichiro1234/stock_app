<?php
namespace App\Controllers\Api;

use PDO;
use RuntimeException;
use App\Core\Auth;
use App\Core\BaseApiController;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\Account;
use App\Data\TradeData;
use App\Services\AssetService;

class TradeApiController extends BaseApiController {
    private PDO $pdo;
    private Trade $model;
    private User $userModel;
    private Stock $stockModel;
    private Account $accountModel;
    private AssetService $assetService;
    

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->model = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->accountModel = new Account($this->pdo);
        $this->assetService = new AssetService($this->pdo);
    }

    public function index(): void 
    {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません');
            }

            $trades = $this->model->getAllByUserId($userId);

            $this->jsonResponse([
                'success' => true,
                'trades' => $trades,
                'errors' => [],
            ]);
            
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['データベースエラー'],
            ], 400);
        }
    
    }

    public function store() 
    {
        // try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);

            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません', 400);
            }

            $jsonstr = $_POST['input_trades'] ?? '';
            $inputTrades = json_decode($jsonstr, true);

            if (!is_array($inputTrades)) {
                throw new RuntimeException('invalid request', 400);
            }

            $this->pdo->beginTransaction();

    
            foreach($inputTrades as $input) {

                if (!isset($input['symbol'], $input['date'], $input['price'], $input['quantity'], $input['type_name'], $input['account_name'])) {
                    continue;
                }

                $symbolWithSuffix = $input['symbol'] . ".T";
                $stockId = $this->stockModel->findBySymbol($symbolWithSuffix)['id'] ?? null;
                if (!$stockId) {
                    continue;
                }

                $accountId = $this->accountModel->findByContent($userId, $input['account_name'])['type'] ?? null;
                if (!$accountId) {
                    continue;
                }

                $type = 0;
                if ($input['type_name'] === '買付') {
                    $type = 1;
                } else if ($input['type_name'] === '売付') {
                    $type = 2;
                } else {
                    $type = 0;
                }
                
                $trade = new TradeData(
                    $stockId,
                    $input['date'],
                    (float)$input['price'],
                    (int)$input['quantity'],
                    $type,
                    $accountId,
                    ''
                );    
                $this->model->create($userId, $trade);
            }

            $this->pdo->commit();

            $this->jsonResponse([
                'success' => true,
                'errors' => [],
            ]);

        // } catch (\Throwable $e) {
        //     $this->pdo->rollBack();

        //     $this->jsonResponse([
        //         'success' => false,
        //         'errors'  => ['書き込みエラー'],
        //     ], 400);
        // }
    }

    public function getForChart($uuid, $stockId): void {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません');
            }

            $daily = $this->model->getForChart($userId, $stockId, 'daily');
            $weekly = $this->model->getForChart($userId, $stockId, 'weekly');
            $monthly = $this->model->getForChart($userId, $stockId, 'monthly');

            $trades = ['daily'=>$daily, 'weekly'=>$weekly, 'monthly'=>$monthly];

            $this->jsonResponse([
                'success' => true,
                'data' => $trades,
                'errors' => [],
            ]);
            
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['書き込みエラー'],
            ], 400);
        }
    }

    public function getDailyAssets(): void {
        // try {
            $ret = $this->assetService->dailyAssets();

            $this->jsonResponse([
                'success' => true,
                'data' => $ret,
                'errors' => [],
            ]);
            
        // } catch (\Throwable $e) {
        //     $this->pdo->rollBack();
        //     $this->jsonResponse([
        //         'success' => false,
        //         'errors'  => ['書き込みエラー'],
        //     ], 400);
        // }
    }
}
