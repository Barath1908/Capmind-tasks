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
        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
            margin: 0;
        }

        /* ── Top Navbar ── */
        .topbar {
            background: var(--sidebar);
            border-bottom: 1px solid var(--border);
            padding: 0.9rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-brand {
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex; align-items: center; gap: 0.6rem;
        }
        .brand-dot {
            width: 10px; height: 10px;
            background: var(--accent);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px var(--accent);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .user-pill {
            display: flex; align-items: center; gap: 0.6rem;
            background: var(--icon-bg);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 0.4rem 1rem 0.4rem 0.5rem;
            font-size: 0.85rem;
        }
        .user-avatar {
            width: 30px; height: 30px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--bg);
        }
        .theme-chip {
            display: inline-flex; align-items: center; gap: 0.35rem;
            background: var(--badge-bg);
            color: var(--badge-text);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .btn-logout {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.4rem;
        }
        .btn-logout:hover {
            background: #ef444420;
            border-color: #ef4444;
            color: #ef4444;
        }

        /* ── Main Content ── */
        .main { padding: 2rem; max-width: 1100px; margin: 0 auto; }

        /* ── Welcome Banner ── */
        .welcome-banner {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem 2.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease both;
        }
        .welcome-banner::before {
            content: '';
            position: absolute; top: 0; right: 0;
            width: 200px; height: 200px;
            background: radial-gradient(circle at center, color-mix(in srgb, var(--accent) 15%, transparent), transparent 70%);
            border-radius: 50%;
        }
        .welcome-banner h2 {
            font-family: 'Space Mono', monospace;
            font-size: 1.8rem;
            margin-bottom: 0.3rem;
        }
        .welcome-banner h2 span { color: var(--accent); }
        .welcome-banner p { color: var(--label); font-size: 0.9rem; }

        /* ── Stat Cards ── */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card {
            background: var(--stat-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.3rem 1.5rem;
            animation: fadeIn 0.6s ease both;
        }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .stat-icon {
            width: 40px; height: 40px;
            background: var(--icon-bg);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--accent);
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
        }
        .stat-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--label); margin-bottom: 0.2rem; }
        .stat-value { font-size: 1.1rem; font-weight: 600; }

        /* ── Session Details Card ── */
        .details-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.8rem 2rem;
            animation: fadeIn 0.7s ease both;
        }
        .details-card h5 {
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            color: var(--accent);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 1.2rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .detail-row {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-key {
            width: 200px;
            color: var(--label);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }
        .detail-val { font-weight: 500; }
        .detail-val code {
            background: var(--icon-bg);
            color: var(--accent);
            border-radius: 6px;
            padding: 0.2rem 0.6rem;
            font-family: 'Space Mono', monospace;
            font-size: 0.82rem;
        }
        .cookie-status {
            display: inline-flex; align-items: center; gap: 0.3rem;
            font-size: 0.8rem;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
        }
        .cookie-on  { background: #22c55e20; color: #22c55e; border: 1px solid #22c55e30; }
        .cookie-off { background: #ef444420; color: #ef4444; border: 1px solid #ef444430; }
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
