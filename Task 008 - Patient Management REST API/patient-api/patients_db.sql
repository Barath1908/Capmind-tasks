-- ──────────────────────────────────────────────────────────────────────────────
-- patients_db — Database setup script
-- Run once to initialise the schema.
-- ──────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS patients_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE patients_db;

CREATE TABLE IF NOT EXISTS patients (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)    NOT NULL,
    age        TINYINT UNSIGNED NOT NULL,          -- 0–255 covers all valid ages
    gender     ENUM('Male','Female','Other') NOT NULL,
    phone      VARCHAR(15)     NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_name  (name),
    INDEX idx_phone (phone)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ── Sample data (optional — remove in production) ─────────────────────────────
INSERT INTO patients (name, age, gender, phone) VALUES
    ('Arun Kumar',    35, 'Male',   '9876543210'),
    ('Priya Sharma',  28, 'Female', '9123456780'),
    ('Mohammed Ali',  52, 'Male',   '9988776655');
