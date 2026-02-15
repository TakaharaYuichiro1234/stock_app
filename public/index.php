<?php

session_name('STOCK_APP_SESSID');
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
use App\Controllers\UserStockController;
use App\Controllers\Api\AdminApiController;
use App\Controllers\Api\StockApiController;
use App\Controllers\Api\TradeApiController;
use App\Controllers\Api\UserStockApiController;
use App\Core\Auth;


$router = new Router();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, BASE_PATH) === 0) {
    $uri = substr($uri, strlen(BASE_PATH));
}
if ($uri === '') {
    $uri = '/';
}


$method = $_SERVER['REQUEST_METHOD'];

$router->add('GET', '/', StockController::class, 'index');
$router->add('GET', '/stocks', StockController::class, 'index');
$router->add('GET', '/stocks/show-detail/{id}', StockController::class, 'showDetail');
$router->add('GET', '/user-stocks', UserStockController::class, 'index');
$router->add('GET', '/show_login', AuthController::class, 'showLogin');
$router->add('POST', '/login', AuthController::class, 'login');
$router->add('POST', '/logout', AuthController::class, 'logout');

// $webRoutes = [
//     ['GET', '/', StockController::class, 'index'],
//     ['GET', '/stocks', StockController::class, 'index'],
//     ['GET', '/stocks/show-detail/{id}', StockController::class, 'showDetail'],

//     ['GET', '/user-stocks', UserStockController::class, 'index'],

//     ['GET',  '/show_login', AuthController::class, 'showLogin'],
//     ['POST', '/login', AuthController::class, 'login'],
//     ['POST', '/logout', AuthController::class, 'logout'],
// ];

$router->add('GET', '/trades', TradeController::class, 'index', [UserMiddleware::class]);
$router->add('GET', '/trades/index', TradeController::class, 'index', [UserMiddleware::class]);
$router->add('GET', '/trades/create', TradeController::class, 'create', [UserMiddleware::class]);
$router->add('POST', '/trades/store', TradeController::class, 'store', [UserMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/trades/update', TradeController::class, 'update', [UserMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/trades/delete', TradeController::class, 'delete', [UserMiddleware::class, CsrfMiddleware::class]);
// $router->add('POST', '/user-stocks/update', UserStockController::class, 'update', [UserMiddleware::class, CsrfMiddleware::class]);

// $userRoutes = [
//     ['GET', '/trades', TradeController::class, 'index', 'user'],
//     ['GET', '/trades/index', TradeController::class, 'index', 'user'],
//     ['GET', '/trades/create', TradeController::class, 'create', 'user'],
//     ['POST', '/trades/store', TradeController::class, 'store', 'user'],
//     ['POST', '/trades/update', TradeController::class, 'update', 'user'],
//     ['POST', '/trades/delete', TradeController::class, 'delete', 'user'],

//     ['POST', '/user-stocks/update', UserStockController::class, 'update', 'user'],
// ];


$router->add('GET', '/admins', AdminController::class, 'index', [AdminMiddleware::class]);
$router->add('POST', '/admins/update_stock_prices_all', AdminController::class, 'updateStockPricesAll', [AdminMiddleware::class, CsrfMiddleware::class]);

// $adminRoutes = [
//     ['GET', '/admins', AdminController::class, 'index', 'admin'],
//     ['POST', '/admins/update_stock_prices_all', AdminController::class, 'updateStockPricesAll', 'admin'],
// ];

$router->add('GET', '/api/stocks/get/{id}', StockApiController::class, 'show');
$router->add('GET', '/api/stocks/get_for_chart/{id}', StockApiController::class, 'getForChart');
$router->add('GET', '/api/stocks/get-user-stocks', StockApiController::class, 'getUserStocks');
$router->add('GET', '/api/stocks/get-filtered', StockApiController::class, 'getFiltered');
$router->add('GET', '/api/stocks/get/{id}', StockApiController::class, 'show');
$router->add('GET', '/api/trades/get_for_chart/{uuid}/{stockId}', TradeApiController::class, 'getForChart');

// $apiRoutes = [
//     ['GET', '/api/stocks/get/{id}', StockApiController::class, 'show'],
//     ['GET', '/api/stocks/get_for_chart/{id}', StockApiController::class, 'getForChart'],
//     ['GET', '/api/stocks/get-user-stocks', StockApiController::class, 'getUserStocks'],
//     ['GET', '/api/stocks/get-filtered', StockApiController::class, 'getFiltered'],
//     ['GET', '/api/trades/get_for_chart/{uuid}/{stockId}', TradeApiController::class, 'getForChart'],
// ];

$router->add('GET', '/api/admins/show', AdminApiController::class, 'show', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/store', StockApiController::class, 'store', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/update', StockApiController::class, 'update', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/delete', StockApiController::class, 'delete', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/stocks/update-stock-prices', StockApiController::class, 'updateStockPrices', [AdminMiddleware::class, CsrfMiddleware::class]);
$router->add('POST', '/api/user-stocks/update', UserStockApiController::class, 'update', [UserMiddleware::class, CsrfMiddleware::class]);

// $adminApiRoutes = [
//     ['GET', '/api/admins/show', AdminApiController::class, 'show', 'admin'],

//     ['POST', '/api/stocks/store', StockApiController::class, 'store', 'admin'],
//     ['POST', '/api/stocks/update', StockApiController::class, 'update', 'admin'],
//     ['POST', '/api/stocks/delete', StockApiController::class, 'delete', 'admin'],
//     ['POST', '/api/stocks/update-stock-prices', StockApiController::class, 'updateStockPrices', 'admin'],
// ];

$router->dispatch($method, $uri);



// $routes = array_merge($webRoutes, $userRoutes, $adminRoutes, $apiRoutes, $adminApiRoutes);

// $matched = false;

// foreach ($routes as $route) {
//     [$routeMethod, $routeUri, $controller, $action, $role] = array_pad($route, 5, null);
//     if ($method !== $routeMethod) continue;

//     $pattern = routeToRegex($routeUri);

//     if (preg_match($pattern, $uri, $matches)) {
//         // 認可
//         if ($role === 'admin') {
//             Auth::requireAdmin();
//         }

//         if ($role === 'user') {
//             Auth::requireUser();
//         }

//         $params = array_slice($matches, 1);
//         (new $controller())->$action(...$params);

//         $matched = true;
//         break;
//     }
// }

// if (!$matched) {
//     http_response_code(404);
//     echo '404 Not Found';
// }

// function routeToRegex($route) {
//     // {xxx} をすべてキャプチャグループに変換
//     $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route);
//     return '#^' . $pattern . '$#';
// }

