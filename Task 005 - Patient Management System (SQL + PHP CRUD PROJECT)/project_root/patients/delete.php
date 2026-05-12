<?php
session_start();
require_once '../config/db.php';
$id = (int)($_POST['id'] ?? 0);
if (!$id) { $_SESSION['error']='Invalid ID.'; header('Location:'.BASE_URL.'patients/list.php'); exit; }

$s = $conn->prepare('SELECT patient_name FROM patients WHERE id=?');
$s->bind_param('i',$id); $s->execute();
$row = $s->get_result()->fetch_assoc();
if (!$row) { $_SESSION['error']='Patient not found.'; header('Location:'.BASE_URL.'patients/list.php'); exit; }

$d = $conn->prepare('DELETE FROM patients WHERE id=?');
$d->bind_param('i',$id); $d->execute();
$_SESSION[$d->affected_rows ? 'success' : 'error'] = $d->affected_rows
    ? 'Patient "'.$row['patient_name'].'" deleted.'
    : 'Delete failed. Please try again.';
header('Location:'.BASE_URL.'patients/list.php');
exit;
