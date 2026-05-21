<?php

/**
 * JsonMiddleware
 *
 * Applied before every request:
 *  - Forces JSON Content-Type on all responses
 *  - Adds security headers (CORS only from trusted origins, no sniffing, etc.)
 *  - Blocks non-JSON bodies on write requests (POST / PUT / PATCH)
 *  - Returns a parsed body array for the caller to use
 */
class JsonMiddleware
{
    /**
     * Run all middleware checks.
     * Returns the decoded JSON body for write methods, or an empty array for GET/DELETE.
     *
     * @return array<string, mixed>
     */
    public static function handle(): array
    {
        self::setResponseHeaders();
        self::handlePreflight();
        return self::parseBody();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private static function setResponseHeaders(): void
    {
        // Force JSON responses
        header('Content-Type: application/json; charset=UTF-8');

        // ── Security headers ──────────────────────────────────────────────────
        // Prevent MIME-type sniffing
        header('X-Content-Type-Options: nosniff');
        // Disallow embedding in iframes
        header('X-Frame-Options: DENY');
        // Basic XSS protection for older browsers
        header('X-XSS-Protection: 1; mode=block');
        // Disable caching of API responses (avoid stale data)
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // ── CORS ──────────────────────────────────────────────────────────────
        // In production replace '*' with your actual front-end origin, e.g.:
        //   header('Access-Control-Allow-Origin: https://yourhospital.com');
        $allowedOrigin = getenv('ALLOWED_ORIGIN') ?: '*';
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400'); // cache preflight 24 h
    }

    /**
     * Respond to CORS preflight requests immediately so the browser does not
     * require an actual route to exist for OPTIONS.
     */
    private static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * For POST / PUT / PATCH: read the raw body, validate it is JSON,
     * and return the decoded array.
     *
     * @return array<string, mixed>
     */
    private static function parseBody(): array
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return [];
        }

        // Reject requests that do not declare application/json
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            require_once __DIR__ . '/../helpers/Response.php';
            Response::badRequest('Content-Type must be application/json.');
        }

        $raw = file_get_contents('php://input');

        if ($raw === '' || $raw === false) {
            require_once __DIR__ . '/../helpers/Response.php';
            Response::badRequest('Request body must not be empty.');
        }

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            require_once __DIR__ . '/../helpers/Response.php';
            Response::badRequest('Invalid JSON: ' . json_last_error_msg());
        }

        return $data;
    }
}
