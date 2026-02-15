<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $uri, string $controller, string $action, array $middlewares = [])
    {
        $this->routes[] = compact('method', 'uri', 'controller', 'action', 'middlewares');
    }

    public function dispatch(string $requestMethod, string $requestUri)
    {
        foreach ($this->routes as $route) {
            if ($requestMethod !== $route['method']) {
                continue;
            }

            $pattern = $this->routeToRegex($route['uri']);

            if (preg_match($pattern, $requestUri, $matches)) {

                foreach ($route['middlewares'] as $middleware) {
                    (new $middleware())->handle();
                }

                $params = array_slice($matches, 1);
                
                (new $route['controller'])->{$route['action']}(...$params);

                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function routeToRegex($route)
    {
        $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route);
        return '#^' . $pattern . '$#';
    }
}
