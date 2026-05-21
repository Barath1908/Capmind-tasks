# Patient Management REST API

A secure, production-ready RESTful API built with **Core PHP + MySQL** — no frameworks required.

---

## Table of Contents
1. [Tech Stack](#tech-stack)
2. [Project Structure](#project-structure)
3. [Quick Start](#quick-start)
4. [API Reference](#api-reference)
5. [Security Design](#security-design)
6. [Response Format](#response-format)
7. [HTTP Status Codes](#http-status-codes)
8. [Postman Testing Guide](#postman-testing-guide)
9. [Validation Rules](#validation-rules)

---

## Tech Stack

| Layer       | Technology          |
|-------------|---------------------|
| Language    | Core PHP 8.1+       |
| Database    | MySQL 8 / MariaDB   |
| Web server  | Apache (XAMPP/WAMP) |
| Testing     | Postman             |
| Data format | JSON                |

---

## Project Structure

```
patient-api/
│
├── api/
│   ├── index.php                   ← Router / entry-point
│   │
│   ├── config/
│   │   └── database.php            ← Singleton DB connection
│   │
│   ├── controllers/
│   │   └── PatientController.php   ← HTTP → model dispatch
│   │
│   ├── models/
│   │   └── Patient.php             ← SQL + validation logic
│   │
│   ├── middlewares/
│   │   └── JsonMiddleware.php      ← Headers, CORS, body parsing
│   │
│   └── helpers/
│       └── Response.php            ← Uniform JSON output
│
├── hospital_db.sql                 ← DB schema + seed data
├── .htaccess                       ← URL rewriting + security
└── README.md
```

---

## Quick Start

### 1. Place the project

Copy `patient-api/` into your XAMPP/WAMP web root:

```
C:\xampp\htdocs\patient-api\   (Windows)
/var/www/html/patient-api/     (Linux)
```

### 2. Create the database

Open **phpMyAdmin** or any MySQL client and run:

```bash
mysql -u root -p < hospital_db.sql
```

### 3. Configure credentials (if needed)

Edit `api/config/database.php` — or set environment variables:

| Variable    | Default     | Description       |
|-------------|-------------|-------------------|
| `DB_HOST`   | `localhost` | MySQL host        |
| `DB_USER`   | `root`      | MySQL username    |
| `DB_PASS`   | *(empty)*   | MySQL password    |
| `DB_NAME`   | `hospital_db` | Database name  |

### 4. Enable Apache `mod_rewrite`

In `httpd.conf` ensure `AllowOverride All` is set for your htdocs directory and `mod_rewrite` is enabled.

### 5. Test

Open Postman and hit `GET http://localhost/patient-api/api/patients`.

---

## API Reference

Base URL: `http://localhost/patient-api`

> ⚠️ **No data is ever passed in the URL query-string.** IDs are path segments; all write data travels in the JSON body.

### Endpoints

| Method   | Endpoint                  | Description          |
|----------|---------------------------|----------------------|
| `GET`    | `/api/patients`           | List all patients    |
| `GET`    | `/api/patients/{id}`      | Get a patient by ID  |
| `POST`   | `/api/patients`           | Create a new patient |
| `PUT`    | `/api/patients/{id}`      | Update a patient     |
| `DELETE` | `/api/patients/{id}`      | Delete a patient     |

---

## Security Design

### No data in URLs
All create/update data is sent in the JSON body (`Content-Type: application/json`).  
IDs in the path are always integer-cast and range-validated before use.

### Prepared Statements
Every SQL query uses `mysqli` prepared statements with bound parameters — SQL injection is structurally impossible.

### Input Validation
All fields are validated (type, length, character set) in `Patient::validate()` before touching the database.  
Validation errors return **400 Bad Request** with a descriptive message.

### Error Handling
- Exceptions are caught at the controller level.  
- Internal details are logged server-side (`error_log`).  
- The client only ever receives a generic `"An unexpected error occurred."` for 500 errors — never stack traces.

### Headers
Every response includes:

```
Content-Type: application/json; charset=UTF-8
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Cache-Control: no-store
```

### Directory Protection
`.htaccess` blocks direct access to `config/`, `models/`, `controllers/`, `helpers/`, and `middlewares/`.

---

## Response Format

All responses follow this envelope:

```json
{
  "status": true,
  "message": "Human-readable description",
  "data": { }
}
```

`data` is `null` for error or no-payload responses (e.g. DELETE).

---

## HTTP Status Codes

| Code  | Meaning                                      |
|-------|----------------------------------------------|
| `200` | OK — request succeeded                       |
| `201` | Created — new patient inserted               |
| `400` | Bad Request — validation failed              |
| `404` | Not Found — patient / endpoint doesn't exist |
| `405` | Method Not Allowed                           |
| `500` | Internal Server Error                        |

---

## Postman Testing Guide

### Collection setup
Set a Postman variable `base_url = http://localhost/patient-api`.

### GET all patients
```
GET {{base_url}}/api/patients
```

### GET single patient
```
GET {{base_url}}/api/patients/1
```

### POST — Create patient
```
POST {{base_url}}/api/patients
Headers:  Content-Type: application/json
Body (raw JSON):
{
  "name":   "Arun Kumar",
  "age":    35,
  "gender": "Male",
  "phone":  "9876543210"
}
```

### PUT — Update patient
```
PUT {{base_url}}/api/patients/1
Headers:  Content-Type: application/json
Body (raw JSON):
{
  "age":   36,
  "phone": "9000000001"
}
```
Partial updates are supported — only the provided fields are changed.

### DELETE patient
```
DELETE {{base_url}}/api/patients/1
```

---

## Validation Rules

| Field    | Rules                                                           |
|----------|-----------------------------------------------------------------|
| `name`   | Required on create. Letters, spaces, `-`, `'`, `.` only. ≤ 100 chars. |
| `age`    | Required on create. Integer 0–150.                              |
| `gender` | Required on create. One of: `Male`, `Female`, `Other`.          |
| `phone`  | Required on create. 7–15 digits, optional leading `+`.          |

On `PUT`, all fields are optional (partial update). At least one field must be supplied.
