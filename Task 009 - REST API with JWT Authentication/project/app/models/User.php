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
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password, created_at FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a user by their primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

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

    // ─── Helpers ─────────────────────────────────────────────────────────────

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
