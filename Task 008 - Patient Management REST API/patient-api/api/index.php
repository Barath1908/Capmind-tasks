<?php

declare(strict_types=1);

/**
 * API Entry-point / Router
 * ─────────────────────────────────────────────────────────────────────────────
 * All requests are funnelled here by .htaccess.
 * URL pattern  :  /api/patients          → collection actions
 *                 /api/patients/{id}     → single-resource actions
 */

// ── Autoload helpers, middleware, controller ──────────────────────────────────
require_once 'helpers/Response.php';
require_once 'middlewares/JsonMiddleware.php';
require_once 'controllers/PatientController.php';

// ── 1. Run middleware (sets headers, validates Content-Type, parses body) ─────
$body = JsonMiddleware::handle();

// ── 2. Determine the HTTP method ──────────────────────────────────────────────
$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ── 3. Parse route segments ───────────────────────────────────────────────────
//
//  .htaccess passes everything after /api/ as the "request" query param:
//    /api/patients        → request = "patients"
//    /api/patients/42     → request = "patients/42"
//
$requestUri = trim($_GET['request'] ?? '', '/');
$segments   = array_values(array_filter(explode('/', $requestUri)));

//  segments[0] should be the resource name ("patients")
//  segments[1] (optional) is the numeric ID
$resource = $segments[0] ?? '';
$rawId    = $segments[1] ?? null;

// ── 4. Validate resource ──────────────────────────────────────────────────────
if ($resource !== 'patients') {
    Response::notFound('Endpoint not found.');
}

// ── 5. Validate and cast ID when present ─────────────────────────────────────
//
//  Only digits are accepted; anything else is a 400 rather than a 404 so we
//  do not accidentally leak information about valid IDs.
//
$id = null;
if ($rawId !== null) {
    if (!ctype_digit($rawId)) {
        Response::badRequest('Patient id must be a positive integer.');
    }
    $id = (int) $rawId;
    if ($id <= 0) {
        Response::badRequest('Patient id must be greater than zero.');
    }
}

// ── 6. Dispatch ───────────────────────────────────────────────────────────────
$controller = new PatientController();

if ($id === null) {
    // ── Collection endpoints (/api/patients) ──────────────────────────────────
    match ($method) {
        'GET'  => $controller->index(),
        'POST' => $controller->store($body),
        default => Response::methodNotAllowed(
            "Method {$method} is not allowed on /api/patients."
        ),
    };
} else {
    // ── Single-resource endpoints (/api/patients/{id}) ────────────────────────
    match ($method) {
        'GET'    => $controller->show($id),
        'PUT'    => $controller->update($id, $body),
        'DELETE' => $controller->destroy($id),
        default  => Response::methodNotAllowed(
            "Method {$method} is not allowed on /api/patients/{id}."
        ),
    };
}
