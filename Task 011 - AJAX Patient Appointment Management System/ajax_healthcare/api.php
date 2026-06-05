<?php
ob_start();
session_start();

// ============================================================
//  api.php — RESTful API for Appointment Management
//  Methods: GET | POST | PUT | DELETE | PATCH (status)
// ============================================================

require_once 'config.php';

header('Content-Type: application/json');

// ── Helpers ──────────────────────────────────────────────────
function respond(bool $success, string $message, $data = null, int $code = 200): void {
    http_response_code($code);
    $payload = ['success' => $success, 'message' => $message];
    if ($data !== null) $payload['data'] = $data;
    echo json_encode($payload);
    exit;
}

function getInput(): array {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

// ── CSRF validation ──────────────────────────────────────────
function validateCsrfToken(string $token): bool {
    if (empty($_SESSION['csrf_token'])) return false;
    if (time() - ($_SESSION['csrf_time'] ?? 0) > CSRF_TOKEN_LIFETIME) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ── Validators ───────────────────────────────────────────────
function validateAppointmentData(array $d, bool $requireId = false): string {
    if ($requireId && empty($d['id']))     return 'Appointment ID is required.';
    if (empty($d['patient_name']))         return 'Patient name is required.';
    if (strlen($d['patient_name']) > 100)  return 'Patient name too long (max 100 chars).';
    if (empty($d['email']))                return 'Email is required.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) return 'Invalid email format.';
    if (empty($d['mobile']))               return 'Mobile number is required.';
    if (!preg_match('/^[0-9]{7,15}$/', $d['mobile'])) return 'Mobile must be 7–15 digits.';
    if (empty($d['appointment_date']))     return 'Appointment date is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['appointment_date'])) return 'Invalid date format.';
    if (!$requireId && $d['appointment_date'] < date('Y-m-d')) return 'Appointment date cannot be in the past.';
    if (empty($d['appointment_time']))     return 'Appointment time is required.';
    if (empty($d['doctor_id']) || !is_numeric($d['doctor_id'])) return 'Please select a doctor.';
    return '';
}

// ── Route by method ──────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

// ════════════════════════════════════════════════════════════
//  GET — Fetch all appointments
// ════════════════════════════════════════════════════════════
if ($method === 'GET') {
    $sql = "SELECT a.*, d.name AS doctor_name, d.specialty
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $result = $conn->query($sql);
    if (!$result) respond(false, 'Database error: ' . $conn->error, null, 500);

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        unset($row['csrf_token']);
        $appointments[] = $row;
    }
    respond(true, count($appointments) . ' appointment(s) found.', $appointments);
}

// ════════════════════════════════════════════════════════════
//  POST — Create new appointment
// ════════════════════════════════════════════════════════════
if ($method === 'POST') {
    $d = getInput();

    $csrfToken = $d['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) respond(false, 'Invalid or expired CSRF token.', null, 403);

    $err = validateAppointmentData($d);
    if ($err) respond(false, $err, null, 422);

    $name  = sanitize($d['patient_name']);
    $email = sanitize($d['email']);
    $mob   = sanitize($d['mobile']);
    $docId = (int)$d['doctor_id'];
    $date  = $d['appointment_date'];
    $time  = $d['appointment_time'];

    // Prevent double booking
    $chk = $conn->prepare(
        "SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status != 'Cancelled'"
    );
    $chk->bind_param('iss', $docId, $date, $time);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        respond(false, 'This time slot is already booked for the selected doctor. Please choose another time.', null, 409);
    }

    // Daily limit per doctor
    $lim = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id=? AND appointment_date=? AND status != 'Cancelled'"
    );
    $lim->bind_param('is', $docId, $date);
    $lim->execute();
    $limRow = $lim->get_result()->fetch_assoc();
    if ($limRow['cnt'] >= MAX_APPOINTMENTS_PER_DAY) {
        respond(false, 'This doctor has reached the maximum appointments (' . MAX_APPOINTMENTS_PER_DAY . ') for that day.', null, 409);
    }

    $stmt = $conn->prepare(
        "INSERT INTO appointments (patient_name, email, mobile, doctor_id, appointment_date, appointment_time, status)
         VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
    );
    $stmt->bind_param('sssiss', $name, $email, $mob, $docId, $date, $time);
    if (!$stmt->execute()) respond(false, 'Database error: ' . $conn->error, null, 500);

    $newId = $conn->insert_id;
    $fetch = $conn->prepare(
        "SELECT a.*, d.name AS doctor_name, d.specialty
         FROM appointments a LEFT JOIN doctors d ON a.doctor_id=d.id WHERE a.id=?"
    );
    $fetch->bind_param('i', $newId);
    $fetch->execute();
    $newRow = $fetch->get_result()->fetch_assoc();
    unset($newRow['csrf_token']);

    respond(true, 'Appointment created successfully!', $newRow, 201);
}

// ════════════════════════════════════════════════════════════
//  PUT — Update existing appointment
// ════════════════════════════════════════════════════════════
if ($method === 'PUT') {
    $d = getInput();

    $csrfToken = $d['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) respond(false, 'Invalid or expired CSRF token.', null, 403);

    $err = validateAppointmentData($d, true);
    if ($err) respond(false, $err, null, 422);

    $id    = (int)$d['id'];
    $name  = sanitize($d['patient_name']);
    $email = sanitize($d['email']);
    $mob   = sanitize($d['mobile']);
    $docId = (int)$d['doctor_id'];
    $date  = $d['appointment_date'];
    $time  = $d['appointment_time'];

    $ex = $conn->prepare("SELECT id FROM appointments WHERE id=?");
    $ex->bind_param('i', $id);
    $ex->execute();
    if ($ex->get_result()->num_rows === 0) respond(false, 'Appointment not found.', null, 404);

    $chk = $conn->prepare(
        "SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND id != ? AND status != 'Cancelled'"
    );
    $chk->bind_param('issi', $docId, $date, $time, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        respond(false, 'This time slot is already booked for the selected doctor.', null, 409);
    }

    $stmt = $conn->prepare(
        "UPDATE appointments SET patient_name=?, email=?, mobile=?, doctor_id=?, appointment_date=?, appointment_time=? WHERE id=?"
    );
    $stmt->bind_param('sssissi', $name, $email, $mob, $docId, $date, $time, $id);
    if (!$stmt->execute()) respond(false, 'Database error: ' . $conn->error, null, 500);
    if ($stmt->affected_rows === 0) respond(false, 'No changes were made.', null, 200);

    $fetch = $conn->prepare(
        "SELECT a.*, d.name AS doctor_name, d.specialty
         FROM appointments a LEFT JOIN doctors d ON a.doctor_id=d.id WHERE a.id=?"
    );
    $fetch->bind_param('i', $id);
    $fetch->execute();
    $updated = $fetch->get_result()->fetch_assoc();
    unset($updated['csrf_token']);

    respond(true, 'Appointment updated successfully!', $updated);
}

// ════════════════════════════════════════════════════════════
//  PATCH — Update status only
// ════════════════════════════════════════════════════════════
if ($method === 'PATCH') {
    $d = getInput();

    $csrfToken = $d['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) respond(false, 'Invalid or expired CSRF token.', null, 403);

    if (empty($d['id']))     respond(false, 'Appointment ID is required.', null, 422);
    if (empty($d['status'])) respond(false, 'Status is required.', null, 422);

    $id      = (int)$d['id'];
    $status  = $d['status'];
    $allowed = ['Pending', 'Confirmed', 'Cancelled'];
    if (!in_array($status, $allowed)) respond(false, 'Invalid status value.', null, 422);

    $ex = $conn->prepare("SELECT id FROM appointments WHERE id=?");
    $ex->bind_param('i', $id);
    $ex->execute();
    if ($ex->get_result()->num_rows === 0) respond(false, 'Appointment not found.', null, 404);

    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->bind_param('si', $status, $id);
    if (!$stmt->execute()) respond(false, 'Database error: ' . $conn->error, null, 500);

    respond(true, 'Status updated to ' . $status . '.');
}

// ════════════════════════════════════════════════════════════
//  DELETE — Delete appointment
// ════════════════════════════════════════════════════════════
if ($method === 'DELETE') {
    $d = getInput();

    $csrfToken = $d['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) respond(false, 'Invalid or expired CSRF token.', null, 403);

    if (empty($d['id'])) respond(false, 'Appointment ID is required.', null, 422);

    $id = (int)$d['id'];

    $ex = $conn->prepare("SELECT id FROM appointments WHERE id=?");
    $ex->bind_param('i', $id);
    $ex->execute();
    if ($ex->get_result()->num_rows === 0) respond(false, 'Appointment not found.', null, 404);

    $stmt = $conn->prepare("DELETE FROM appointments WHERE id=?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) respond(false, 'Database error: ' . $conn->error, null, 500);

    respond(true, 'Appointment deleted successfully.');
}

respond(false, 'Invalid request method.', null, 405);
