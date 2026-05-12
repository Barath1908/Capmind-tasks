<?php
if (!defined('BASE_URL')) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $proto . '://' . $_SERVER['HTTP_HOST'] . '/' . basename(dirname(__DIR__)) . '/');
}
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error)
    die('<p style="color:#c53030;padding:20px;font-family:sans-serif"><b>DB Error:</b> ' . htmlspecialchars($conn->connect_error) . '</p>');
$conn->set_charset('utf8mb4');
