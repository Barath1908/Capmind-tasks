<?php
session_start();
require_once '../config/db.php';
$pageTitle = 'Edit Patient';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { $_SESSION['error']='Invalid ID.'; header('Location:'.BASE_URL.'patients/list.php'); exit; }

$f = $conn->prepare('SELECT * FROM patients WHERE id=?');
$f->bind_param('i',$id); $f->execute();
$patient = $f->get_result()->fetch_assoc();
if (!$patient) { $_SESSION['error']='Patient not found.'; header('Location:'.BASE_URL.'patients/list.php'); exit; }

$errors = []; $old = $patient;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['patient_name','email','phone','age','gender','diagnosis','doctor_id'] as $k)
        $old[$k] = trim($_POST[$k] ?? '');

    if (strlen($old['patient_name']) < 2) $errors['patient_name'] = 'Name must be at least 2 characters.';
    if (!filter_var($old['email'],FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email.';
    } else {
        $c = $conn->prepare('SELECT id FROM patients WHERE email=? AND id!=?');
        $c->bind_param('si',$old['email'],$id); $c->execute();
        if ($c->get_result()->num_rows) $errors['email'] = 'Email already used by another patient.';
    }
    if (!preg_match('/^[6-9]\d{9}$/',$old['phone']))               $errors['phone']     = 'Enter a valid 10-digit mobile number.';
    if (!is_numeric($old['age'])||$old['age']<1||$old['age']>130)  $errors['age']       = 'Age must be 1–130.';
    if (!in_array($old['gender'],['Male','Female','Other']))        $errors['gender']    = 'Please select a gender.';
    if ($old['diagnosis']==='')                                     $errors['diagnosis'] = 'Diagnosis is required.';

    if (!$errors) {
        $did = $old['doctor_id']!=='' ? (int)$old['doctor_id'] : null;
        $s = $conn->prepare('UPDATE patients SET patient_name=?,email=?,phone=?,age=?,gender=?,diagnosis=?,doctor_id=? WHERE id=?');
        $s->bind_param('ssisssii',$old['patient_name'],$old['email'],$old['phone'],$old['age'],$old['gender'],$old['diagnosis'],$did,$id);
        if ($s->execute()) { $_SESSION['success']='Patient "'.$old['patient_name'].'" updated!'; header('Location:'.BASE_URL.'patients/list.php'); exit; }
        $errors['db'] = 'DB error: '.$conn->error;
    }
}
$doctors = $conn->query('SELECT id,doctor_name,specialization FROM doctors ORDER BY doctor_name');
require_once '../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><span class="header-icon"><i class="bi bi-pencil-square"></i></span> Edit Patient</h1>
        <ol class="breadcrumb"><li class="breadcrumb-item"><a href="list.php">Patients</a></li><li class="breadcrumb-item active">Edit #<?= $id ?></li></ol>
    </div>
    <span class="text-muted" style="font-size:.8rem"><i class="bi bi-clock me-1"></i>Registered: <?= date('d M Y',strtotime($patient['created_at'])) ?></span>
</div>
<div class="form-page"><div class="card">
    <div class="card-header-custom" style="background:linear-gradient(135deg,#fffbeb,#fff);border-color:#fde68a;color:#92400e">
        <i class="bi bi-pencil-fill"></i> Editing: <?= htmlspecialchars($patient['patient_name']) ?>
    </div>
    <div class="card-body p-4">
        <?php if(!empty($errors['db'])):?><div class="alert-inline alert-danger"><?= $errors['db'] ?></div><?php endif;?>
        <form method="POST" novalidate>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-grid">
                <?php
                $fields = [
                    ['patient_name','text','Patient Name','Full name'],
                    ['email','email','Email Address','patient@example.com'],
                    ['diagnosis','text','Diagnosis','e.g. Hypertension'],
                ];
                foreach ($fields as [$fk,$t,$lbl,$ph]): $err=$errors[$fk]??''; ?>
                <div class="field-group">
                    <label class="form-label"><?= $lbl ?> <span class="req">*</span></label>
                    <input type="<?= $t ?>" name="<?= $fk ?>" class="form-control <?= $err?'is-invalid':'' ?>"
                           value="<?= htmlspecialchars($old[$fk]) ?>" placeholder="<?= $ph ?>">
                    <?php if($err):?><div class="invalid-feedback"><?= $err ?></div><?php endif;?>
                </div>
                <?php endforeach; ?>

                <div class="field-group">
                    <label class="form-label">Phone Number <span class="req">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">+91</span>
                        <input type="text" name="phone" class="form-control <?= isset($errors['phone'])?'is-invalid':'' ?>"
                               value="<?= htmlspecialchars($old['phone']) ?>" placeholder="10-digit mobile" maxlength="10">
                    </div>
                    <?php if(!empty($errors['phone'])):?><div class="invalid-feedback"><?= $errors['phone'] ?></div><?php endif;?>
                </div>

                <div class="field-group">
                    <label class="form-label">Age <span class="req">*</span></label>
                    <input type="number" name="age" class="form-control <?= isset($errors['age'])?'is-invalid':'' ?>"
                           value="<?= htmlspecialchars($old['age']) ?>" placeholder="Years" min="1" max="130">
                    <?php if(!empty($errors['age'])):?><div class="invalid-feedback"><?= $errors['age'] ?></div><?php endif;?>
                </div>

                <div class="field-group">
                    <label class="form-label">Gender <span class="req">*</span></label>
                    <select name="gender" class="form-select <?= isset($errors['gender'])?'is-invalid':'' ?>">
                        <option value="">Select…</option>
                        <?php foreach(['Male','Female','Other'] as $g): ?>
                        <option <?= $old['gender']===$g?'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(!empty($errors['gender'])):?><div class="invalid-feedback"><?= $errors['gender'] ?></div><?php endif;?>
                </div>

                <div class="field-group span-2">
                    <label class="form-label">Assigned Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">— Not assigned —</option>
                        <?php while($d=$doctors->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>" <?= $old['doctor_id']==$d['id']?'selected':'' ?>>
                            <?= htmlspecialchars($d['doctor_name']) ?> (<?= htmlspecialchars($d['specialization']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <a href="list.php" class="btn-outline"><i class="bi bi-x-circle me-1"></i>Cancel</a>
                <button class="btn-teal" style="background:linear-gradient(135deg,#b45309,#92400e)">
                    <i class="bi bi-save-fill me-1"></i>Update Patient
                </button>
            </div>
        </form>
    </div>
</div></div>
<?php require_once '../includes/footer.php'; ?>
