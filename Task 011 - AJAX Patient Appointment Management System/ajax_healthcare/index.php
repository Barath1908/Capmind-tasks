<?php
session_start();
require_once 'config.php';

// ── Generate CSRF token server-side ──────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_time']  = time();
}
$csrfToken = $_SESSION['csrf_token'];

// ── Fetch doctors server-side ─────────────────────────────────
$doctorOptions = '';
$result = $conn->query("SELECT id, name, specialty FROM doctors ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id        = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
        $name      = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        $specialty = htmlspecialchars($row['specialty'], ENT_QUOTES, 'UTF-8');
        $doctorOptions .= "<option value=\"{$id}\">{$name} ({$specialty})</option>";
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ClinicFlow — Patient Appointment System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles/styles.css" />
  </head>
  <body>
    <!-- ── Header ─────────────────────────────────────────────── -->
    <header class="site-header">
      <div class="logo">
        <div class="logo-mark">🏥</div>
        <div>
          <h1>ClinicFlow</h1>
          <p>Appointment Management</p>
        </div>
      </div>
      <div class="header-time">
        <strong id="clock"></strong>
        <span id="dateDisplay"></span>
      </div>
    </header>

    <!-- ── Toast container ────────────────────────────────────── -->
    <div id="toastContainer"></div>

    <!-- ── Main ───────────────────────────────────────────────── -->
    <main class="container">
      <!-- Stats -->
      <div class="stats-bar">
        <div class="stat-card">
          <div class="stat-icon blue">📋</div>
          <div>
            <div class="stat-label">Total</div>
            <div class="stat-value" id="statTotal">0</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">⏳</div>
          <div>
            <div class="stat-label">Pending</div>
            <div class="stat-value" id="statPending">0</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">✅</div>
          <div>
            <div class="stat-label">Confirmed</div>
            <div class="stat-value" id="statConfirmed">0</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">❌</div>
          <div>
            <div class="stat-label">Cancelled</div>
            <div class="stat-value" id="statCancelled">0</div>
          </div>
        </div>
      </div>

      <!-- Two-column layout -->
      <div class="page-grid">
        <!-- ── Left: Form ──────────────────────────────────── -->
        <aside>
          <div class="card">
            <div class="card-header">
              <span>📋</span>
              <h2 id="formTitle">New Appointment</h2>
              <span class="bonus-badge">✨ Bonus</span>
            </div>
            <div class="card-body">
              <form id="appointmentForm" novalidate>

                <!-- CSRF token — server-rendered hidden field -->
                <input type="hidden" id="csrf_token" name="csrf_token"
                       value="<?php echo $csrfToken; ?>" />

                <div class="form-group">
                  <label for="patient_name">Patient Name</label>
                  <input type="text" id="patient_name" placeholder="e.g. Arun Krishnan" autocomplete="name" />
                  <span class="field-error" id="err_patient_name"></span>
                </div>

                <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" placeholder="patient@email.com" autocomplete="email" />
                  <span class="field-error" id="err_email"></span>
                </div>

                <div class="form-group">
                  <label for="mobile">Mobile Number</label>
                  <input type="tel" id="mobile" placeholder="10-digit mobile number" autocomplete="tel" />
                  <span class="field-error" id="err_mobile"></span>
                </div>

                <!-- Doctor options rendered by PHP -->
                <div class="form-group">
                  <label for="doctor_id">Select Doctor</label>
                  <select id="doctor_id">
                    <option value="">— Select Doctor —</option>
                    <?php echo $doctorOptions; ?>
                  </select>
                  <span class="field-error" id="err_doctor_id"></span>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="appointment_date">Date</label>
                    <input type="date" id="appointment_date" />
                    <span class="field-error" id="err_appointment_date"></span>
                  </div>
                  <div class="form-group">
                    <label for="appointment_time">Time</label>
                    <input type="time" id="appointment_time" step="1800" />
                    <span class="field-error" id="err_appointment_time"></span>
                  </div>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">📅 Book Appointment</button>
                <button type="button" class="btn-cancel" id="cancelEditBtn">✕ Cancel Edit</button>
              </form>
            </div>
          </div>
        </aside>

        <!-- ── Right: Table ────────────────────────────────── -->
        <section>
          <div class="card">
            <div class="table-toolbar">
              <h2 style="font-family: var(--font-display); font-style: italic; font-size: 1.1rem; color: var(--slate-900)">📆 Appointments</h2>
              <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="search" id="searchInput" placeholder="Search patients, doctors…" />
              </div>
              <button class="btn-refresh" onclick="loadAppointments()">🔄 Refresh</button>
            </div>

            <!-- Loader -->
            <div id="tableLoader">
              <div class="spinner"></div>
              Loading appointments…
            </div>

            <!-- Table -->
            <div class="table-wrap">
              <table id="appointmentTable" style="display: none">
                <thead>
                  <tr>
                    <th>#ID</th>
                    <th>Patient</th>
                    <th>Mobile</th>
                    <th>Doctor</th>
                    <th>Date / Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="appointmentBody"></tbody>
              </table>

              <!-- Empty state -->
              <div id="emptyState">
                <div class="empty-icon">🗓️</div>
                <h3>No appointments found</h3>
                <p style="font-size: 0.82rem">Book the first appointment using the form.</p>
              </div>
            </div>
          </div>
        </section>
      </div>
      <!-- /page-grid -->
    </main>

    <footer>ClinicFlow &mdash; AJAX Healthcare Appointment System &bull; Task 011</footer>

    <script>
      // Live clock
      function updateClock() {
        const now = new Date();
        document.getElementById("clock").textContent = now.toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
        document.getElementById("dateDisplay").textContent = now.toLocaleDateString("en-IN", {
          weekday: "short",
          day: "numeric",
          month: "short",
          year: "numeric",
        });
      }
      updateClock();
      setInterval(updateClock, 1000);
    </script>
    <script src="app.js"></script>
  </body>
</html>
