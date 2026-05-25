<?php

namespace App\Middleware;

use App\Helpers\Response;

/**
 * app/middleware/JsonMiddleware.php
 *
 * Ensures every API request carries a valid JSON body for write methods,
 * sets the Content-Type response header, and attaches the decoded body
 * to the $request array.
 */
class JsonMiddleware
{
    /** HTTP methods that MUST carry a JSON body */
    private const BODY_REQUIRED_METHODS = ['POST', 'PUT', 'PATCH'];

    /**
     * @param  array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function handle(array $request): array
    {
        // Always respond with JSON
        header('Content-Type: application/json; charset=utf-8');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($method, self::BODY_REQUIRED_METHODS, true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            // Must declare application/json
            if (!str_contains($contentType, 'application/json')) {
                Response::error(
                    'Content-Type must be application/json.',
                    415 // Unsupported Media Type
                );
            }

            // Read raw body
            $rawBody = file_get_contents('php://input');

            if ($rawBody === '' || $rawBody === false) {
                Response::error('Request body cannot be empty.', 400);
            }

            $decoded = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON payload: ' . json_last_error_msg(), 400);
            }

            $request['body'] = $decoded;
        } else {
            $request['body'] = [];
        }

        return $request;
    }
}
