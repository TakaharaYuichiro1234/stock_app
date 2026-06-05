<?php

session_name('STOCK_APP_SESSID');
session_set_cookie_params([
    'secure' => false,      
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
define('BASE_PATH', '/stock_app');
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\UserMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Controllers\AdminController;
use App\Controllers\StockController;
use App\Controllers\TradeController;
use App\Controllers\AuthController;
use App\Controllers\DividendController;
use App\Controllers\ManualInputController;

use App\Controllers\DashboardController;
use App\Controllers\UserStockController;
use App\Controllers\Api\AdminApiController;
use App\Controllers\Api\StockApiController;
use App\Controllers\Api\TradeApiController;
use App\Controllers\Api\UserStockApiController;
use App\Controllers\Api\SplitApiController;
use App\Controllers\Api\AssetApiController;
use App\Controllers\Api\AccountApiController;
use App\Controllers\Api\DividendApiController;

$router = new Router();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, BASE_PATH) === 0) {
    $uri = substr($uri, strlen(BASE_PATH));
}
if ($uri === '') {
    $uri = '/';
}


$method = $_SERVER['REQUEST_METHOD'];

$router->add('GET', '/', DashboardController::class, 'index');
$router->add('GET', '/stocks', StockController::class, 'index');
$router->add('GET', '/stocks/show-detail/{id}', StockController::class, 'showDetail');
$router->add('GET', '/user-stocks', UserStockController::class, 'index');
$router->add('GET', '/show_login', AuthController::class, 'showLogin');
$router->add('POST', '/login', AuthController::class, 'login');
$router->add('POST', '/logout', AuthController::class, 'logout');
$router->add('GET', '/show_register', AuthController::class, 'showRegister');
$router->add('POST', '/register', AuthController::class, 'register');

$router->add('GET', '/trades', TradeController::class, 'index', [UserMiddleware::class]);
$router->add('GET', '/trades/index', TradeController::class, 'index', [UserMiddleware::class]);
$router->add('GET', '/trades/create', TradeController::class, 'create', [UserMiddleware::class]);
$router->add('POST', '/trades/store', TradeController::class, 'store', [UserMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/trades/update', TradeController::class, 'update', [UserMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/trades/delete', TradeController::class, 'delete', [UserMiddleware::class, CsrfMiddleware::class]);

$router->add('GET', '/admins', AdminController::class, 'index', [AdminMiddleware::class]);
$router->add('POST', '/admins/update_stock_prices_all', AdminController::class, 'updateStockPricesAll', [AdminMiddleware::class, CsrfMiddleware::class]);

$router->add('GET', '/dividends', DividendController::class, 'index', [AdminMiddleware::class]);


$router->add('GET', '/manual-inputs', ManualInputController::class, 'index', [AdminMiddleware::class]);
$router->add('POST', '/manual-inputs/update-stock-prices', ManualInputController::class, 'updateStockPrices', [AdminMiddleware::class, CsrfMiddleware::class]);


$router->add('GET', '/api/stocks/get-all', StockApiController::class, 'index');
$router->add('GET', '/api/stocks/get/{id}', StockApiController::class, 'show');
$router->add('GET', '/api/stocks/get_for_chart/{id}', StockApiController::class, 'getForChart');
$router->add('GET', '/api/stocks/get-user-stocks', StockApiController::class, 'getUserStocks');
$router->add('GET', '/api/stocks/get-filtered', StockApiController::class, 'getFiltered');
$router->add('GET', '/api/stocks/get/{id}', StockApiController::class, 'show');
$router->add('GET', '/api/stocks/find-by-symbol/{symbol}', StockApiController::class, 'findBySymbol');
$router->add('POST', '/api/stocks/tentative-store', StockApiController::class, 'tentativeStore', [UserMiddleware::class, CsrfMiddleware::class]);
$router->add('GET', '/api/admins/show', AdminApiController::class, 'show', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/store', StockApiController::class, 'store', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/update', StockApiController::class, 'update', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/delete', StockApiController::class, 'delete', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/update-stock-prices', StockApiController::class, 'updateStockPrices', [AdminMiddleware::class, CsrfMiddleware::class]);

$router->add('GET', '/api/accounts/', AccountApiController::class, 'index');

$router->add('GET', '/api/trades', TradeApiController::class, 'index');
$router->add('GET', '/api/trades/get_for_chart/{uuid}/{stockId}', TradeApiController::class, 'getForChart');
$router->add('GET', '/api/trades/get_stock_ids_belong_to_user', TradeApiController::class, 'getStockIdsBelongToUser');
$router->add('POST', '/api/trades/store', TradeApiController::class, 'store', [UserMiddleware::class, CsrfMiddleware::class]);
$router->add('GET', '/api/assets/get_daily_assets', AssetApiController::class, 'getDailyAssets', [UserMiddleware::class]);
// $router->add('GET', '/api/assets/get_daily_assets_individual/{stockId}/{accountId}', AssetApiController::class, 'getDailyAssetsIndividual', [UserMiddleware::class]);
$router->add('POST', '/api/assets/daily_assets_by_stock_account_pairs', AssetApiController::class, 'dailyAssetsByStockAccountPairs', [UserMiddleware::class, CsrfMiddleware::class]);

$router->add('POST', '/api/user-stocks/update', UserStockApiController::class, 'update', [UserMiddleware::class, CsrfMiddleware::class]);

$router->add('POST', '/api/splits/store', SplitApiController::class, 'store', [UserMiddleware::class, CsrfMiddleware::class]);

$router->add('GET', '/api/dividends', DividendApiController::class, 'index', [AdminMiddleware::class]);
$router->add('POST', '/api/dividends/store', DividendApiController::class, 'store', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/dividends/update/{id}', DividendApiController::class, 'update', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/dividends/delete/{id}', DividendApiController::class, 'delete', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/dividends/upsert', DividendApiController::class, 'upsert', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('GET', '/api/dividends/get-yfinance-dividends', DividendApiController::class, 'getYfinanceDividends');



$router->dispatch($method, $uri);
