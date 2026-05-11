

-- Step 1: Create & use the database
CREATE DATABASE IF NOT EXISTS hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_db;

-- ============================================================
-- Step 2: Create the doctors table (needed for JOIN task)
-- ============================================================
CREATE TABLE IF NOT EXISTS doctors (
    id              INT          PRIMARY KEY AUTO_INCREMENT,
    doctor_name     VARCHAR(100) NOT NULL,
    specialization  VARCHAR(100) NOT NULL
);

-- ============================================================
-- Step 3: Create the patients table
-- ============================================================
CREATE TABLE IF NOT EXISTS patients (
    id             INT          PRIMARY KEY AUTO_INCREMENT,
    patient_name   VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    phone          VARCHAR(20)  NOT NULL,
    age            TINYINT UNSIGNED NOT NULL,
    gender         ENUM('Male','Female','Other') NOT NULL,
    diagnosis      VARCHAR(255) NOT NULL,
    doctor_id      INT          DEFAULT NULL,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- ============================================================
-- Step 4: Insert test data — doctors
-- ============================================================
INSERT INTO doctors (doctor_name, specialization) VALUES
    ('Dr. Arjun Sharma',   'Cardiology'),
    ('Dr. Priya Mehta',    'Neurology'),
    ('Dr. Suresh Rajan',   'Orthopedics'),
    ('Dr. Kavitha Nair',   'General Medicine'),
    ('Dr. Vikram Patel',   'Pulmonology');

-- ============================================================
-- Step 5: Insert test data — patients (at least 5)
-- ============================================================
INSERT INTO patients (patient_name, email, phone, age, gender, diagnosis, doctor_id) VALUES
    ('Ravi Kumar',       'ravi.kumar@email.com',     '9876543210', 45, 'Male',   'Hypertension',            1),
    ('Anitha Suresh',    'anitha.suresh@email.com',  '9876543211', 32, 'Female', 'Migraine',                2),
    ('Gopal Krishnan',   'gopal.k@email.com',        '9876543212', 60, 'Male',   'Arthritis',               3),
    ('Meena Devi',       'meena.devi@email.com',     '9876543213', 28, 'Female', 'Vitamin D Deficiency',    4),
    ('Selvan Murugan',   'selvan.m@email.com',       '9876543214', 52, 'Male',   'Chronic Bronchitis',      5),
    ('Lakshmi Prasad',   'lakshmi.p@email.com',      '9876543215', 38, 'Female', 'Diabetes Type 2',         1),
    ('Deepak Nair',      'deepak.nair@email.com',    '9876543216', 70, 'Male',   'Coronary Artery Disease', 1),
    ('Saranya Raj',      'saranya.raj@email.com',    '9876543217', 24, 'Female', 'Anxiety Disorder',        2),
    ('Balu Venkatesan',  'balu.v@email.com',         '9876543218', 55, 'Male',   'Lumbar Spondylosis',      3),
    ('Kaveri Srinivas',  'kaveri.s@email.com',       '9876543219', 41, 'Female', 'Hypothyroidism',         NULL);

