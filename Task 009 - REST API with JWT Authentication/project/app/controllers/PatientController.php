<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Patient;

/**
 * app/controllers/PatientController.php
 * CRUD operations for the Patient resource.
 * All routes are protected — AuthMiddleware has already validated the JWT
 * and attached the user payload to $request['user'].
 * Every operation is scoped to the authenticated user's ID.
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
     * Return a list of patients belonging to the authenticated user.
     */
    public function index(array $request): void
    {
        $userId   = (int) $request['user']['user_id'];
        $patients = $this->patientModel->getAll($userId);

        Response::success($patients, 'Patients retrieved successfully.');
    }

    // ─── GET /api/patients/{id} ───────────────────────────────────────────────

    /**
     * Return a single patient by ID (only if owned by the authenticated user).
     */
    public function show(array $request, string $id): void
    {
        $userId  = (int) $request['user']['user_id'];
        $patient = $this->patientModel->findById((int) $id, $userId);

        if ($patient === null) {
            Response::error("Patient with id $id not found.", 404);
        }

        Response::success($patient, 'Patient retrieved successfully.');
    }

    // ─── POST /api/patients ───────────────────────────────────────────────────

    /**
     * Create a new patient record linked to the authenticated user.
     */
    public function store(array $request): void
    {
        $userId = (int) $request['user']['user_id'];
        $body   = $request['body'] ?? [];

        $this->validatePatientInput($body);

        $patientId = $this->patientModel->create($body, $userId);
        $patient   = $this->patientModel->findById($patientId, $userId);

        Response::success($patient, 'Patient created successfully.', 201);
    }

    // ─── PUT /api/patients/{id} ───────────────────────────────────────────────

    /**
     * Update a patient (only if owned by the authenticated user).
     */
    public function update(array $request, string $id): void
    {
        $userId   = (int) $request['user']['user_id'];
        $existing = $this->patientModel->findById((int) $id, $userId);

        if ($existing === null) {
            Response::error("Patient with id $id not found.", 404);
        }

        $body = $request['body'] ?? [];

        $this->validatePatientInput($body);

        $this->patientModel->update((int) $id, $body, $userId);

        $updated = $this->patientModel->findById((int) $id, $userId);

        Response::success($updated, 'Patient updated successfully.');
    }

    // ─── DELETE /api/patients/{id} ────────────────────────────────────────────

    /**
     * Delete a patient (only if owned by the authenticated user).
     */
    public function destroy(array $request, string $id): void
    {
        $userId   = (int) $request['user']['user_id'];
        $existing = $this->patientModel->findById((int) $id, $userId);

        if ($existing === null) {
            Response::error("Patient with id $id not found.", 404);
        }

        $this->patientModel->delete((int) $id, $userId);

        Response::success(null, "Patient with id $id deleted successfully.");
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Validate incoming patient data and abort with 422 on failure.
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
