<?php
require_once '../config/db.php';
require_once '../includes/header.php';
?>

<div class="page-header">
  <h1>Monthly Report</h1>
  
</div>

<?php
// ── SQL: Visits per month (last 6 months) ───────────────────────────────────
$monthly_visits = $conn->query("
    SELECT
        DATE_FORMAT(visit_date, '%Y-%m')                        AS ym,
        DATE_FORMAT(visit_date, '%b %Y')                        AS month_label,
        YEAR(visit_date)                                         AS yr,
        MONTH(visit_date)                                        AS mo,
        COUNT(*)                                                 AS visit_count,
        COUNT(DISTINCT patient_id)                              AS unique_patients,
        SUM(consultation_fee)                                    AS total_consult,
        SUM(lab_fee)                                             AS total_lab,
        SUM(consultation_fee + lab_fee)                         AS total_revenue
    FROM visits
    WHERE visit_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH),'%Y-%m-01')
    GROUP BY YEAR(visit_date), MONTH(visit_date)
    ORDER BY yr, mo
");

// ── SQL: Patients joined per month (group by join month) ────────────────────
$monthly_joins = $conn->query("
    SELECT
        DATE_FORMAT(join_date,'%b %Y')      AS month_label,
        YEAR(join_date)                      AS yr,
        MONTHNAME(join_date)                 AS month_name,
        MONTH(join_date)                     AS mo,
        COUNT(*)                             AS joined_count
    FROM patients
    GROUP BY YEAR(join_date), MONTH(join_date)
    ORDER BY yr DESC, mo DESC
    LIMIT 12
");

// ── SQL: Patients grouped by join month (visit link) ────────────────────────
$joinmonth_visits = $conn->query("
    SELECT
        DATE_FORMAT(p.join_date,'%b %Y')    AS join_month_label,
        YEAR(p.join_date)                    AS yr,
        MONTH(p.join_date)                   AS mo,
        COUNT(DISTINCT p.patient_id)         AS patient_count,
        COUNT(v.visit_id)                    AS total_visits,
        ROUND(COUNT(v.visit_id)/COUNT(DISTINCT p.patient_id),1) AS avg_visits_per_patient
    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
    GROUP BY YEAR(p.join_date), MONTH(p.join_date)
    ORDER BY yr DESC, mo DESC
");

$mv_rows = [];
while ($r = $monthly_visits->fetch_assoc()) $mv_rows[] = $r;
$max_visits = $mv_rows ? max(array_column($mv_rows,'visit_count')) : 1;
?>

<!-- ── Monthly Visits (Last 6 months) ── -->
<div class="card">
  <div class="card-title">📅 Visits Per Month — Last 6 Months</div>
  <div style="display:grid;gap:1rem;margin-bottom:1.5rem">
    <?php foreach ($mv_rows as $r): ?>
    <div>
      <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.35rem">
        <strong><?= $r['month_label'] ?></strong>
        <span><?= $r['visit_count'] ?> visits | <?= $r['unique_patients'] ?> patients | ₹<?= number_format($r['total_revenue'],0) ?></span>
      </div>
      <?php $pct = round($r['visit_count']/$max_visits*100); ?>
      <div style="background:var(--sage);border-radius:6px;height:20px;position:relative;overflow:hidden">
        <div style="background:var(--teal);width:<?= $pct ?>%;height:100%;border-radius:6px;transition:width .6s;display:flex;align-items:center;padding-left:.5rem">
          <?php if ($pct > 15): ?>
          <span style="color:#fff;font-size:.75rem;font-weight:600"><?= $r['visit_count'] ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>Month</th>
          <th>Total Visits</th>
          <th>Unique Patients</th>
          <th>Consult Revenue</th>
          <th>Lab Revenue</th>
          <th>Total Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mv_rows)): ?>
        <tr><td colspan="6" class="empty">No data available.</td></tr>
        <?php else: foreach ($mv_rows as $r): ?>
        <tr>
          <td style="font-weight:600"><?= $r['month_label'] ?></td>
          <td style="text-align:center"><?= $r['visit_count'] ?></td>
          <td style="text-align:center"><?= $r['unique_patients'] ?></td>
          <td>₹<?= number_format($r['total_consult'],0) ?></td>
          <td>₹<?= number_format($r['total_lab'],0) ?></td>
          <td style="font-weight:600">₹<?= number_format($r['total_revenue'],0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Patients Joined Per Month ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

  <div class="card">
    <div class="card-title">🗓️ Patients Joined Per Month</div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr><th>Month Joined</th><th>Month Name</th><th>Patients Joined</th></tr>
        </thead>
        <tbody>
          <?php
          $mj_rows = [];
          while ($r = $monthly_joins->fetch_assoc()) $mj_rows[] = $r;
          if (empty($mj_rows)):
          ?>
          <tr><td colspan="3" class="empty">No data.</td></tr>
          <?php else: foreach ($mj_rows as $r): ?>
          <tr>
            <td style="font-weight:600"><?= $r['month_label'] ?></td>
            <td><?= $r['month_name'] ?></td>
            <td style="text-align:center"><span class="badge badge-teal"><?= $r['joined_count'] ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-title">🔗 Visits Linked to Join-Month Groups</div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr><th>Join Month</th><th>Patients</th><th>Total Visits</th><th>Avg Visits/Patient</th></tr>
        </thead>
        <tbody>
          <?php
          $jmv_rows = [];
          while ($r = $joinmonth_visits->fetch_assoc()) $jmv_rows[] = $r;
          if (empty($jmv_rows)):
          ?>
          <tr><td colspan="4" class="empty">No data.</td></tr>
          <?php else: foreach ($jmv_rows as $r): ?>
          <tr>
            <td style="font-weight:600"><?= $r['join_month_label'] ?></td>
            <td style="text-align:center"><?= $r['patient_count'] ?></td>
            <td style="text-align:center"><?= $r['total_visits'] ?></td>
            <td style="text-align:center">
              <span class="badge badge-<?= $r['avg_visits_per_patient'] >= 2 ? 'green' : 'slate' ?>"><?= $r['avg_visits_per_patient'] ?></span>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
