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
use App\Services\DividendService;

class AssetApiController extends BaseApiController {
    private PDO $pdo;
    private Trade $model;
    private User $userModel;
    private Stock $stockModel;
    private Account $accountModel;
    private AssetService $assetService;
    private DividendService $dividendService;
    

    public function __construct() {
        require __DIR__ . '/../../../config/db.php';
        $this->pdo = $pdo;
        $this->model = new Trade($this->pdo);
        $this->userModel = new User($this->pdo);
        $this->stockModel = new Stock($this->pdo);
        $this->accountModel = new Account($this->pdo);
        $this->assetService = new AssetService($this->pdo);
        $this->dividendService = new DividendService($this->pdo);
    }

    public function getDailyAssets(): void {
        try {
            $ret = $this->assetService->dailyAssets();

            $this->jsonResponse([
                'success' => true,
                'data' => $ret,
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

    // public function getDailyAssetsIndividual(int $stockId, int $accountId): void {
    //     try {
    //         $ret = $this->assetService->dailyAssetsIndividual($stockId, $accountId);

    //         $this->jsonResponse([
    //             'success' => true,
    //             'data' => $ret,
    //             'errors' => [],
    //         ]);
            
    //     } catch (\Throwable $e) {
    //         $this->pdo->rollBack();
    //         $this->jsonResponse([
    //             'success' => false,
    //             'errors'  => ['書き込みエラー'],
    //         ], 400);
    //     }
    // }

    public function dailyAssetsByStockAccountPairs(): void {
        try {
            $uuid = $_SESSION['user']['uuid'];
            $userId = $this->userModel->getUserIdByUuid($uuid);
            if (!$userId) throw new RuntimeException('ユーザーが存在しません');

            $stockAccountPairs = array_map(fn($v) => json_decode($v, true), $_POST['pairs']);
            [$dailyAssetDetail, $latestAssetDetail] = $this->assetService->dailyAssetDetail($userId, $stockAccountPairs);

            $dailyAssetsTotal = $this->assetService->dailyAssetsTotal($dailyAssetDetail);

            $expectedDividends = $this->dividendService->getExpectedDividends($userId, $stockAccountPairs);

            $this->jsonResponse([
                'success' => true,
                'dailyAssetsTotal' => $dailyAssetsTotal,
                'latestAssetDetail' => $latestAssetDetail,
                'expectedDividends' => $expectedDividends,
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
}
