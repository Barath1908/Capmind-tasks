-- ============================================================
--  AJAX Healthcare Appointment System — Database Setup
--  Database: clinic_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS clinic_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE clinic_db;

-- ─── Doctors table (Bonus: doctor selection) ────────────────
CREATE TABLE IF NOT EXISTS doctors (
  id          INT          NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  specialty   VARCHAR(100) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO doctors (name, specialty) VALUES
  ('Dr. Priya Sharma',   'General Physician'),
  ('Dr. Ravi Kumar',     'Cardiologist'),
  ('Dr. Meena Iyer',     'Dermatologist'),
  ('Dr. Arjun Nair',     'Orthopedist'),
  ('Dr. Sneha Pillai',   'Pediatrician');

-- ─── Appointments table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
  id               INT          NOT NULL AUTO_INCREMENT,
  patient_name     VARCHAR(100) NOT NULL,
  email            VARCHAR(100) NOT NULL,
  mobile           VARCHAR(20)  NOT NULL,
  doctor_id        INT          NOT NULL DEFAULT 1,
  appointment_date DATE         NOT NULL,
  appointment_time TIME         NOT NULL,
  status           VARCHAR(20)  NOT NULL DEFAULT 'Pending',
  csrf_token       VARCHAR(64)  DEFAULT NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_date_time     (appointment_date, appointment_time),
  INDEX idx_doctor_date   (doctor_id, appointment_date),
  CONSTRAINT fk_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Sample data ─────────────────────────────────────────────
INSERT INTO appointments (patient_name, email, mobile, doctor_id, appointment_date, appointment_time, status) VALUES
  ('Arun Krishnan',   'arun@email.com',   '9876543210', 1, CURDATE() + INTERVAL 1 DAY, '09:00:00', 'Confirmed'),
  ('Lakshmi Devi',    'lakshmi@email.com','9123456780', 2, CURDATE() + INTERVAL 2 DAY, '11:30:00', 'Pending'),
  ('Vikram Pandian',  'vikram@email.com', '9988776655', 3, CURDATE() + INTERVAL 3 DAY, '14:00:00', 'Pending');
