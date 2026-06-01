<?php

declare(strict_types=1);

/**
 * public/index.php
 * Single entry point for the entire API.
 */

// ── Manual Class Loading ──────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/app/core/Database.php';
require_once dirname(__DIR__) . '/app/core/Router.php';
require_once dirname(__DIR__) . '/app/helpers/JWT.php';
require_once dirname(__DIR__) . '/app/helpers/RefreshToken.php';
require_once dirname(__DIR__) . '/app/helpers/Encryption.php';
require_once dirname(__DIR__) . '/app/helpers/Response.php';
require_once dirname(__DIR__) . '/app/middleware/JsonMiddleware.php';
require_once dirname(__DIR__) . '/app/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/app/middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/Patient.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/PatientController.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/config.php';

// ── Imports ───────────────────────────────────────────────────────────────────
use App\Controllers\AuthController;
use App\Controllers\PatientController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\JsonMiddleware;

// ── Default JSON header ───────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

// ── Router ────────────────────────────────────────────────────────────────────
$router = new Router();

// ── Auth routes ───────────────────────────────────────────────────────────────
$router->post('/api/register',      AuthController::class, 'register', [JsonMiddleware::class]);
$router->post('/api/login',         AuthController::class, 'login',    [JsonMiddleware::class]);
$router->post('/api/token/refresh', AuthController::class, 'refresh',  []);
$router->post('/api/logout',        AuthController::class, 'logout',   [AuthMiddleware::class, CsrfMiddleware::class]);

// ── Patient routes (protected) ────────────────────────────────────────────────
// GET  → AuthMiddleware only (read, no body)
// POST/PUT/DELETE → JsonMiddleware + AuthMiddleware + CsrfMiddleware (write operations)
$router->get(   '/api/patients',      PatientController::class, 'index',   [AuthMiddleware::class]);
$router->get(   '/api/patients/{id}', PatientController::class, 'show',    [AuthMiddleware::class]);
$router->post(  '/api/patients',      PatientController::class, 'store',   [JsonMiddleware::class, AuthMiddleware::class, CsrfMiddleware::class]);
$router->put(   '/api/patients/{id}', PatientController::class, 'update',  [JsonMiddleware::class, AuthMiddleware::class, CsrfMiddleware::class]);
$router->delete('/api/patients/{id}', PatientController::class, 'destroy', [AuthMiddleware::class, CsrfMiddleware::class]);

// ── Dispatch ──────────────────────────────────────────────────────────────────
$method     = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir !== '' && str_starts_with($requestUri, $scriptDir)) {
    $requestUri = substr($requestUri, strlen($scriptDir));
}
$requestUri = $requestUri ?: '/';

$router->dispatch($method, $requestUri, []);
