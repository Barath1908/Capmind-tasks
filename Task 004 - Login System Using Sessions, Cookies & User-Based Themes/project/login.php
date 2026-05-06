<?php
session_start();

// Auto-fill username from cookie if it exists
$rememberedUsername = isset($_COOKIE['remember_username']) ? htmlspecialchars($_COOKIE['remember_username']) : '';
$rememberedEmail    = isset($_COOKIE['remember_email'])    ? htmlspecialchars($_COOKIE['remember_email'])    : '';


// Auto-apply theme from cookie if no session theme yet
$theme = 'light'; // default
if (isset($_SESSION['theme'])) {
    $theme = $_SESSION['theme'];
} elseif (isset($_COOKIE['user_theme'])) {
    $theme = $_COOKIE['user_theme'];
}

// Pull error messages from session and clear them
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

// Theme CSS mappings
$themeStyles = [
    'dark'  => ['bg' => '#0d0d0d', 'card' => '#1a1a2e', 'text' => '#e0e0e0', 'accent' => '#00d4ff', 'border' => '#2a2a4a', 'input_bg' => '#0f0f23', 'btn' => '#00d4ff', 'btn_text' => '#0d0d0d', 'label' => '#a0a0c0', 'shadow' => '0 0 40px rgba(0,212,255,0.15)'],
    'warm'  => ['bg' => '#2c1a0e', 'card' => '#3d2210', 'text' => '#f5e6d0', 'accent' => '#ff8c42', 'border' => '#6b3a1f', 'input_bg' => '#4a2b14', 'btn' => '#ff8c42', 'btn_text' => '#fff', 'label' => '#d4a57a', 'shadow' => '0 0 40px rgba(255,140,66,0.2)'],
    'light' => ['bg' => '#f0f4f8', 'card' => '#ffffff', 'text' => '#2d3748', 'accent' => '#4f46e5', 'border' => '#e2e8f0', 'input_bg' => '#f7fafc', 'btn' => '#4f46e5', 'btn_text' => '#fff', 'label' => '#718096', 'shadow' => '0 10px 40px rgba(79,70,229,0.12)'],
];

$t = $themeStyles[$theme] ?? $themeStyles['light'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SecureAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Sora:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles/login.css" rel="stylesheet">
    <style>
        :root {
            --bg:        <?= $t['bg'] ?>; 
            --card:      <?= $t['card'] ?>;
            --text:      <?= $t['text'] ?>;
            --accent:    <?= $t['accent'] ?>;
            --border:    <?= $t['border'] ?>;
            --input-bg:  <?= $t['input_bg'] ?>;
            --btn:       <?= $t['btn'] ?>;
            --btn-text:  <?= $t['btn_text'] ?>;
            --label:     <?= $t['label'] ?>;
            --shadow:    <?= $t['shadow'] ?>;
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h1>SecureAuth</h1>
        <p>Sign in to your account</p>
        <?php if ($theme !== 'light'): ?>
            <div class="theme-badge"><i class="bi bi-palette-fill"></i> <?= ucfirst($theme) ?> theme active</div>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST" novalidate>

            <!-- Username -->
            <div class="mb-field">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        value="<?= $rememberedUsername ?: htmlspecialchars($oldInput['username'] ?? '') ?>"
                        autocomplete="username"
                    >
                </div>
            </div>

            <!-- Email -->
            <div class="mb-field">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="you@example.com"
                        value="<?= $rememberedEmail ?: htmlspecialchars($oldInput['email'] ?? '') ?>"
                        autocomplete="email"
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="mb-field">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        name="password"
                        id="passwordField"
                        class="form-control pass-block"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                    <span class="input-group-text eye-box" style="cursor:pointer;" onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="d-flex align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe"
                        <?= !empty($rememberedUsername) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rememberMe">Remember me for 7 days</label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="hint-box">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Demo credentials:</strong><br>
            User1 (Dark): <strong>admin</strong> / admin@example.com / Admin@123<br>
            User2 (Warm): <strong>user2</strong> / user2@example.com / User2@123<br>
            User3 (Light): <strong>user3</strong> / user3@example.com / User3@123
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const field = document.getElementById('passwordField');
    const icon  = document.getElementById('eyeIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
