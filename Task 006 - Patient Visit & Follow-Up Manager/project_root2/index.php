<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// ── SQL: Dashboard summary stats (all via SQL) ──────────────────────────────
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM patients)                                          AS total_patients,
        (SELECT COUNT(*) FROM visits)                                            AS total_visits,
        (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE())            AS overdue_followups,
        (SELECT COUNT(*) FROM visits
            WHERE follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY))
                                                                                 AS upcoming_followups,
        (SELECT COUNT(DISTINCT p.patient_id) FROM patients p
            LEFT JOIN visits v ON p.patient_id = v.patient_id
            WHERE v.visit_id IS NULL)                                            AS no_visit_patients,
        (SELECT IFNULL(SUM(consultation_fee + lab_fee),0) FROM visits
            WHERE YEAR(visit_date) = YEAR(CURDATE())
            AND MONTH(visit_date) = MONTH(CURDATE()))                            AS revenue_this_month,
        (SELECT COUNT(*) FROM patients
            WHERE MONTH(dob) = MONTH(CURDATE()) AND DAY(dob) = DAY(CURDATE()))  AS birthdays_today,
        CURDATE()                                                                AS today
")->fetch_assoc();

// ── SQL: Monthly visit trend (last 6 months) ────────────────────────────────
$trend = $conn->query("
    SELECT
        DATE_FORMAT(v.visit_date, '%b %Y')                      AS month_label,
        YEAR(v.visit_date)                                       AS yr,
        MONTH(v.visit_date)                                      AS mo,
        COUNT(*)                                                 AS visit_count,
        IFNULL(SUM(v.consultation_fee + v.lab_fee), 0)          AS total_revenue
    FROM visits v
    WHERE v.visit_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH),'%Y-%m-01')
    GROUP BY YEAR(v.visit_date), MONTH(v.visit_date)
    ORDER BY yr, mo
");

// ── SQL: Recent visits (last 10) ────────────────────────────────────────────
$recent = $conn->query("
    SELECT
        v.visit_id,
        p.name,
        p.patient_id,
        DATE_FORMAT(v.visit_date,'%d %b %Y')                    AS visit_date_fmt,
        DATEDIFF(CURDATE(), v.visit_date)                        AS days_ago,
        v.consultation_fee + v.lab_fee                           AS total_fee,
        DATE_FORMAT(v.follow_up_due,'%d %b %Y')                 AS follow_up_fmt,
        CASE
            WHEN v.follow_up_due < CURDATE()                     THEN 'overdue'
            WHEN v.follow_up_due <= DATE_ADD(CURDATE(),INTERVAL 7 DAY) THEN 'upcoming'
            ELSE 'scheduled'
        END                                                      AS follow_status
    FROM visits v
    JOIN patients p ON p.patient_id = v.patient_id
    ORDER BY v.visit_date DESC
    LIMIT 10
");

// ── SQL: Patients with upcoming birthdays (next 30 days) ───────────────────
$birthdays = $conn->query("
    SELECT
        name,
        patient_id,
        DATE_FORMAT(dob,'%d %b')                                 AS bday_short,
        TIMESTAMPDIFF(YEAR, dob, CURDATE())                      AS current_age,
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) + 1                  AS turning_age,
        DATEDIFF(
            DATE(CONCAT(YEAR(CURDATE()),'-',MONTH(dob),'-',DAY(dob))),
            CURDATE()
        )                                                        AS days_away
    FROM patients
    WHERE
        DAYOFYEAR(DATE(CONCAT(YEAR(CURDATE()),'-',LPAD(MONTH(dob),2,'0'),'-',LPAD(DAY(dob),2,'0'))))
        BETWEEN DAYOFYEAR(CURDATE()) AND DAYOFYEAR(CURDATE()) + 30
    ORDER BY days_away
    LIMIT 5
");

$trend_rows = [];
while ($r = $trend->fetch_assoc()) $trend_rows[] = $r;

// Pre-fetch recent rows and birthday rows so we can loop twice if needed
$recent_rows = [];
while ($row = $recent->fetch_assoc()) $recent_rows[] = $row;

$birthday_rows = [];
while ($b = $birthdays->fetch_assoc()) $birthday_rows[] = $b;
?>

<div class="page-header">
  <h1>Dashboard</h1>
  <p>Today: <strong><?= htmlspecialchars($stats['today']) ?></strong> &mdash; Healthcare overview at a glance</p>
</div>

<!-- ── STAT CARDS ── -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="num"><?= $stats['total_patients'] ?></div>
    <div class="lbl">Total Patients</div>
  </div>
  <div class="stat-card green">
    <div class="num"><?= $stats['total_visits'] ?></div>
    <div class="lbl">Total Visits</div>
  </div>
  <div class="stat-card red">
    <div class="num"><?= $stats['overdue_followups'] ?></div>
    <div class="lbl">Overdue Follow-Ups</div>
  </div>
  <div class="stat-card amber">
    <div class="num"><?= $stats['upcoming_followups'] ?></div>
    <div class="lbl">Follow-Ups This Week</div>
  </div>
  <div class="stat-card">
    <div class="num"><?= $stats['no_visit_patients'] ?></div>
    <div class="lbl">No Visits Yet</div>
  </div>
  <div class="stat-card green">
    <div class="num">₹<?= number_format($stats['revenue_this_month'], 0) ?></div>
    <div class="lbl">Revenue This Month</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

  <!-- ── RECENT VISITS TABLE ── -->
  <div class="card">
    <div class="flex-between" style="margin-bottom:1rem">
      <div class="card-title" style="margin:0;border:0;padding:0">Recent Visits</div>
      <a href="visits/list.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Visit Date</th>
            <th>Days Ago</th>
            <th>Fee (₹)</th>
            <th>Follow-Up Due</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_rows as $row): ?>
          <tr>
            <td>
              <!-- Patient name → POST to patients/view.php -->
              <form method="POST" action="patients/view.php" style="display:inline">
                <input type="hidden" name="id" value="<?= $row['patient_id'] ?>">
                <button type="submit" style="background:none;border:none;padding:0;color:var(--teal);font-weight:600;cursor:pointer;font-family:inherit;font-size:inherit"><?= htmlspecialchars($row['name']) ?></button>
              </form>
            </td>
            <td><?= $row['visit_date_fmt'] ?></td>
            <td><?= $row['days_ago'] ?> d</td>
            <td>₹<?= number_format($row['total_fee'], 0) ?></td>
            <td><?= $row['follow_up_fmt'] ?></td>
            <td>
              <?php
              $cls = ['overdue'=>'badge-red','upcoming'=>'badge-amber','scheduled'=>'badge-green'];
              $lbl = ['overdue'=>'Overdue','upcoming'=>'Upcoming','scheduled'=>'Scheduled'];
              $s = $row['follow_status'];
              echo "<span class='badge {$cls[$s]}'>{$lbl[$s]}</span>";
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recent_rows)): ?>
          <tr><td colspan="6" class="empty">No visits recorded yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── SIDEBAR ── -->
  <div>
    <!-- Monthly Trend -->
    <div class="card">
      <div class="card-title">Monthly Visits (Last 6 months)</div>
      <?php foreach ($trend_rows as $t): ?>
      <div style="margin-bottom:.75rem">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:.25rem">
          <span><?= $t['month_label'] ?></span>
          <strong><?= $t['visit_count'] ?> visits</strong>
        </div>
        <?php $pct = $trend_rows ? min(100, round($t['visit_count'] / max(array_column($trend_rows,'visit_count')) * 100)) : 0; ?>
        <div style="background:var(--sage);border-radius:4px;height:8px">
          <div style="background:var(--teal);width:<?= $pct ?>%;height:8px;border-radius:4px;transition:width .4s"></div>
        </div>
        <div style="font-size:.75rem;color:var(--slate);margin-top:.2rem">₹<?= number_format($t['total_revenue'], 0) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Upcoming Birthdays -->
    <div class="card">
      <div class="card-title">🎂 Upcoming Birthdays</div>
      <?php if (empty($birthday_rows)): ?>
        <p style="color:var(--slate);font-size:.85rem">No birthdays in next 30 days.</p>
      <?php else: foreach ($birthday_rows as $b): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--border)">
        <div>
          <!-- Birthday patient name → POST to patients/view.php -->
          <form method="POST" action="patients/view.php" style="display:inline">
            <input type="hidden" name="id" value="<?= $b['patient_id'] ?>">
            <button type="submit" style="background:none;border:none;padding:0;color:var(--navy);font-weight:600;cursor:pointer;font-family:inherit;font-size:.9rem"><?= htmlspecialchars($b['name']) ?></button>
          </form>
          <div style="font-size:.75rem;color:var(--slate)">Turning <?= $b['turning_age'] ?> on <?= $b['bday_short'] ?></div>
        </div>
        <span class="badge badge-teal"><?= $b['days_away'] ?>d</span>
      </div>
      <?php endforeach; endif; ?>
      <a href="reports/birthdays.php" class="btn btn-outline btn-sm mt-2">All Birthdays →</a>
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-title">Quick Actions</div>
      <div style="display:flex;flex-direction:column;gap:.5rem">
        <a href="patients/add.php" class="btn btn-primary">+ Register Patient</a>
        <a href="visits/add.php" class="btn btn-outline">+ Record Visit</a>
        <a href="reports/followups.php" class="btn btn-outline">Follow-Up Report</a>
        <a href="reports/summary.php" class="btn btn-outline">Full Summary</a>
      </div>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
