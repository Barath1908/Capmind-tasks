<?php

namespace App\Controllers;

use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\User;

/**
 * app/controllers/AuthController.php
 * Handles user registration and login.
 */
class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ─── POST /api/register ───────────────────────────────────────────────────

    /**
     * Register a new user.
     *
     * @param array<string, mixed> $request
     */
    public function register(array $request): void
    {
        $body = $request['body'] ?? [];

        $name     = trim($body['name']     ?? '');
        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        // ── Validation ────────────────────────────────────────────────────────
        if ($name === '' || $email === '' || $password === '') {
            Response::error('name, email, and password are required fields.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Please provide a valid email address.', 422);
        }

        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters long.', 422);
        }

        // ── Duplicate check ───────────────────────────────────────────────────
        if ($this->userModel->emailExists($email)) {
            Response::error('An account with this email already exists.', 409);
        }

        // ── Persist ───────────────────────────────────────────────────────────
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId         = $this->userModel->create($name, $email, $hashedPassword);

        Response::success(
            ['id' => $userId, 'name' => $name, 'email' => $email],
            'Registration successful.',
            201
        );
    }

    // ─── POST /api/login ──────────────────────────────────────────────────────

    /**
     * Authenticate a user and issue a JWT.
     *
     * @param array<string, mixed> $request
     */
    public function login(array $request): void
    {
        $body = $request['body'] ?? [];

        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        // ── Validation ────────────────────────────────────────────────────────
        if ($email === '' || $password === '') {
            Response::error('email and password are required.', 422);
        }

        // ── Lookup ────────────────────────────────────────────────────────────
        $user = $this->userModel->findByEmail($email);

        // Use a generic error message to prevent user-enumeration attacks
        if ($user === null || !password_verify($password, $user['password'])) {
            Response::error('Invalid email or password.', 401);
        }

        // ── Issue token ───────────────────────────────────────────────────────
        $token = JWT::generate(['id' => $user['id'], 'email' => $user['email']]);

        Response::success(
            [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => JWT_EXPIRY,
                'user'       => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                ],
            ],
            'Login successful.'
        );
    }
}
