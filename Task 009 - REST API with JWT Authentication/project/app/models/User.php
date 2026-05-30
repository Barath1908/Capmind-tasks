<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * app/models/User.php
 * Handles all database operations for the `users` table.
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password, token_version, created_at
             FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a user by their primary key.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, token_version, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a user by their refresh token (only if token is not expired).
     */
    public function findByRefreshToken(string $refreshToken): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, token_version
             FROM users
             WHERE refresh_token = :token
             AND refresh_token_expiry > NOW()
             LIMIT 1'
        );
        $stmt->execute([':token' => $refreshToken]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Insert a new user and return the new record's ID.
     */
    public function create(string $name, string $email, string $hashedPassword): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$name, $email, $hashedPassword]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Increment token_version by 1 on every new login.
     * This invalidates all previously issued access tokens for this user.
     */
    public function incrementTokenVersion(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET token_version = token_version + 1 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    /**
     * Return the current token_version for a user.
     */
    public function getTokenVersion(int $id): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT token_version FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch();
        return $row ? (int) $row['token_version'] : null;
    }

    /**
     * Store a new refresh token and its expiry in the DB.
     * Replaces any existing refresh token (one active token per user).
     */
    public function saveRefreshToken(int $id, string $token, string $expiry): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET refresh_token = :token, refresh_token_expiry = :expiry
             WHERE id = :id'
        );
        $stmt->execute([
            ':token'  => $token,
            ':expiry' => $expiry,
            ':id'     => $id,
        ]);
    }

    /**
     * Clear the refresh token from DB (used on logout).
     */
    public function clearRefreshToken(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET refresh_token = NULL, refresh_token_expiry = NULL
             WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return true if any row with this email already exists.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }
}
