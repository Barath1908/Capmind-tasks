<?php

/**
 * config/config.php
 * Loads environment variables from .env and exposes them as constants.
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException(".env file not found at: $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments
        if (str_starts_with($line, '#') || $line === '') {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));

        if (!empty($key)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load .env from project root
loadEnv(dirname(__DIR__) . '/.env');

// Database
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'jwt_new_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// JWT
define('JWT_SECRET',           getenv('JWT_SECRET')           ?: 'fallback_secret');
define('JWT_EXPIRY',           (int)(getenv('JWT_EXPIRY')           ?: 900));
define('REFRESH_TOKEN_EXPIRY', (int)(getenv('REFRESH_TOKEN_EXPIRY') ?: 172800));

// AES Encryption
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '12345678901234567890123456789012');
