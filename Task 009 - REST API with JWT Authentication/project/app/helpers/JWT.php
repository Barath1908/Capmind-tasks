<?php

namespace App\Helpers;

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
     *
     * @param array{id: int, email: string} $userData
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
            'user_id' => $userData['id'],
            'email'   => $userData['email'],
            'iat'     => $issuedAt,
            'exp'     => $expiry,
        ]));

        $signature = self::sign("$header.$payload");

        return "$header.$payload.$signature";
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    /**
     * Validate the token and return its decoded payload.
     * Returns null if the token is invalid or expired.
     *
     * @return array<string, mixed>|null
     */
    public static function validate(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $providedSignature] = $parts;

        // Re-create signature and compare (constant-time)
        $expectedSignature = self::sign("$encodedHeader.$encodedPayload");

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);

        if (!is_array($payload)) {
            return null;
        }

        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expired
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
