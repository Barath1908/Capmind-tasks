<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie (browser-side)
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  /*
  [
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true
  ]
  */
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
  );
}

// Destroy the server-side session
session_destroy();

// NOTE: Per task spec, do NOT delete remember_username or user_theme cookies.
// They remain so the next visit can auto-fill username and apply the theme.

// Redirect back to login page
header('Location: login.php');
exit;
