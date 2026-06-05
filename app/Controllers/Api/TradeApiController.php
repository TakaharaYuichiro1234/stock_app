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
    private Trade $tradeModel;
    private User $userModel;
    private Stock $stockModel;
    private Account $accountModel;
    private AssetService $assetService;
    

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->tradeModel = new Trade($this->pdo);
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

            $trades = $this->tradeModel->getAllByUserId($userId);

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
        try {
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

                if (!isset($input['symbol'], $input['date'], $input['price'], $input['quantity'], $input['type'], $input['account_name'], $input['subtotal'])) {
                    continue;
                }

                // 証券コードをチェック
                $symbol = strtoupper(trim($input['symbol']));
                $stockId = $this->stockModel->findBySymbol($symbol)['id'] ?? null;
                if (!$stockId) {
                    $data = [
                        'name' => '仮登録',
                        'digit' => 0,
                        'symbol' => $symbol,
                        'tentative' => 1,
                    ];
                    $stockId = $this->stockModel->create($data);
                }

                // 口座をチェック
                $accountId = $this->accountModel->findByContent($userId, $input['account_name'])['id'] ?? null;
                if (!$accountId) {
                    $accountData = [
                        'user_id' => $userId,
                        'content' => $input['account_name'],
                    ];
                    $accountId = $this->accountModel->create($accountData);
                }

                // 取引日をチェック
                $date = date_create($input['date']);
                if (!$date) {
                    continue;
                }

                // 取引種別をチェック
                // $type = $input['type'];
                // if ($input['type_name'] === '買付') {
                //     $type = 1;
                // } else if ($input['type_name'] === '売付') {
                //     $type = 2;
                // } else if ($input['type_name'] === '配当') {
                //     $type = 3;
                // } else {
                //     $type = 0;
                // }

                // 
                $content = $input['content'] ?? '';
                if (mb_strlen($content) > 1000) {
                    $content = mb_substr($content, 0, 1000);
                }
                
                $trade = new TradeData(
                    $stockId,
                    $date ->format('Y-m-d'),
                    (float)$input['price'],
                    (int)$input['quantity'],
                    (int)$input['type'],
                    $accountId,
                    $content,
                    (float)$input['subtotal'],
                );    
                $this->tradeModel->create($userId, $trade);
            }

            $this->pdo->commit();

            $this->jsonResponse([
                'success' => true,
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

    public function getForChart($uuid, $stockId): void {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません');
            }

            $daily = $this->tradeModel->getForChart($userId, $stockId, 'daily');
            $weekly = $this->tradeModel->getForChart($userId, $stockId, 'weekly');
            $monthly = $this->tradeModel->getForChart($userId, $stockId, 'monthly');

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

    public function getStockIdsBelongToUser(): void
    {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if ($userId === null) {
                throw new RuntimeException('ユーザーが存在しません');
            }

            $stockIds = $this->tradeModel->getStockIdsByUserId($userId);

            $this->jsonResponse([
                'success' => true,
                'data' => $stockIds,
                'errors' => [],
            ]);
            
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => ['エラー'],
            ], 400);
        }
    }
}
