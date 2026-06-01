<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Encryption;
use PDO;

/**
 * app/models/Patient.php
 * Handles all database operations for the `patients` table.
 *
 * Sensitive fields are encrypted before storing and decrypted after reading:
 *   - name    → encrypted
 *   - phone   → encrypted (nullable)
 *   - address → encrypted (nullable)
 *
 * Non-sensitive fields stored as plain:
 *   - age, gender (needed for filtering/sorting in future)
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
        $rows = $stmt->fetchAll();

        // Decrypt sensitive fields for every row
        return array_map([$this, 'decryptPatient'], $rows);
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
        if (!$result) return null;

        // Decrypt before returning
        return $this->decryptPatient($result);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Insert a new patient with encrypted sensitive fields.
     */
    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO patients (user_id, name, age, gender, phone, address, created_at, updated_at)
             VALUES (:user_id, :name, :age, :gender, :phone, :address, NOW(), NOW())'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':name'    => Encryption::encrypt($data['name']),
            ':age'     => (int) $data['age'],
            ':gender'  => $data['gender'],
            ':phone'   => Encryption::encryptNullable($data['phone']   ?? null),
            ':address' => Encryption::encryptNullable($data['address'] ?? null),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a patient with encrypted sensitive fields.
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
            ':name'    => Encryption::encrypt($data['name']),
            ':age'     => (int) $data['age'],
            ':gender'  => $data['gender'],
            ':phone'   => Encryption::encryptNullable($data['phone']   ?? null),
            ':address' => Encryption::encryptNullable($data['address'] ?? null),
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

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Decrypt all sensitive fields of a patient row.
     */
    private function decryptPatient(array $patient): array
    {
        $patient['name']    = Encryption::decrypt($patient['name']);
        $patient['phone']   = Encryption::decryptNullable($patient['phone']);
        $patient['address'] = Encryption::decryptNullable($patient['address']);
        return $patient;
    }
}
