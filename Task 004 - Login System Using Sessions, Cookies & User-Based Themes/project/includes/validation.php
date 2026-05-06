<?php

/**
 * Validate username:
 * - 3 to 20 characters
 * - Only letters, numbers, and underscores
 */
function validateUsername(string $username): string {
    $username = trim($username);
    if (empty($username)) {
        return "Username is required.";
    }
    if (strlen($username) < 3 || strlen($username) > 20) {
        return "Username must be between 3 and 20 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return "Username can only contain letters, numbers, and underscores.";
    }
    return "";
}

/**
 * Validate email:
 * - Not empty
 * - Valid email format
 */
function validateEmail(string $email): string {
    $email = trim($email);
    if (empty($email)) {
        return "Email is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }
    return "";
}

/**
 * Validate password:
 * - At least 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one digit
 * - At least one special character
 */
function validatePassword(string $password): string {
    if (empty($password)) {
        return "Password is required.";
    }
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number.";
    }
    if (!preg_match('/[\W_]/', $password)) {
        return "Password must contain at least one special character (e.g. @, #, !).";
    }
    return "";
}
