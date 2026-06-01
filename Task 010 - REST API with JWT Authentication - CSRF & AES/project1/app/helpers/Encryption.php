<?php

namespace App\Helpers;

/**
 * app/helpers/Encryption.php
 * AES-256-CBC encryption and decryption helper.
 *
 * Flow:
 *   Encrypt: PlainText → AES-256-CBC (with random IV) → IV + Cipher → Base64
 *   Decrypt: Base64 → IV + Cipher → AES-256-CBC Decrypt → PlainText
 */
class Encryption
{
    private const CIPHER = 'AES-256-CBC';

    // ─── Encrypt ──────────────────────────────────────────────────────────────

    /**
     * Encrypt a plain text string.
     * Returns Base64-encoded string: IV + CipherText
     */
    public static function encrypt(string $plainText): string
    {
        $key = ENCRYPTION_KEY;

        // Generate a random IV (16 bytes for AES-256-CBC)
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));

        // Encrypt the data
        $encrypted = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $key,
            0,
            $iv
        );

        // Combine IV + CipherText → Base64 encode for safe DB storage
        return base64_encode($iv . $encrypted);
    }

    // ─── Decrypt ──────────────────────────────────────────────────────────────

    /**
     * Decrypt a Base64-encoded encrypted string.
     * Returns the original plain text.
     */
    public static function decrypt(string $encryptedData): string
    {
        $key = ENCRYPTION_KEY;

        // Base64 decode to get raw binary (IV + CipherText)
        $data = base64_decode($encryptedData);

        // Extract IV from first 16 bytes
        $ivLength  = openssl_cipher_iv_length(self::CIPHER);
        $iv        = substr($data, 0, $ivLength);

        // Remaining bytes are the actual cipher text
        $cipherText = substr($data, $ivLength);

        // Decrypt using same key and extracted IV
        return openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $key,
            0,
            $iv
        );
    }

    // ─── Nullable helpers ─────────────────────────────────────────────────────

    /**
     * Encrypt only if value is not null (for optional fields like phone, address).
     */
    public static function encryptNullable(?string $value): ?string
    {
        return $value !== null ? self::encrypt($value) : null;
    }

    /**
     * Decrypt only if value is not null.
     */
    public static function decryptNullable(?string $value): ?string
    {
        return $value !== null ? self::decrypt($value) : null;
    }
}
