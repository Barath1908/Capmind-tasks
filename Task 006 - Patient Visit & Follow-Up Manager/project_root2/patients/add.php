<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$errors = [];
$success = '';
$new_id  = 0;
$data = ['name'=>'','dob'=>'','join_date'=>'','phone'=>'','address'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']      = trim($_POST['name'] ?? '');
    $data['dob']       = trim($_POST['dob'] ?? '');
    $data['join_date'] = trim($_POST['join_date'] ?? '');
    $data['phone']     = trim($_POST['phone'] ?? '');
    $data['address']   = trim($_POST['address'] ?? '');

    // Basic PHP validation (format only — date logic delegated to SQL)
    if (empty($data['name']))       $errors[] = 'Patient name is required.';
    if (empty($data['dob']))        $errors[] = 'Date of Birth is required.';
    if (empty($data['join_date']))  $errors[] = 'Join date is required.';

    if (empty($errors)) {
        // ── SQL validates: DOB not in the future ──────────────────────────────
        $chk = $conn->query("
            SELECT
                CASE WHEN STR_TO_DATE('{$conn->real_escape_string($data['dob'])}','%Y-%m-%d') > CURDATE()
                     THEN 1 ELSE 0 END AS dob_future,
                CASE WHEN STR_TO_DATE('{$conn->real_escape_string($data['dob'])}','%Y-%m-%d') IS NULL
                     THEN 1 ELSE 0 END AS dob_invalid
        ")->fetch_assoc();

        if ($chk['dob_invalid'])  $errors[] = 'Invalid Date of Birth.';
        if ($chk['dob_future'])   $errors[] = 'Date of Birth cannot be in the future.';

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO patients (name, dob, join_date, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $data['name'], $data['dob'], $data['join_date'], $data['phone'], $data['address']);
            if ($stmt->execute()) {
                $new_id  = $stmt->insert_id;
                $success = true;
                $data    = ['name'=>'','dob'=>'','join_date'=>'','phone'=>'','address'=>''];
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
        }
    }
}
?>

<div class="page-header">
  <h1>Register New Patient</h1>
  <p>All date validations are performed by SQL</p>
</div>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
  <div class="alert alert-success">
    Patient registered successfully!
    <!-- POST to view.php instead of ?id= link -->
    <form method="POST" action="view.php" style="display:inline">
      <input type="hidden" name="id" value="<?= $new_id ?>">
      <button type="submit" style="background:none;border:none;color:var(--teal);cursor:pointer;font-weight:600;text-decoration:underline;padding:0">View record →</button>
    </form>
  </div>
<?php endif; ?>

<div class="card">
  <form method="POST" action="add.php">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" placeholder="e.g. Arjun Sharma" required>
      </div>
      <div class="form-group">
        <label>Date of Birth *</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($data['dob']) ?>" required>
        <span class="hint">Must be a past date. SQL validates this.</span>
      </div>
      <div class="form-group">
        <label>Join Date *</label>
        <input type="date" name="join_date" value="<?= htmlspecialchars($data['join_date']) ?>" required>
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($data['phone']) ?>" placeholder="9876543210">
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>Address</label>
        <textarea name="address" rows="3" placeholder="Full address..."><?= htmlspecialchars($data['address']) ?></textarea>
      </div>
    </div>
    <div class="btn-group">
      <button type="submit" class="btn btn-primary">Register Patient</button>
      <a href="list.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
