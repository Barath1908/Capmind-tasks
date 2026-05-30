<?php

namespace App\Controllers;

use App\Helpers\JWT;
use App\Helpers\RefreshToken;
use App\Helpers\Response;
use App\Models\User;

/**
 * app/controllers/AuthController.php
 * Handles user registration, login, token refresh, and logout.
 */
class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ─── POST /api/register ───────────────────────────────────────────────────

    public function register(array $request): void
    {
        $body = $request['body'] ?? [];

        $name     = trim($body['name']     ?? '');
        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            Response::error('name, email, and password are required fields.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Please provide a valid email address.', 422);
        }

        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters long.', 422);
        }

        if ($this->userModel->emailExists($email)) {
            Response::error('An account with this email already exists.', 409);
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId         = $this->userModel->create($name, $email, $hashedPassword);

        Response::success(
            ['id' => $userId, 'name' => $name, 'email' => $email],
            'Registration successful.',
            201
        );
    }

    // ─── POST /api/login ──────────────────────────────────────────────────────

    public function login(array $request): void
    {
        $body = $request['body'] ?? [];

        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::error('email and password are required.', 422);
        }

        

        // ── Lookup user ───────────────────────────────────────────────────────
        $user = $this->userModel->findByEmail($email);

        // ── Verify password BEFORE doing anything else ────────────────────────
        // $user must not be null before accessing $user['id']
        if ($user === null || !password_verify($password, $user['password'])) {
            Response::error('Invalid email or password.', 401);
        }

        // ── Invalidate all old access tokens ──────────────────────────────────
        $this->userModel->incrementTokenVersion($user['id']);

        // ── Generate refresh token → store in DB + set HttpOnly cookie ─────────
        $refreshToken = RefreshToken::generate();
        $expiry       = RefreshToken::expiryDatetime();

        $this->userModel->saveRefreshToken($user['id'], $refreshToken, $expiry);
        RefreshToken::setCookie($refreshToken);

        // ── Fetch updated user and issue new access token ─────────────────────
        $updatedUser = $this->userModel->findById($user['id']);

        $accessToken = JWT::generate([
            'id'            => $updatedUser['id'],
            'email'         => $updatedUser['email'],
            'token_version' => $updatedUser['token_version'],
        ]);

        Response::success(
            [
                'access_token' => $accessToken,
                'token_type'   => 'Bearer',
                'expires_in'   => JWT_EXPIRY,
                'user'         => [
                    'id'    => $updatedUser['id'],
                    'name'  => $updatedUser['name'],
                    'email' => $updatedUser['email'],
                ],
            ],
            'Login successful.'
        );
    }

    // ─── POST /api/token/refresh ──────────────────────────────────────────────

    public function refresh(array $request): void
    {
        // ── Read refresh token from HttpOnly cookie ───────────────────────────
        $refreshToken = RefreshToken::fromCookie();

        if ($refreshToken === null) {
            Response::error('Refresh token not found. Please login again.', 401);
        }

        // ── Validate against DB ───────────────────────────────────────────────
        $user = $this->userModel->findByRefreshToken($refreshToken);

        if ($user === null) {
            RefreshToken::clearCookie();
            Response::error('Refresh token is invalid or expired. Please login again.', 401);
        }

        // ── Rotate refresh token ──────────────────────────────────────────────
        $newRefreshToken = RefreshToken::generate();
        $newExpiry       = RefreshToken::expiryDatetime();

        $this->userModel->saveRefreshToken($user['id'], $newRefreshToken, $newExpiry);
        RefreshToken::setCookie($newRefreshToken);

        // ── Issue new access token ────────────────────────────────────────────
        $newAccessToken = JWT::generate([
            'id'            => $user['id'],
            'email'         => $user['email'],
            'token_version' => $user['token_version'],
        ]);

        Response::success(
            [
                'access_token' => $newAccessToken,
                'token_type'   => 'Bearer',
                'expires_in'   => JWT_EXPIRY,
            ],
            'Access token refreshed successfully.'
        );
    }

    // ─── POST /api/logout ─────────────────────────────────────────────────────

    public function logout(array $request): void
    {
        $userId = (int) $request['user']['user_id'];

        $this->userModel->incrementTokenVersion($userId);
        $this->userModel->clearRefreshToken($userId);
        RefreshToken::clearCookie();

        Response::success(null, 'Logged out successfully.');
    }
}