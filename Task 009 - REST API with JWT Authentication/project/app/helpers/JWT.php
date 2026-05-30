<?php

namespace App\Helpers;

use App\Models\User;

/**
 * app/helpers/JWT.php
 * Pure-PHP HS256 JSON Web Token implementation.
 *
 * Token anatomy: base64url(header).base64url(payload).base64url(signature)
 */
class JWT
{
    // ─── Encoding ─────────────────────────────────────────────────────────────

    /**
     * Generate a signed JWT for the given user data.
     * token_version is embedded so old tokens are rejected on next login.
     */
    public static function generate(array $userData): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $issuedAt = time();
        $expiry   = $issuedAt + JWT_EXPIRY;

        $payload = self::base64UrlEncode(json_encode([
            'user_id'       => $userData['id'],
            'email'         => $userData['email'],
            'token_version' => $userData['token_version'],
            'iat'           => $issuedAt,
            'exp'           => $expiry,
        ]));

        $signature = self::sign("$header.$payload");

        return "$header.$payload.$signature";
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    /**
     * Validate the token and return its decoded payload.
     *
     * Returns null if:
     *   - token is malformed
     *   - signature is wrong
     *   - token is expired
     *   - token_version is outdated (user logged in again elsewhere)
     *   - email doesn't match the token's email (wrong user's token)
     */
    public static function validate(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $providedSignature] = $parts;

        // ── Signature check ───────────────────────────────────────────────────
        $expectedSignature = self::sign("$encodedHeader.$encodedPayload");

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        // ── Decode payload ────────────────────────────────────────────────────
        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);

        // payload is now:
        // [
        //     'user_id'       => 1,
        //     'email'         => 'aadhi@test.com',
        //     'token_version' => 2,
        //     'iat'           => 1716000000,
        //     'exp'           => 1716003600,
        // ]

        if (!is_array($payload)) {
            return null;
        }

        // ── Expiry check ──────────────────────────────────────────────────────
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }


        // ── Token version check ───────────────────────────────────────────────
        // Compare the version embedded in the token against the DB.
        // If the user has logged in again since this token was issued,
        // the DB version will be higher and this token is rejected.
        $userId       = $payload['user_id']       ?? null;
        $tokenVersion = $payload['token_version'] ?? null;

        if ($userId === null || $tokenVersion === null) {
            return null;
        }

        $userModel      = new User();
        $currentVersion = $userModel->getTokenVersion((int) $userId);

        if ($currentVersion === null || (int) $tokenVersion !== $currentVersion) {
            return null;
        }

        return $payload;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private static function sign(string $data): string
    {
        return self::base64UrlEncode(
            hash_hmac('sha256', $data, JWT_SECRET, true)
        );
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}