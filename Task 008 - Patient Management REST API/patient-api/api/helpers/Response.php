<?php

/**
 * Response Helper
 * Centralises all JSON output so every response is consistent.
 */
class Response
{
    /**
     * Send a JSON response and terminate execution.
     *
     * @param int        $httpCode  HTTP status code (200, 201, 400 …)
     * @param bool       $status    true = success, false = error
     * @param string     $message   Human-readable description
     * @param mixed      $data      Payload (array, object, or null)
     */
    public static function send(
        int    $httpCode,
        bool   $status,
        string $message,
        mixed  $data = null
    ): never {
        http_response_code($httpCode);

        $body = [
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ];

        echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Convenience wrappers ─────────────────────────────────────────────────

    public static function ok(string $message, mixed $data = null): never
    {
        self::send(200, true, $message, $data);
    }

    public static function created(string $message, mixed $data = null): never
    {
        self::send(201, true, $message, $data);
    }

    public static function badRequest(string $message): never
    {
        self::send(400, false, $message);
    }

    public static function notFound(string $message = 'Resource not found.'): never
    {
        self::send(404, false, $message);
    }

    public static function methodNotAllowed(string $message = 'HTTP method not allowed.'): never
    {
        self::send(405, false, $message);
    }

    public static function serverError(string $message = 'An unexpected error occurred.'): never
    {
        self::send(500, false, $message);
    }
}
