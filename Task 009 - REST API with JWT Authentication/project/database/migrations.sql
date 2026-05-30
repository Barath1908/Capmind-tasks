-- ─────────────────────────────────────────────────────────────────────────────
-- database/migrations.sql
-- Run this file once to create the required tables.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS jwt_new_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE jwt_new_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id                    INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(100) NOT NULL,
    email                 VARCHAR(150) NOT NULL UNIQUE,
    password              VARCHAR(255) NOT NULL,
    token_version         INT          NOT NULL DEFAULT 0,
    refresh_token         VARCHAR(255)          DEFAULT NULL,
    refresh_token_expiry  DATETIME              DEFAULT NULL,
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Patients table (user_id links each patient to the user who created them)
CREATE TABLE IF NOT EXISTS patients (
    id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    name       VARCHAR(100) NOT NULL,
    age        INT          NOT NULL CHECK (age >= 0 AND age <= 150),
    gender     VARCHAR(10)  NOT NULL,
    phone      VARCHAR(20)           DEFAULT NULL,
    address    TEXT                  DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
