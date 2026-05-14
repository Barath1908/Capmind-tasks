<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HealthCore — Patient Visit &amp; Follow-Up Manager</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<style>
  :root {
    --teal:    #0d7377;
    --teal-lt: #14a085;
    --sage:    #e8f5f0;
    --navy:    #0f2027;
    --cream:   #faf9f7;
    --slate:   #4a5568;
    --border:  #dde5e0;
    --red:     #e53e3e;
    --amber:   #d69e2e;
    --green:   #2f855a;
    --shadow:  0 1px 3px rgba(13,115,119,.12), 0 4px 16px rgba(13,115,119,.08);
    --shadow-lg: 0 8px 32px rgba(13,115,119,.15);
    --radius:  10px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { font-size: 15px; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--navy);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  /* ── TOP NAV ── */
  nav {
    background: var(--navy);
    padding: 0 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(0,0,0,.3);
  }
  .nav-brand {
    font-family: 'DM Serif Display', serif;
    font-size: 1.4rem;
    color: #fff;
    text-decoration: none;
    padding: 1rem 0;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: .5rem;
  }
  .nav-brand span { color: var(--teal-lt); }
  .nav-links { display: flex; gap: .25rem; flex-wrap: wrap; flex: 1; }
  .nav-links a {
    color: rgba(255,255,255,.7);
    text-decoration: none;
    padding: .55rem .9rem;
    border-radius: 6px;
    font-size: .875rem;
    font-weight: 500;
    transition: all .2s;
    white-space: nowrap;
  }
  .nav-links a:hover, .nav-links a.active { background: var(--teal); color: #fff; }
  .nav-sep { color: rgba(255,255,255,.2); font-size: .8rem; padding: .55rem 0; }

  /* ── PAGE WRAPPER ── */
  .page-wrap { flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; padding: 2rem 1.5rem; }

  /* ── PAGE HEADER ── */
  .page-header { margin-bottom: 2rem; }
  .page-header h1 {
    font-family: 'DM Serif Display', serif;
    font-size: 2rem;
    color: var(--navy);
    margin-bottom: .3rem;
  }
  .page-header p { color: var(--slate); font-size: .95rem; }

  /* ── CARDS ── */
  .card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  .card-title {
    font-family: 'DM Serif Display', serif;
    font-size: 1.15rem;
    color: var(--teal);
    margin-bottom: 1rem;
    padding-bottom: .6rem;
    border-bottom: 2px solid var(--sage);
  }

  /* ── STAT CARDS ── */
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 1rem; margin-bottom: 1.5rem; }
  .stat-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.25rem 1.5rem;
    border-left: 4px solid var(--teal);
  }
  .stat-card .num { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--teal); }
  .stat-card .lbl { font-size: .8rem; color: var(--slate); margin-top: .2rem; text-transform: uppercase; letter-spacing: .06em; }
  .stat-card.amber { border-color: var(--amber); }
  .stat-card.amber .num { color: var(--amber); }
  .stat-card.red   { border-color: var(--red); }
  .stat-card.red   .num { color: var(--red); }
  .stat-card.green { border-color: var(--green); }
  .stat-card.green .num { color: var(--green); }

  /* ── TABLE ── */
  .tbl-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: .88rem; }
  thead th {
    background: var(--navy);
    color: #fff;
    padding: .7rem 1rem;
    text-align: left;
    font-weight: 500;
    font-size: .8rem;
    letter-spacing: .05em;
    text-transform: uppercase;
    white-space: nowrap;
  }
  tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
  tbody tr:hover { background: var(--sage); }
  tbody td { padding: .75rem 1rem; vertical-align: middle; }

  /* ── BADGES ── */
  .badge {
    display: inline-block;
    padding: .2rem .6rem;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
  }
  .badge-red    { background: #fff5f5; color: var(--red);   border: 1px solid #fed7d7; }
  .badge-amber  { background: #fffff0; color: var(--amber); border: 1px solid #fefcbf; }
  .badge-green  { background: #f0fff4; color: var(--green); border: 1px solid #c6f6d5; }
  .badge-teal   { background: var(--sage); color: var(--teal); border: 1px solid #b2dfdb; }
  .badge-slate  { background: #f7fafc; color: var(--slate); border: 1px solid var(--border); }

  /* ── FORMS ── */
  .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 1.2rem; }
  .form-group { display: flex; flex-direction: column; gap: .4rem; }
  .form-group label { font-size: .82rem; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: .05em; }
  .form-group input,
  .form-group select,
  .form-group textarea {
    border: 1.5px solid var(--border);
    border-radius: 7px;
    padding: .6rem .9rem;
    font-family: inherit;
    font-size: .9rem;
    color: var(--navy);
    transition: border-color .2s, box-shadow .2s;
    background: #fff;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--teal);
    box-shadow: 0 0 0 3px rgba(13,115,119,.12);
  }
  .form-group .hint { font-size: .75rem; color: #999; }

  /* ── BUTTONS ── */
  .btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .6rem 1.2rem;
    border-radius: 7px;
    font-family: inherit;
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all .2s;
    white-space: nowrap;
  }
  .btn-primary { background: var(--teal); color: #fff; }
  .btn-primary:hover { background: var(--teal-lt); box-shadow: 0 4px 12px rgba(13,115,119,.3); }
  .btn-outline { background: transparent; color: var(--teal); border: 1.5px solid var(--teal); }
  .btn-outline:hover { background: var(--teal); color: #fff; }
  .btn-sm { padding: .35rem .8rem; font-size: .8rem; }
  .btn-danger { background: var(--red); color: #fff; }
  .btn-danger:hover { background: #c53030; }
  .btn-amber { background: var(--amber); color: #fff; }
  .btn-amber:hover { background: #b7791f; }
  .btn-group { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1.2rem; }

  /* ── ALERTS ── */
  .alert { padding: .9rem 1.2rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
  .alert-success { background: #f0fff4; color: var(--green); border-left: 4px solid var(--green); }
  .alert-error   { background: #fff5f5; color: var(--red);   border-left: 4px solid var(--red); }
  .alert-warn    { background: #fffff0; color: var(--amber); border-left: 4px solid var(--amber); }
  .alert-info    { background: var(--sage); color: var(--teal); border-left: 4px solid var(--teal); }

  /* ── DETAIL ROWS ── */
  .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1rem; }
  .detail-item .lbl { font-size: .75rem; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: .05em; margin-bottom: .25rem; }
  .detail-item .val { font-size: .95rem; color: var(--navy); font-weight: 500; }

  /* ── EMPTY STATE ── */
  .empty { text-align: center; padding: 3rem 1rem; color: var(--slate); }
  .empty .ico { font-size: 2.5rem; margin-bottom: .75rem; }

  /* ── FOOTER ── */
  footer {
    background: var(--navy);
    color: rgba(255,255,255,.5);
    text-align: center;
    font-size: .78rem;
    padding: 1rem;
    margin-top: auto;
  }

  /* ── UTIL ── */
  .text-right { text-align: right; }
  .mt-1 { margin-top: .5rem; }
  .mt-2 { margin-top: 1rem; }
  .flex-between { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; }
  .section-tabs { display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
  .section-tabs a {
    padding: .5rem 1rem;
    border-radius: 20px;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
    background: #fff;
    color: var(--slate);
    border: 1.5px solid var(--border);
    transition: all .2s;
  }
  .section-tabs a:hover, .section-tabs a.active { background: var(--teal); color: #fff; border-color: var(--teal); }
</style>
</head>
<body>

<?php
/*
 * BASE URL DETECTION
 * Works whether the project is at localhost/ or localhost/healthcare_system/
 * Uses __DIR__ of the calling file (not header.php itself) to compute depth.
 *
 * Project root = the folder containing index.php
 * header.php lives in:  <root>/includes/
 * All other pages live in: <root>/, <root>/patients/, <root>/visits/, <root>/reports/
 *
 * We compute the root by going up from includes/ => root, then build the
 * relative URL from the calling file's directory back to root.
 */
$headerDir  = __DIR__;                         // .../healthcare_system/includes
$rootDir    = dirname($headerDir);             // .../healthcare_system
$callerDir  = dirname(realpath($_SERVER['SCRIPT_FILENAME'])); // calling file's dir

// Count how many levels up from callerDir to rootDir
$rel = '';
$dir = $callerDir;
while (rtrim($dir, '/\\') !== rtrim($rootDir, '/\\') && strlen($dir) > 3) {
    $rel .= '../';
    $dir = dirname($dir);
}
if ($rel === '') $rel = './';   // caller is already at root level
define('BASE', $rel);
?>

<nav>
  <a href="<?= BASE ?>index.php" class="nav-brand">🏥 <span>Health</span>Core</a>
  <div class="nav-links">
    <a href="<?= BASE ?>index.php">Dashboard</a>
    <span class="nav-sep">|</span>
    <a href="<?= BASE ?>patients/list.php">Patients</a>
    <a href="<?= BASE ?>patients/add.php">+ Patient</a>
    <span class="nav-sep">|</span>
    <a href="<?= BASE ?>visits/list.php">All Visits</a>
    <a href="<?= BASE ?>visits/add.php">+ Visit</a>
    <span class="nav-sep">|</span>
    <a href="<?= BASE ?>reports/followups.php">Follow-Ups</a>
    <a href="<?= BASE ?>reports/monthly.php">Monthly</a>
    <a href="<?= BASE ?>reports/birthdays.php">Birthdays</a>
    <a href="<?= BASE ?>reports/summary.php">Summary</a>
  </div>
</nav>

<div class="page-wrap">
