<?php
session_start();

// ── Protect Dashboard ─────────────────────────────────────────────────────────
if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// ── Read Session Data ─────────────────────────────────────────────────────────
$userId   = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$email    = htmlspecialchars($_SESSION['email']);
$theme    = $_SESSION['theme'] ?? 'light';

// ── Theme Styles ──────────────────────────────────────────────────────────────
$themeStyles = [
    'dark' => [
        'bg'         => '#0d0d0d',
        'sidebar'    => '#111122',
        'card'       => '#1a1a2e',
        'text'       => '#e0e0e0',
        'accent'     => '#00d4ff',
        'border'     => '#2a2a4a',
        'label'      => '#6b7db3',
        'badge_bg'   => '#00d4ff22',
        'badge_text' => '#00d4ff',
        'icon_bg'    => '#00d4ff18',
        'stat_bg'    => '#12122a',
        'name'       => 'Dark Mode',
        'icon'       => '🌑',
    ],
    'warm' => [
        'bg'         => '#2c1a0e',
        'sidebar'    => '#35200f',
        'card'       => '#3d2210',
        'text'       => '#f5e6d0',
        'accent'     => '#ff8c42',
        'border'     => '#6b3a1f',
        'label'      => '#b87350',
        'badge_bg'   => '#ff8c4222',
        'badge_text' => '#ff8c42',
        'icon_bg'    => '#ff8c4218',
        'stat_bg'    => '#2a1808',
        'name'       => 'Warm Mode',
        'icon'       => '🌅',
    ],
    'light' => [
        'bg'         => '#f0f4f8',
        'sidebar'    => '#ffffff',
        'card'       => '#ffffff',
        'text'       => '#2d3748',
        'accent'     => '#4f46e5',
        'border'     => '#e2e8f0',
        'label'      => '#718096',
        'badge_bg'   => '#4f46e515',
        'badge_text' => '#4f46e5',
        'icon_bg'    => '#4f46e510',
        'stat_bg'    => '#f7fafc',
        'name'       => 'Light Mode',
        'icon'       => '☀️',
    ],
];

$t = $themeStyles[$theme] ?? $themeStyles['light'];

// Session started time (simulate)
$sessionTime = date('H:i:s');
$sessionDate = date('D, d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SecureAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Sora:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles/dashboard.css" rel="stylesheet">
    <style>
        :root {
            --bg:         <?= $t['bg'] ?>;
            --sidebar:    <?= $t['sidebar'] ?>;
            --card:       <?= $t['card'] ?>;
            --text:       <?= $t['text'] ?>;
            --accent:     <?= $t['accent'] ?>;
            --border:     <?= $t['border'] ?>;
            --label:      <?= $t['label'] ?>;
            --badge-bg:   <?= $t['badge_bg'] ?>;
            --badge-text: <?= $t['badge_text'] ?>;
            --icon-bg:    <?= $t['icon_bg'] ?>;
            --stat-bg:    <?= $t['stat_bg'] ?>;
        }
    </style>
</head>
<body>

<!-- ── Top Bar ── -->
<nav class="topbar">
    <div class="topbar-brand">
        <span class="brand-dot"></span> SecureAuth
    </div>
    <div class="topbar-right">
        <div class="theme-chip">
            <?= $t['icon'] ?> <?= $t['name'] ?>
        </div>
        <div class="user-pill">
            <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
            <?= $username ?>
        </div>
        <a href="logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<!-- ── Main ── -->
<div class="main">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <h2>Welcome back, <span><?= $username ?></span> 👋</h2>
        <p>You are logged in securely. Your session is active and protected.</p>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-person-check"></i></div>
            <div class="stat-label">User ID</div>
            <div class="stat-value">#<?= $userId ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
            <div class="stat-label">Login Date</div>
            <div class="stat-value"><?= $sessionDate ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-clock"></i></div>
            <div class="stat-label">Session Time</div>
            <div class="stat-value"><?= $sessionTime ?></div>
        </div>
    </div>

    <!-- Session Details -->
    <div class="details-card">
        <h5><i class="bi bi-database"></i> Active Session Details</h5>

        <div class="detail-row">
            <div class="detail-key">$_SESSION['user_id']</div>
            <div class="detail-val"><code><?= $userId ?></code></div>
        </div>
        <div class="detail-row">
            <div class="detail-key">$_SESSION['username']</div>
            <div class="detail-val"><code><?= $username ?></code></div>
        </div>
        <div class="detail-row">
            <div class="detail-key">$_SESSION['email']</div>
            <div class="detail-val"><code><?= $email ?></code></div>
        </div>
        <div class="detail-row">
            <div class="detail-key">$_SESSION['theme']</div>
            <div class="detail-val"><code><?= $theme ?></code></div>
        </div>
        <div class="detail-row">
            <div class="detail-key">Cookie: username</div>
            <div class="detail-val">
                <?php if (isset($_COOKIE['remember_username'])): ?>
                    <span class="cookie-status cookie-on"><i class="bi bi-check-circle-fill"></i> Set — <?= htmlspecialchars($_COOKIE['remember_username']) ?></span>
                <?php else: ?>
                    <span class="cookie-status cookie-off"><i class="bi bi-x-circle-fill"></i> Not set</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-key">Cookie: theme</div>
            <div class="detail-val">
                <?php if (isset($_COOKIE['user_theme'])): ?>
                    <span class="cookie-status cookie-on"><i class="bi bi-check-circle-fill"></i> Set — <?= htmlspecialchars($_COOKIE['user_theme']) ?></span>
                <?php else: ?>
                    <span class="cookie-status cookie-off"><i class="bi bi-x-circle-fill"></i> Not set</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-key">Session ID</div>
            <div class="detail-val"><code><?= session_id() ?></code></div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
