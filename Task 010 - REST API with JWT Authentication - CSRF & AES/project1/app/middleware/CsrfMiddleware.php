<?php

namespace App\Middleware;

use App\Helpers\Response;

/**
 * app/middleware/CsrfMiddleware.php
 *
 * Verifies the X-CSRF-Token header on every write request (POST, PUT, DELETE).
 * Token is generated on login and stored in the PHP session.
 *
 * Flow:
 *   Login → generate CSRF token → store in session → return to frontend
 *   Frontend → stores token in memory → sends in X-CSRF-Token header
 *   Backend → compares header token with session token → allows or blocks
 */
class CsrfMiddleware
{
    public function handle(array $request): array
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Read token from request header
        $headers   = getallheaders();
        $csrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';

        if (empty($csrfToken)) {
            Response::error('CSRF token missing.', 403);
        }

        // Compare with session token using constant-time comparison (prevents timing attacks)
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (!hash_equals($sessionToken, $csrfToken)) {
            Response::error('Invalid CSRF token.', 403);
        }

        return $request;
    }
}
