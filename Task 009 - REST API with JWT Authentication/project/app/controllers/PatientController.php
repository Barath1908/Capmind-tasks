<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Patient;

/**
 * app/controllers/PatientController.php
 * CRUD operations for the Patient resource.
 * All routes are protected — AuthMiddleware has already validated the JWT.
 */
class PatientController
{
    private Patient $patientModel;

    public function __construct()
    {
        $this->patientModel = new Patient();
    }

    // ─── GET /api/patients ────────────────────────────────────────────────────

    /**
     * Return a list of all patients.
     *
     * @param array<string, mixed> $request
     */
    public function index(array $request): void
    {
        $patients = $this->patientModel->getAll();

        Response::success($patients, 'Patients retrieved successfully.');
    }

    // ─── GET /api/patients/{id} ───────────────────────────────────────────────

    /**
     * Return a single patient by ID.
     *
     * @param array<string, mixed> $request
     */
    public function show(array $request, string $id): void
    {
        $patient = $this->patientModel->findById((int) $id);

        if ($patient === null) {
            Response::error("Patient with id $id not found.", 404);
        }

        Response::success($patient, 'Patient retrieved successfully.');
    }

    // ─── POST /api/patients ───────────────────────────────────────────────────

    /**
     * Create a new patient record.
     *
     * @param array<string, mixed> $request
     */
    public function store(array $request): void
    {
        $body = $request['body'] ?? [];

        $this->validatePatientInput($body);

        $patientId = $this->patientModel->create($body);
        $patient   = $this->patientModel->findById($patientId);

        Response::success($patient, 'Patient created successfully.', 201);
    }

    // ─── PUT /api/patients/{id} ───────────────────────────────────────────────

    /**
     * Update an existing patient.
     *
     * @param array<string, mixed> $request
     */
    public function update(array $request, string $id): void
    {
        $existing = $this->patientModel->findById((int) $id);

        if ($existing === null) {
            Response::error("Patient with id $id not found.", 404);
        }

        $body = $request['body'] ?? [];

        $this->validatePatientInput($body);

        $this->patientModel->update((int) $id, $body);

        $updated = $this->patientModel->findById((int) $id);

        Response::success($updated, 'Patient updated successfully.');
    }

    // ─── DELETE /api/patients/{id} ────────────────────────────────────────────

    /**
     * Delete a patient.
     *
     * @param array<string, mixed> $request
     */
    public function destroy(array $request, string $id): void
    {
        $existing = $this->patientModel->findById((int) $id);

        if ($existing === null) {
            Response::error("Patient with id $id not found.", 404);
        }

        $this->patientModel->delete((int) $id);

        Response::success(null, "Patient with id $id deleted successfully.");
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Validate incoming patient data and abort with 422 on failure.
     *
     * @param array<string, mixed> $body
     */
    private function validatePatientInput(array $body): void
    {
        $name   = trim($body['name']   ?? '');
        $age    = $body['age']         ?? null;
        $gender = trim($body['gender'] ?? '');

        if ($name === '' || $age === null || $gender === '') {
            Response::error('name, age, and gender are required fields.', 422);
        }

        if (!is_numeric($age) || (int) $age < 0 || (int) $age > 150) {
            Response::error('age must be a number between 0 and 150.', 422);
        }

        $allowedGenders = ['male', 'female', 'other'];
        if (!in_array(strtolower($gender), $allowedGenders, true)) {
            Response::error('gender must be one of: male, female, other.', 422);
        }
    }
}
