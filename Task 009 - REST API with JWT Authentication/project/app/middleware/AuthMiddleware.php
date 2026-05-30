<?php

namespace App\Middleware;

use App\Helpers\JWT;
use App\Helpers\Response;

/**
 * app/middleware/AuthMiddleware.php
 *
 * Reads the Authorization header, extracts the Bearer token,
 * validates it via the JWT helper, and attaches the decoded
 * user payload to $request['user'].
 *
 * Blocks the request with 401 Unauthorized if the token is
 * missing, malformed, or expired.
 */
class AuthMiddleware
{
    public function handle(array $request): array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? getallheaders()['Authorization']
            ?? '';

        if (empty($authHeader)) {
            Response::error('Authorization header is missing.', 401);
        }

        // Expect: Bearer <token>
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            /*
            $matches = [
                        0 => "Bearer eyJhbGci...",
                        1 => "eyJhbGci..."
                    ];
            */
            Response::error('Invalid Authorization header format. Expected: Bearer <token>', 401);
        }

        $token   = $matches[1];
        $payload = JWT::validate($token);

        if ($payload === null) {
            Response::error('Invalid or expired token.', 401);
        }

        // Attach decoded user data so controllers can read it
        $request['user'] = $payload;

        return $request;
    }
}

// [
//     'Content-Type'  => 'application/json',
//     'Authorization' => 'Bearer eyJhbGci...',
//     'Host'          => 'localhost',
// ]