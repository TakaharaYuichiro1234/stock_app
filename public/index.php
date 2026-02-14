<?php

session_name('STOCK_APP_SESSID');
session_start();

require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';
require_once __DIR__ . '/../app/Controllers/StockController.php';
require_once __DIR__ . '/../app/Controllers/TradeController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/Api/AdminApiController.php';
require_once __DIR__ . '/../app/Controllers/Api/StockApiController.php';
require_once __DIR__ . '/../app/Controllers/Api/TradeApiController.php';
require_once __DIR__ . '/../app/Controllers/UserStockController.php';

use App\Controllers\AdminController;
use App\Controllers\StockController;
use App\Controllers\TradeController;
use App\Controllers\AuthController;
use App\Controllers\Api\AdminApiController;
use App\Controllers\Api\StockApiController;
use App\Controllers\Api\TradeApiController;
use App\Controllers\UserStockController;
use App\Core\Auth;

define('BASE_PATH', '/stock_app');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, BASE_PATH) === 0) {
    $uri = substr($uri, strlen(BASE_PATH));
}
if ($uri === '') {
    $uri = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

$webRoutes = [
    ['GET', '/', StockController::class, 'index'],
    ['GET', '/stocks', StockController::class, 'index'],
    ['GET', '/stocks/show-detail/{id}', StockController::class, 'showDetail'],

    ['GET', '/user-stocks', UserStockController::class, 'index'],

    ['GET',  '/show_login', AuthController::class, 'showLogin'],
    ['POST', '/login', AuthController::class, 'login'],
    ['POST', '/logout', AuthController::class, 'logout'],
];

$userRoutes = [
    ['GET', '/trades', TradeController::class, 'index', 'user'],
    ['GET', '/trades/index', TradeController::class, 'index', 'user'],
    ['GET', '/trades/create', TradeController::class, 'create', 'user'],
    ['POST', '/trades/store', TradeController::class, 'store', 'user'],
    ['POST', '/trades/update', TradeController::class, 'update', 'user'],
    ['POST', '/trades/delete', TradeController::class, 'delete', 'user'],

    ['POST', '/user-stocks/update', UserStockController::class, 'update', 'user'],
];

$adminRoutes = [
    ['GET', '/admins', AdminController::class, 'index', 'admin'],
    ['POST', '/admins/update_stock_prices_all', AdminController::class, 'updateStockPricesAll', 'admin'],
];

$apiRoutes = [
    ['GET', '/api/stocks/get/{id}', StockApiController::class, 'show'],
    ['GET', '/api/stocks/get_for_chart/{id}', StockApiController::class, 'getForChart'],
    ['GET', '/api/stocks/get-user-stocks', StockApiController::class, 'getUserStocks'],
    ['GET', '/api/stocks/get-filtered', StockApiController::class, 'getFiltered'],
    ['GET', '/api/trades/get_for_chart/{uuid}/{stockId}', TradeApiController::class, 'getForChart'],
];

$adminApiRoutes = [
    ['GET', '/api/admins/show', AdminApiController::class, 'show', 'admin'],

    ['POST', '/api/stocks/store', StockApiController::class, 'store', 'admin'],
    ['POST', '/api/stocks/update', StockApiController::class, 'update', 'admin'],
    ['POST', '/api/stocks/delete', StockApiController::class, 'delete', 'admin'],
    ['POST', '/api/stocks/update-stock-prices', StockApiController::class, 'updateStockPrices', 'admin'],
];

$routes = array_merge($webRoutes, $userRoutes, $adminRoutes, $apiRoutes, $adminApiRoutes);

$matched = false;

foreach ($routes as $route) {
    [$routeMethod, $routeUri, $controller, $action, $role] = array_pad($route, 5, null);
    if ($method !== $routeMethod) continue;

    $pattern = routeToRegex($routeUri);

    if (preg_match($pattern, $uri, $matches)) {
        // 認可
        if ($role === 'admin') {
            Auth::requireAdmin();
        }

        if ($role === 'user') {
            Auth::requireUser();
        }

        $params = array_slice($matches, 1);
        (new $controller())->$action(...$params);

        $matched = true;
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    echo '404 Not Found';
}

function routeToRegex($route) {
    // {xxx} をすべてキャプチャグループに変換
    $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route);
    return '#^' . $pattern . '$#';
}

