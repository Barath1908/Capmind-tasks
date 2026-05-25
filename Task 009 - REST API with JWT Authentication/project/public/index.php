<?php

declare(strict_types=1);

/**
 * public/index.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Single entry point for the entire API.
 *
 * Responsibilities:
 *   1. Autoload classes using a simple PSR-4-style loader
 *   2. Load environment variables via config/config.php
 *   3. Register all routes
 *   4. Dispatch the current request
 */

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    // Map namespace prefix to a base directory
    $namespaceMap = [
        'App\\' => dirname(__DIR__) . '/app/',
    ];

    foreach ($namespaceMap as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/config.php';

// ── Imports ───────────────────────────────────────────────────────────────────
use App\Controllers\AuthController;
use App\Controllers\PatientController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\JsonMiddleware;

// ── Set default JSON header ───────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

// ── Router ────────────────────────────────────────────────────────────────────
$router = new Router();

// Public routes (JsonMiddleware only)
$router->post('/api/register', AuthController::class, 'register', [JsonMiddleware::class]);
$router->post('/api/login',    AuthController::class, 'login',    [JsonMiddleware::class]);

// Protected routes (JsonMiddleware → AuthMiddleware)
$authStack = [JsonMiddleware::class, AuthMiddleware::class];

$router->get(   '/api/patients',      PatientController::class, 'index',   [AuthMiddleware::class]);
$router->get(   '/api/patients/{id}', PatientController::class, 'show',    [AuthMiddleware::class]);
$router->post(  '/api/patients',      PatientController::class, 'store',   $authStack);
$router->put(   '/api/patients/{id}', PatientController::class, 'update',  $authStack);
$router->delete('/api/patients/{id}', PatientController::class, 'destroy', [AuthMiddleware::class]);

// ── Dispatch ──────────────────────────────────────────────────────────────────
$method     = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Strip the subfolder prefix so routes match regardless of where the
// project lives inside wamp's www/ folder.
// e.g. /project/public/api/register  →  /api/register
//      /project/api/register         →  /api/register
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir !== '' && str_starts_with($requestUri, $scriptDir)) {
    $requestUri = substr($requestUri, strlen($scriptDir));
}
$requestUri = $requestUri ?: '/';

$router->dispatch($method, $requestUri, []);