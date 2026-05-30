<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * app/models/Patient.php
 * Handles all database operations for the `patients` table.
 * Every query is scoped to the authenticated user's ID.
 */
class Patient
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /**
     * Fetch every patient belonging to a specific user.
     */
    public function getAll(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, age, gender, phone, address, created_at, updated_at
             FROM patients
             WHERE user_id = :user_id
             ORDER BY created_at DESC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single patient by primary key, only if it belongs to the given user.
     */
    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, age, gender, phone, address, created_at, updated_at
             FROM patients
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Insert a new patient linked to a user and return the new ID.
     */
    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO patients (user_id, name, age, gender, phone, address, created_at, updated_at)
             VALUES (:user_id, :name, :age, :gender, :phone, :address, NOW(), NOW())'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':name'    => $data['name'],
            ':age'     => (int) $data['age'],
            ':gender'  => $data['gender'],
            ':phone'   => $data['phone']   ?? null,
            ':address' => $data['address'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a patient only if it belongs to the given user.
     */
    public function update(int $id, array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            'UPDATE patients
             SET name = :name, age = :age, gender = :gender,
                 phone = :phone, address = :address, updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );

        $stmt->execute([
            ':name'    => $data['name'],
            ':age'     => (int) $data['age'],
            ':gender'  => $data['gender'],
            ':phone'   => $data['phone']   ?? null,
            ':address' => $data['address'] ?? null,
            ':id'      => $id,
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Delete a patient only if it belongs to the given user.
     */
    public function delete(int $id, int $userId): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM patients WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->rowCount();
    }
}
