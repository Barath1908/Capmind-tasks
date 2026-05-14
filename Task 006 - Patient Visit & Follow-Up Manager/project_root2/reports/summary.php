<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// ── SQL: Full summary report ─────────────────────────────────────────────────
$result = $conn->query("
    SELECT
        p.patient_id,
        p.name,
        p.phone,

        -- Age (SQL)
        TIMESTAMPDIFF(YEAR, p.dob, CURDATE())                AS age_years,
        CONCAT(
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()), 'y ',
            MOD(TIMESTAMPDIFF(MONTH, p.dob, CURDATE()), 12), 'm'
        )                                                     AS age_full,

        -- Visit stats (SQL aggregates)
        COUNT(v.visit_id)                                     AS total_visits,
        DATE_FORMAT(MIN(v.visit_date), '%d %b %Y')           AS first_visit_fmt,
        DATE_FORMAT(MAX(v.visit_date), '%d %b %Y')           AS last_visit_fmt,
        DATEDIFF(CURDATE(), MAX(v.visit_date))               AS days_since_last,
        DATEDIFF(MAX(v.visit_date), MIN(v.visit_date))       AS visit_span_days,
        IFNULL(SUM(v.consultation_fee + v.lab_fee), 0)       AS total_spent,

        -- Next follow-up (SQL subquery)
        DATE_FORMAT(
            (SELECT follow_up_due FROM visits
             WHERE patient_id = p.patient_id
             ORDER BY visit_date DESC LIMIT 1),
            '%d %b %Y'
        )                                                     AS next_followup_fmt,

        -- Follow-up status (SQL CASE)
        CASE
            WHEN (SELECT follow_up_due FROM visits
                  WHERE patient_id = p.patient_id
                  ORDER BY visit_date DESC LIMIT 1) < CURDATE()
                THEN 'overdue'
            WHEN (SELECT follow_up_due FROM visits
                  WHERE patient_id = p.patient_id
                  ORDER BY visit_date DESC LIMIT 1)
                  BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                THEN 'upcoming'
            WHEN COUNT(v.visit_id) = 0
                THEN 'no_visit'
            ELSE 'ok'
        END                                                   AS followup_status,

        -- Activity status (SQL CASE + DATEDIFF)
        CASE
            WHEN COUNT(v.visit_id) = 0
                THEN 'no_visits'
            WHEN DATEDIFF(CURDATE(), MAX(v.visit_date)) >= 180
                THEN 'inactive'
            ELSE 'active'
        END                                                   AS activity_status

    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
    GROUP BY p.patient_id, p.name, p.phone, p.dob, p.join_date
    ORDER BY p.name
");

$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

// ── SQL: Grand totals ────────────────────────────────────────────────────────
$totals = $conn->query("
    SELECT
        COUNT(DISTINCT p.patient_id)               AS patient_count,
        COUNT(v.visit_id)                          AS total_visits,
        IFNULL(SUM(v.consultation_fee+v.lab_fee),0) AS total_revenue,
        ROUND(AVG(TIMESTAMPDIFF(YEAR,p.dob,CURDATE())),1) AS avg_age
    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
")->fetch_assoc();
?>

<div class="page-header flex-between">
  <div>
    <h1>Full Summary Report</h1>
  </div>
  <button onclick="window.print()" class="btn btn-outline">🖨️ Print</button>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $totals['patient_count'] ?></div><div class="lbl">Total Patients</div></div>
  <div class="stat-card green"><div class="num"><?= $totals['total_visits'] ?></div><div class="lbl">Total Visits</div></div>
  <div class="stat-card"><div class="num">₹<?= number_format($totals['total_revenue'],0) ?></div><div class="lbl">Total Revenue</div></div>
  <div class="stat-card amber"><div class="num"><?= $totals['avg_age'] ?></div><div class="lbl">Average Patient Age</div></div>
</div>

<div class="card">
  <div class="flex-between" style="margin-bottom:1rem">
    <div class="card-title" style="margin:0;border:0;padding:0">Patient Summary</div>
    <div style="font-size:.8rem;color:var(--slate)"><?= count($rows) ?> patients</div>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Age</th>
          <th>Visits</th>
          <th>First Visit</th>
          <th>Last Visit</th>
          <th>Days Since</th>
          <th>Span (days)</th>
          <th>Spent (₹)</th>
          <th>Next Follow-Up</th>
          <th>F/U Status</th>
          <th>Activity</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="12" class="empty">No patients found.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td style="color:var(--slate);font-size:.78rem"><?= $r['patient_id'] ?></td>
          <td>
            <!-- Patient name → POST to patients/view.php -->
            <form method="POST" action="../patients/view.php" style="display:inline">
              <input type="hidden" name="id" value="<?= $r['patient_id'] ?>">
              <button type="submit" style="background:none;border:none;padding:0;color:var(--teal);font-weight:600;cursor:pointer;font-family:inherit;font-size:.9rem"><?= htmlspecialchars($r['name']) ?></button>
            </form>
            <div style="font-size:.72rem;color:var(--slate)"><?= htmlspecialchars($r['phone']) ?></div>
          </td>
          <td style="white-space:nowrap">
            <strong><?= $r['age_years'] ?></strong>
            <div style="font-size:.72rem;color:var(--slate)"><?= $r['age_full'] ?></div>
          </td>
          <td style="text-align:center;font-weight:700;font-size:1.1rem;color:var(--teal)"><?= $r['total_visits'] ?></td>
          <td style="font-size:.82rem"><?= $r['first_visit_fmt'] ?? '—' ?></td>
          <td style="font-size:.82rem"><?= $r['last_visit_fmt'] ?? '—' ?></td>
          <td style="text-align:center;font-size:.82rem">
            <?= is_null($r['days_since_last']) ? '—' : $r['days_since_last'] . 'd' ?>
          </td>
          <td style="text-align:center;font-size:.82rem">
            <?= is_null($r['visit_span_days']) ? '—' : $r['visit_span_days'] . 'd' ?>
          </td>
          <td style="font-size:.82rem">₹<?= number_format($r['total_spent'],0) ?></td>
          <td style="font-size:.82rem"><?= $r['next_followup_fmt'] ?? '—' ?></td>
          <td>
            <?php
            $fc = ['overdue'=>'badge-red','upcoming'=>'badge-amber','ok'=>'badge-green','no_visit'=>'badge-slate'];
            $fl = ['overdue'=>'Overdue','upcoming'=>'Upcoming','ok'=>'On Track','no_visit'=>'—'];
            $fs = $r['followup_status'];
            echo "<span class='badge {$fc[$fs]}'>{$fl[$fs]}</span>";
            ?>
          </td>
          <td>
            <?php
            $ac = ['active'=>'badge-green','inactive'=>'badge-amber','no_visits'=>'badge-slate'];
            $al = ['active'=>'Active','inactive'=>'Inactive','no_visits'=>'No Visits'];
            $as = $r['activity_status'];
            echo "<span class='badge {$ac[$as]}'>{$al[$as]}</span>";
            ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>


<?php require_once '../includes/footer.php'; ?>
