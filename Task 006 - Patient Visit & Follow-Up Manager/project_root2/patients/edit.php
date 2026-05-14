<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

$patient = $conn->query("SELECT * FROM patients WHERE patient_id = $id")->fetch_assoc();
if (!$patient) { echo "<div class='alert alert-error'>Patient not found.</div>"; require_once '../includes/footer.php'; exit; }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'save') {
    $name      = trim($_POST['name'] ?? '');
    $dob       = trim($_POST['dob'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if (empty($name))      $errors[] = 'Name is required.';
    if (empty($dob))       $errors[] = 'Date of Birth is required.';
    if (empty($join_date)) $errors[] = 'Join date is required.';

    if (empty($errors)) {
        // SQL date validation
        $chk = $conn->query("
            SELECT
                CASE WHEN STR_TO_DATE('{$conn->real_escape_string($dob)}','%Y-%m-%d') > CURDATE() THEN 1 ELSE 0 END AS dob_future
        ")->fetch_assoc();

        if ($chk['dob_future']) $errors[] = 'Date of Birth cannot be in the future.';

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE patients SET name=?, dob=?, join_date=?, phone=?, address=? WHERE patient_id=?");
            $stmt->bind_param('sssssi', $name, $dob, $join_date, $phone, $address, $id);
            if ($stmt->execute()) {
                $success = 'Patient record updated successfully.';
                $patient = $conn->query("SELECT * FROM patients WHERE patient_id = $id")->fetch_assoc();
            } else {
                $errors[] = 'Update failed: ' . $conn->error;
            }
        }
    }
}
?>

<div class="page-header">
  <h1>Edit Patient</h1>
  <p>Updating: <strong><?= htmlspecialchars($patient['name']) ?></strong></p>
</div>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
<?php if ($success): ?>
  <div class="alert alert-success">
    <?= $success ?>
    <!-- POST link to view.php -->
    <form method="POST" action="view.php" style="display:inline">
      <input type="hidden" name="id" value="<?= $id ?>">
      <button type="submit" style="background:none;border:none;color:var(--teal);cursor:pointer;font-weight:600;text-decoration:underline;padding:0">View →</button>
    </form>
  </div>
<?php endif; ?>

<div class="card">
  <form method="POST" action="edit.php">
    <!-- Carry the patient ID and mark this as a save action -->
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="_action" value="save">

    <div class="form-grid">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required>
      </div>
      <div class="form-group">
        <label>Date of Birth *</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($patient['dob']) ?>" required>
        <span class="hint">SQL validates: must be a past date.</span>
      </div>
      <div class="form-group">
        <label>Join Date *</label>
        <input type="date" name="join_date" value="<?= htmlspecialchars($patient['join_date']) ?>" required>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>">
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>Address</label>
        <textarea name="address" rows="3"><?= htmlspecialchars($patient['address']) ?></textarea>
      </div>
    </div>
    <div class="btn-group">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <!-- Cancel: POST back to view.php -->
      <form method="POST" action="view.php" style="display:inline">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="btn btn-outline">Cancel</button>
      </form>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
