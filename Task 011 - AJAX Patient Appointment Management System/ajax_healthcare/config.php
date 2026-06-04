<?php
// ============================================================
//  config.php — Database connection
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← change to your MySQL user
define('DB_PASS', '');            // ← change to your MySQL password
define('DB_NAME', 'clinic_db');

// Maximum appointments per doctor per day (Bonus)
define('MAX_APPOINTMENTS_PER_DAY', 10);

// CSRF token lifetime in seconds (30 minutes)
define('CSRF_TOKEN_LIFETIME', 1800);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');
