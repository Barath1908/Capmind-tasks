<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * app/core/Database.php
 * Singleton PDO wrapper. Returns a single shared connection per request.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Returns the shared PDO instance, creating it on first call.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Never leak connection details to clients
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
                exit;
            }
        }

        return self::$instance;
    }
}
