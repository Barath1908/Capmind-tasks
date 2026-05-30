<?php

namespace App\Helpers;

/**
 * app/helpers/Response.php
 * Centralised JSON response builder.
 */
class Response
{
    /**
     * Send a successful JSON response and exit.
     *
     * @param mixed $data
     */
    public static function success(mixed $data, string $message = 'Success', int $statusCode = 200): never
    {
        http_response_code($statusCode);
        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ]);
        exit;
    }

    /**
     * Send an error JSON response and exit.
     */
    public static function error(string $message, int $statusCode = 400): never
    {
        http_response_code($statusCode);
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => null,
        ]);
        exit;
    }
}
