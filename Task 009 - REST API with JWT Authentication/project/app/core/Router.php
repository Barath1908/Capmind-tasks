<?php

namespace App\Core;

/**
 * app/core/Router.php
 * Matches incoming HTTP method + URI against registered routes
 * and dispatches to the correct controller method.
 */
class Router
{
    /** @var array<string, array<string, array{0: string, 1: string, middlewares: string[]}>> */
    private array $routes = [];

    // ─── Route Registration ───────────────────────────────────────────────────

    public function get(string $uri, string $controller, string $method, array $middlewares = []): void
    {
        $this->addRoute('GET', $uri, $controller, $method, $middlewares);
    }

    public function post(string $uri, string $controller, string $method, array $middlewares = []): void
    {
        $this->addRoute('POST', $uri, $controller, $method, $middlewares);
    }

    public function put(string $uri, string $controller, string $method, array $middlewares = []): void
    {
        $this->addRoute('PUT', $uri, $controller, $method, $middlewares);
    }

    public function delete(string $uri, string $controller, string $method, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $uri, $controller, $method, $middlewares);
    }

    private function addRoute(
        string $httpMethod,
        string $uri,
        string $controller,
        string $action,
        array  $middlewares
    ): void {
        $this->routes[$httpMethod][$uri] = [
            'controller'  => $controller,
            'action'      => $action,
            'middlewares' => $middlewares,
        ];
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────────

    public function dispatch(string $requestMethod, string $requestUri, array $request): void
    {
        // Strip query string and normalise slashes
        $uri = parse_url($requestUri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Try exact match first
        $route = $this->routes[$requestMethod][$uri] ?? null;

        // Dynamic segment match (e.g. /api/patients/{id})
        $params = [];
        if ($route === null) {
            foreach (($this->routes[$requestMethod] ?? []) as $pattern => $routeData) {
                $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';

                if (preg_match($regex, $uri, $matches)) {
                    array_shift($matches); // remove full match
                    $params = $matches;
                    $route  = $routeData;
                    break;
                }
            }
        }

        if ($route === null) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Route not found.']);
            return;
        }

        // Run middleware chain
        foreach ($route['middlewares'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $request    = $middleware->handle($request);
        }

        // Dispatch to controller
        $controllerClass = $route['controller'];
        $action          = $route['action'];

        $controller = new $controllerClass();
        $controller->$action($request, ...$params);
    }
}
