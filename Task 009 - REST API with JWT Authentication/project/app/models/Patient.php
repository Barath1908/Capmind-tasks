<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * app/models/Patient.php
 * Handles all database operations for the `patients` table.
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
     * Fetch every patient, ordered by creation date descending.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, age, gender, phone, address, created_at, updated_at
             FROM patients
             ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Find a single patient by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, age, gender, phone, address, created_at, updated_at
             FROM patients WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Insert a new patient and return the new ID.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO patients (name, age, gender, phone, address, created_at, updated_at)
             VALUES (:name, :age, :gender, :phone, :address, NOW(), NOW())'
        );

        $stmt->execute([
            ':name'    => $data['name'],
            ':age'     => (int) $data['age'],
            ':gender'  => $data['gender'],
            ':phone'   => $data['phone']   ?? null,
            ':address' => $data['address'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing patient. Returns the number of affected rows.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): int
    {
        $stmt = $this->db->prepare(
            'UPDATE patients
             SET name = :name, age = :age, gender = :gender,
                 phone = :phone, address = :address, updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            ':name'    => $data['name'],
            ':age'     => (int) $data['age'],
            ':gender'  => $data['gender'],
            ':phone'   => $data['phone']   ?? null,
            ':address' => $data['address'] ?? null,
            ':id'      => $id,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Delete a patient by ID. Returns the number of affected rows.
     */
    public function delete(int $id): int
    {
        $stmt = $this->db->prepare('DELETE FROM patients WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
