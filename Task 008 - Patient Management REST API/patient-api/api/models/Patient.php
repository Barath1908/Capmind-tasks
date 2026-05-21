<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Patient Model
 *
 * All database interaction is done through prepared statements —
 * no user-supplied value is ever interpolated directly into SQL.
 */
class Patient
{
    private mysqli $db;

    // ─── Allowed / maximum lengths ────────────────────────────────────────────
    private const MAX_NAME   = 100;
    private const MAX_PHONE  = 15;
    private const GENDERS    = ['Male', 'Female', 'Other'];
    private const MIN_AGE    = 0;
    private const MAX_AGE    = 150;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Retrieve all patients (newest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllPatients(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, age, gender, phone, created_at, updated_at
               FROM patients
           ORDER BY created_at DESC'
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Retrieve a single patient by primary key.
     *
     * @return array<string, mixed>|null  null when not found
     */
    public function getPatientById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, age, gender, phone, created_at, updated_at
               FROM patients
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Insert a new patient record.
     *
     * @param  array<string, mixed> $data  Validated payload
     * @return array{id: int, name: string, age: int, gender: string, phone: string, created_at: string}
     * @throws InvalidArgumentException on validation failure
     */
    public function createPatient(array $data): array
    {
        $cleaned = $this->validate($data, required: true);

        $stmt = $this->db->prepare(
            'INSERT INTO patients (name, age, gender, phone)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'siss',
            $cleaned['name'],
            $cleaned['age'],
            $cleaned['gender'],
            $cleaned['phone']
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        return $this->getPatientById($newId);
    }

    /**
     * Update an existing patient (partial updates supported).
     *
     * @param  int                  $id
     * @param  array<string, mixed> $data
     * @return array<string, mixed>|null  Updated record, or null when not found
     * @throws InvalidArgumentException on validation failure
     */
    public function updatePatient(int $id, array $data): ?array
    {
        // Confirm the record exists first
        if ($this->getPatientById($id) === null) {
            return null;
        }

        $cleaned = $this->validate($data, required: false);

        if (empty($cleaned)) {
            throw new InvalidArgumentException('No valid fields provided for update.');
        }

        // Build SET clause dynamically from whatever fields were supplied
        $setClauses = [];
        $types      = '';
        $values     = [];

        if (isset($cleaned['name'])) {
            $setClauses[] = 'name = ?';
            $types       .= 's';
            $values[]     = $cleaned['name'];
        }
        if (isset($cleaned['age'])) {
            $setClauses[] = 'age = ?';
            $types       .= 'i';
            $values[]     = $cleaned['age'];
        }
        if (isset($cleaned['gender'])) {
            $setClauses[] = 'gender = ?';
            $types       .= 's';
            $values[]     = $cleaned['gender'];
        }
        if (isset($cleaned['phone'])) {
            $setClauses[] = 'phone = ?';
            $types       .= 's';
            $values[]     = $cleaned['phone'];
        }

        $sql    = 'UPDATE patients SET ' . implode(', ', $setClauses) . ' WHERE id = ?';
        $types .= 'i';
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();

        return $this->getPatientById($id);
    }

    /**
     * Hard-delete a patient record.
     *
     * @return bool  true if a row was deleted, false if the id did not exist
     */
    public function deletePatient(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM patients WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRIVATE — Validation & Sanitisation
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Validate and sanitise a data array.
     *
     * @param  array<string, mixed> $data
     * @param  bool                 $required  When true every field must be present
     * @return array<string, mixed>            Cleaned values
     * @throws InvalidArgumentException        Descriptive message per failed field
     */
    private function validate(array $data, bool $required): array
    {
        $errors  = [];
        $cleaned = [];

        // ── name ──────────────────────────────────────────────────────────────
        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                $errors[] = 'name must not be blank.';
            } elseif (mb_strlen($name) > self::MAX_NAME) {
                $errors[] = 'name must not exceed ' . self::MAX_NAME . ' characters.';
            } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $name)) {
                $errors[] = 'name contains invalid characters.';
            } else {
                $cleaned['name'] = $name;
            }
        } elseif ($required) {
            $errors[] = 'name is required.';
        }

        // ── age ───────────────────────────────────────────────────────────────
        if (isset($data['age'])) {
            if (!is_numeric($data['age'])) {
                $errors[] = 'age must be a number.';
            } else {
                $age = (int) $data['age'];
                if ($age < self::MIN_AGE || $age > self::MAX_AGE) {
                    $errors[] = 'age must be between ' . self::MIN_AGE . ' and ' . self::MAX_AGE . '.';
                } else {
                    $cleaned['age'] = $age;
                }
            }
        } elseif ($required) {
            $errors[] = 'age is required.';
        }

        // ── gender ────────────────────────────────────────────────────────────
        if (isset($data['gender'])) {
            $gender = trim((string) $data['gender']);
            if (!in_array($gender, self::GENDERS, true)) {
                $errors[] = 'gender must be one of: ' . implode(', ', self::GENDERS) . '.';
            } else {
                $cleaned['gender'] = $gender;
            }
        } elseif ($required) {
            $errors[] = 'gender is required.';
        }

        // ── phone ─────────────────────────────────────────────────────────────
        if (isset($data['phone'])) {
            // Strip spaces/dashes that callers sometimes include
            $phone = preg_replace('/[\s\-]/', '', (string) $data['phone']);
            if (!preg_match('/^\+?[0-9]{7,' . self::MAX_PHONE . '}$/', $phone)) {
                $errors[] = 'phone must be 7–' . self::MAX_PHONE . ' digits (optional leading +).';
            } else {
                $cleaned['phone'] = $phone;
            }
        } elseif ($required) {
            $errors[] = 'phone is required.';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' | ', $errors));
        }

        return $cleaned;
    }
}
