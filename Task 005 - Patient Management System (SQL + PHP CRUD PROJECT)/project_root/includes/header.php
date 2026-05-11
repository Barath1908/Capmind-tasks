<?php
if (!defined('BASE_URL')) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $proto . '://' . $_SERVER['HTTP_HOST'] . '/' . basename(dirname(__DIR__)) . '/');
}
$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' : '' ?>MediTrack HMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav id="mainNav">
    <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
        <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
        <div><div class="brand-name">MediTrack</div><div class="brand-sub">Hospital Management</div></div>
    </a>
    <ul class="navbar-nav">
        <li><a class="nav-link <?= $cur==='list.php'?'active':'' ?>" href="<?= BASE_URL ?>patients/list.php"><i class="bi bi-people-fill me-1"></i>Patients</a></li>
    </ul>
</nav>
<?php if (!empty($_SESSION['success'])): ?>
<div class="alert-float alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?> <button class="btn-close" onclick="this.parentElement.remove()"></button></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert-float alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?> <button class="btn-close" onclick="this.parentElement.remove()"></button></div>
<?php unset($_SESSION['error']); endif; ?>
<div class="page-wrapper">
