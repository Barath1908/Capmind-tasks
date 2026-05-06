<?php
session_start();
require_once 'includes/validation.php';
 
// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}
 
// ── 1. Receive Form Data ──────────────────────────────────────────────────────
$username   = trim($_POST['username']   ?? '');
$email      = trim($_POST['email']      ?? '');
$password   = trim($_POST['password']   ?? '');
$rememberMe = isset($_POST['remember_me']);
 
// ── 2. Validate Inputs ────────────────────────────────────────────────────────
$errors = [];
 
$usernameError = validateUsername($username);
if ($usernameError) $errors[] = $usernameError;

/*
if ($usernameError != "") {
    $errors[] = $usernameError;
}
*/
 
$emailError = validateEmail($email);
if ($emailError) $errors[] = $emailError;
 
$passwordError = validatePassword($password);
if ($passwordError) $errors[] = $passwordError;
 
if (!empty($errors)) {
    $_SESSION['errors']    = $errors;
    $_SESSION['old_input'] = ['username' => $username, 'email' => $email];
    header('Location: login.php');
    exit;
}
 
// ── 3. Dummy User Store ───────────────────────────────────────────────────────
$users = [
    [
        'user_id'  => 1,
        'username' => 'admin',
        'email'    => 'admin@example.com',
        'password' => 'Admin@123',
        'theme'    => 'dark',
    ],
    [
        'user_id'  => 2,
        'username' => 'user2',
        'email'    => 'user2@example.com',
        'password' => 'User2@123',
        'theme'    => 'warm',
    ],
    [
        'user_id'  => 3,
        'username' => 'user3',
        'email'    => 'user3@example.com',
        'password' => 'User3@123',
        'theme'    => 'light',
    ],
];
 
// ── 4. Authenticate ───────────────────────────────────────────────────────────
$matchedUser = null;
foreach ($users as $user) {
    if (
        strtolower($user['username']) === strtolower($username) &&
        strtolower($user['email'])    === strtolower($email)    &&
        $user['password']             === $password
    ) {
        $matchedUser = $user;
        break;
    }
}
 
if (!$matchedUser) {
    $_SESSION['errors']    = ['Invalid username, email, or password. Please try again.'];
    $_SESSION['old_input'] = ['username' => $username, 'email' => $email];
    header('Location: login.php');
    exit;
}
 
// ── 5. Theme Assignment ───────────────────────────────────────────────────────
$theme = $matchedUser['theme'] ?? 'light';
 
// ── 6. Create Session ─────────────────────────────────────────────────────────
$_SESSION['user_id']  = $matchedUser['user_id'];
$_SESSION['username'] = $matchedUser['username'];
$_SESSION['email']    = $matchedUser['email'];
$_SESSION['theme']    = $theme;
 
// ── 7. Cookie Logic ───────────────────────────────────────────────────────────
// Use 60 seconds for testing (as per task spec). Change to time() + 7*24*3600 for production.
$cookieExpiry = time() + 60; // 60 sec for testing
 
if ($rememberMe) {
    // Remember Me checked: save both username and theme cookies
    // setcookie(name, value, expiry, path, domain, secure, httponly);
    setcookie('remember_username', $matchedUser['username'], $cookieExpiry, '/', '', false, true);
    setcookie('remember_email',    $matchedUser['email'],    $cookieExpiry, '/', '', false, true);
    setcookie('user_theme',        $theme,                   $cookieExpiry, '/', '', false, true);
} else {
    // Remember Me NOT checked: clear both cookies so login page returns to default light theme
    setcookie('remember_username', '', time() - 3600, '/');
    setcookie('remember_email',    '', time() - 3600, '/');
    setcookie('user_theme',        '', time() - 3600, '/');
}
 
// ── 8. Redirect to Dashboard ──────────────────────────────────────────────────
header('Location: dashboard.php');
exit;
 