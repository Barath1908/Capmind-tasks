# 🏥 Hospital REST API — PHP + JWT Authentication

A secure REST API built with **Core PHP** following the **MVC architecture**, featuring JWT-based authentication, middleware-protected routes, and PDO database access.

---

## 📁 Project Structure

```
project/
├── app/
│   ├── controllers/
│   │   ├── AuthController.php       # register & login
│   │   └── PatientController.php    # CRUD for patients
│   ├── models/
│   │   ├── User.php                 # users table queries
│   │   └── Patient.php              # patients table queries
│   ├── middleware/
│   │   ├── JsonMiddleware.php       # validates JSON body
│   │   └── AuthMiddleware.php       # validates JWT token
│   ├── helpers/
│   │   ├── JWT.php                  # HS256 token generator/validator
│   │   └── Response.php             # JSON response builder
│   └── core/
│       ├── Database.php             # singleton PDO wrapper
│       └── Router.php               # method + URI dispatcher
├── config/
│   └── config.php                   # loads .env, defines constants
├── public/
│   └── index.php                    # single entry point
├── database/
│   └── migrations.sql               # table creation SQL
├── .env                             # secrets (never commit)
├── .gitignore
└── .htaccess                        # rewrites all requests → index.php
```

---

## ⚙️ Setup

### 1. Clone & configure environment

```bash
git clone <repo-url>
cd project
cp .env.example .env   # or edit .env directly
```

Edit `.env`:
```dotenv
DB_HOST=localhost
DB_NAME=hospital_db
DB_USER=root
DB_PASS=your_db_password

JWT_SECRET=replace_with_a_long_random_string
JWT_EXPIRY=3600
```

> ⚠️ **Never commit `.env` to Git.** It is listed in `.gitignore`.

### 2. Create the database

```bash
mysql -u root -p < database/migrations.sql
```

### 3. Run the server

```bash
php -S localhost:8000 -t public
```

Or configure Apache/Nginx with the provided `.htaccess`.

---

## 🔐 Authentication Flow

```
Client                        API
  │                             │
  │── POST /api/register ──────▶│  Hash password, store user
  │◀── 201 { user } ────────────│
  │                             │
  │── POST /api/login ─────────▶│  Verify password_verify()
  │◀── 200 { token } ───────────│  Return signed JWT
  │                             │
  │── GET /api/patients ────────▶│  AuthMiddleware validates JWT
  │   Authorization: Bearer <t> │
  │◀── 200 { patients } ────────│
```

---

## 📡 API Endpoints

### Auth

| Method | Endpoint        | Auth Required | Description   |
|--------|-----------------|:-------------:|---------------|
| POST   | /api/register   | ❌            | Create account |
| POST   | /api/login      | ❌            | Get JWT token  |

### Patients

| Method | Endpoint              | Auth Required | Description        |
|--------|-----------------------|:-------------:|--------------------|
| GET    | /api/patients         | ✅            | List all patients  |
| GET    | /api/patients/{id}    | ✅            | Get one patient    |
| POST   | /api/patients         | ✅            | Create patient     |
| PUT    | /api/patients/{id}    | ✅            | Update patient     |
| DELETE | /api/patients/{id}    | ✅            | Delete patient     |

---

## 🧪 Postman / Thunder Client Testing

### 1. Register

```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123"
}
```

**Success (201):**
```json
{
  "status": "success",
  "message": "Registration successful.",
  "data": { "id": 1, "name": "John Doe", "email": "john@example.com" }
}
```

**Duplicate email (409):**
```json
{ "status": "error", "message": "An account with this email already exists.", "data": null }
```

---

### 2. Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Success (200):**
```json
{
  "status": "success",
  "message": "Login successful.",
  "data": {
    "token": "eyJhbGci...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": { "id": 1, "name": "John Doe", "email": "john@example.com" }
  }
}
```

**Wrong password (401):**
```json
{ "status": "error", "message": "Invalid email or password.", "data": null }
```

---

### 3. Get All Patients (Protected)

```http
GET /api/patients
Authorization: Bearer <your_token_here>
```

**No token (401):**
```json
{ "status": "error", "message": "Authorization header is missing.", "data": null }
```

---

### 4. Create Patient

```http
POST /api/patients
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Jane Smith",
  "age": 34,
  "gender": "female",
  "phone": "9876543210",
  "address": "123 Main Street, Bengaluru"
}
```

---

### 5. Update Patient

```http
PUT /api/patients/1
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Jane Smith",
  "age": 35,
  "gender": "female",
  "phone": "9876543210",
  "address": "456 New Road, Bengaluru"
}
```

---

### 6. Delete Patient

```http
DELETE /api/patients/1
Authorization: Bearer <token>
```

---

## 🔒 Security Notes

| Practice | Implementation |
|---|---|
| Password hashing | `password_hash($pass, PASSWORD_DEFAULT)` (bcrypt) |
| Password verification | `password_verify($plain, $hash)` |
| JWT signature | HMAC-SHA256 with secret from `.env` |
| Token comparison | `hash_equals()` — prevents timing attacks |
| Generic auth errors | `"Invalid email or password."` — prevents user enumeration |
| SQL injection | PDO prepared statements throughout |
| Secrets | Loaded from `.env`, never hardcoded |

---

## 🏗️ Architecture Decisions

- **Single entry point** — `public/index.php` handles routing, keeping web root clean
- **Singleton Database** — one PDO connection per request, no connection pooling overhead
- **Middleware chain** — `JsonMiddleware → AuthMiddleware → Controller`, each stage can abort early
- **No framework** — pure PHP to understand what frameworks abstract away
