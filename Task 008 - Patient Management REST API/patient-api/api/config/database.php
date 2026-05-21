<?php

/**
 * Database Configuration & Connection
 * Uses environment variables or constants for credentials — never hardcoded in production.
 */
class Database
{
    // ─── Connection settings (override via env vars in production) ───────────
    private string $host     = 'localhost';
    private string $db_name  = 'patients_db';
    private string $username = 'root';          // change in production
    private string $password = '';              // change in production
    private string $charset  = 'utf8mb4';

    private ?mysqli $connection = null;

    // ─── Singleton ────────────────────────────────────────────────────────────
    private static ?Database $instance = null;

    private function __construct() {}

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return (and lazily open) the mysqli connection.
     */
    public function getConnection(): mysqli
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        // Allow environment-variable overrides (Docker / production)
        $host     = getenv('DB_HOST')     ?: $this->host;
        $username = getenv('DB_USER')     ?: $this->username;
        $password = getenv('DB_PASS')     ?: $this->password;
        $db_name  = getenv('DB_NAME')     ?: $this->db_name;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli($host, $username, $password, $db_name);
            $conn->set_charset($this->charset);
            $this->connection = $conn;
        } catch (mysqli_sql_exception $e) {
            // Never expose raw DB errors to the client
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status'  => false,
                'message' => 'Database connection error. Please try again later.',
                'data'    => null,
            ]);
            exit;
        }

        return $this->connection;
    }
}
