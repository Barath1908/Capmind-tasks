<?php

require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../helpers/Response.php';

/**
 * PatientController
 *
 * Dispatches HTTP methods to the correct Patient model function
 * and returns uniform JSON responses via the Response helper.
 *
 * Security notes
 * ──────────────
 * • IDs always come from the parsed URL segment (integer-cast + range-checked),
 *   never from the query-string or body.
 * • Body data is always read from php://input via JsonMiddleware — never $_GET.
 * • All model calls are wrapped in try/catch so exceptions never bubble to the
 *   client as raw stack traces.
 */
class PatientController
{
    private Patient $model;

    public function __construct()
    {
        $this->model = new Patient();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Route entry-points (called by index.php)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/patients  — list all patients
     */
    public function index(): never
    {
        try {
            $patients = $this->model->getAllPatients();

            if (empty($patients)) {
                Response::ok('No patients found.', []);
            }

            Response::ok('Patients retrieved successfully.', $patients);
        } catch (Throwable $e) {
            $this->handleUnexpected($e);
        }
    }

    /**
     * GET /api/patients/{id}  — fetch a single patient
     */
    public function show(int $id): never
    {
        $this->assertValidId($id);

        try {
            $patient = $this->model->getPatientById($id);

            if ($patient === null) {
                Response::notFound("Patient with id {$id} not found.");
            }

            Response::ok('Patient retrieved successfully.', $patient);
        } catch (Throwable $e) {
            $this->handleUnexpected($e);
        }
    }

    /**
     * POST /api/patients  — create a new patient
     *
     * @param array<string, mixed> $body  Pre-parsed JSON body from JsonMiddleware
     */
    public function store(array $body): never
    {
        try {
            $patient = $this->model->createPatient($body);
            Response::created('Patient created successfully.', $patient);
        } catch (InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (Throwable $e) {
            $this->handleUnexpected($e);
        }
    }

    /**
     * PUT /api/patients/{id}  — full or partial update
     *
     * @param array<string, mixed> $body  Pre-parsed JSON body from JsonMiddleware
     */
    public function update(int $id, array $body): never
    {
        $this->assertValidId($id);

        if (empty($body)) {
            Response::badRequest('Request body must not be empty.');
        }

        try {
            $patient = $this->model->updatePatient($id, $body);

            if ($patient === null) {
                Response::notFound("Patient with id {$id} not found.");
            }

            Response::ok('Patient updated successfully.', $patient);
        } catch (InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (Throwable $e) {
            $this->handleUnexpected($e);
        }
    }

    /**
     * DELETE /api/patients/{id}  — remove a patient
     */
    public function destroy(int $id): never
    {
        $this->assertValidId($id);

        try {
            $deleted = $this->model->deletePatient($id);

            if (!$deleted) {
                Response::notFound("Patient with id {$id} not found.");
            }

            Response::ok("Patient with id {$id} deleted successfully.");
        } catch (Throwable $e) {
            $this->handleUnexpected($e);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Private helpers
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Validate that the route ID is a positive integer within a safe range.
     * Terminates with 400 if invalid.
     */
    private function assertValidId(int $id): void
    {
        if ($id <= 0 || $id > PHP_INT_MAX) {
            Response::badRequest('Patient id must be a positive integer.');
        }
    }

    /**
     * Log unexpected errors server-side and return a generic 500 to the client.
     * Never expose exception messages or stack traces to the caller.
     */
    private function handleUnexpected(Throwable $e): never
    {
        error_log(
            '[PatientController] Unexpected error: ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine()
        );
        Response::serverError();
    }
}
