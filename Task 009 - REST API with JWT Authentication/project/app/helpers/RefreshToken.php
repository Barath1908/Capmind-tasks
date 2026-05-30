<?php

namespace App\Helpers;

/**
 * app/helpers/RefreshToken.php
 * Handles generation and cookie management for refresh tokens.
 * Expiry is driven by the REFRESH_TOKEN_EXPIRY constant (set in .env).
 */
class RefreshToken
{
    // ─── Generate ─────────────────────────────────────────────────────────────

    /**
     * Generate a cryptographically secure random refresh token string.
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(40)); // 80 hex chars
    }

    /**
     * Return the expiry datetime string for DB storage.
     * Reads REFRESH_TOKEN_EXPIRY seconds from config.
     */
    public static function expiryDatetime(): string
    {
        return date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXPIRY);
    }

    // ─── Cookie ───────────────────────────────────────────────────────────────

    /**
     * Set the refresh token as an HttpOnly cookie.
     * JavaScript cannot access HttpOnly cookies — protects against XSS.
     */
    public static function setCookie(string $token): void
    {
        setcookie(
            'refresh_token',
            $token,
            [
                'expires'  => time() + REFRESH_TOKEN_EXPIRY,
                'path'     => '/',
                'httponly' => true,    // JS cannot read this
                'samesite' => 'Strict',
            ]
        );
    }

    /**
     * Clear the refresh token cookie (used on logout).
     */
    public static function clearCookie(): void
    {
        setcookie('refresh_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    /**
     * Read the refresh token from the incoming cookie.
     * Returns null if cookie is not present.
     */
    public static function fromCookie(): ?string
    {
        return $_COOKIE['refresh_token'] ?? null;
    }
}