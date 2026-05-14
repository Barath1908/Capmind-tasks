<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$pid = (int)($_POST['patient_id'] ?? 0);
if (!$pid) { header('Location: list.php'); exit; }

$patient = $conn->query("SELECT * FROM patients WHERE patient_id = $pid")->fetch_assoc();
if (!$patient) { echo "<div class='alert alert-error'>Patient not found.</div>"; require_once '../includes/footer.php'; exit; }

// ── SQL: Visit history with span statistics ──────────────────────────────────
$visits = $conn->query("
    SELECT
        v.visit_id,
        DATE_FORMAT(v.visit_date,'%d %b %Y')                       AS visit_date_fmt,
        v.visit_date,
        DATEDIFF(CURDATE(), v.visit_date)                          AS days_ago,
        v.consultation_fee,
        v.lab_fee,
        v.consultation_fee + v.lab_fee                             AS total_fee,
        DATE_FORMAT(v.follow_up_due,'%d %b %Y')                   AS follow_up_fmt,
        CASE
            WHEN v.follow_up_due < CURDATE()
                THEN 'overdue'
            WHEN v.follow_up_due <= DATE_ADD(CURDATE(),INTERVAL 7 DAY)
                THEN 'upcoming'
            ELSE 'scheduled'
        END                                                         AS follow_status
    FROM visits v
    WHERE v.patient_id = $pid
    ORDER BY v.visit_date DESC
");

// ── SQL: Aggregate visit stats (first, last, span, total) ───────────────────
$vstats = $conn->query("
    SELECT
        COUNT(*)                                         AS total_visits,
        DATE_FORMAT(MIN(visit_date),'%d %b %Y')         AS first_visit_fmt,
        DATE_FORMAT(MAX(visit_date),'%d %b %Y')         AS last_visit_fmt,
        DATEDIFF(MAX(visit_date), MIN(visit_date))      AS span_days,
        DATEDIFF(CURDATE(), MAX(visit_date))            AS days_since,
        IFNULL(SUM(consultation_fee + lab_fee), 0)      AS total_spent,
        IFNULL(AVG(consultation_fee + lab_fee), 0)      AS avg_fee,
        DATE_FORMAT(
            DATE_ADD(MAX(visit_date), INTERVAL 7 DAY),
            '%d %b %Y'
        )                                                AS next_followup
    FROM visits
    WHERE patient_id = $pid
")->fetch_assoc();

// ── SQL: Patient age ─────────────────────────────────────────────────────────
$age = $conn->query("
    SELECT
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age_years,
        CONCAT(
            TIMESTAMPDIFF(YEAR, dob, CURDATE()), ' yrs ',
            MOD(TIMESTAMPDIFF(MONTH, dob, CURDATE()), 12), ' mo'
        ) AS age_full
    FROM patients WHERE patient_id = $pid
")->fetch_assoc();

/* ── Helper: inline POST form button ── */
function post_btn(string $label, string $action, array $fields, string $cls = 'btn btn-outline btn-sm'): string {
    $inputs = '';
    foreach ($fields as $name => $val) {
        $inputs .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($val) . '">';
    }
    return '<form method="POST" action="' . htmlspecialchars($action) . '" style="display:inline">'
         . $inputs
         . '<button type="submit" class="' . $cls . '">' . $label . '</button>'
         . '</form>';
}
?>

<div class="page-header flex-between">
  <div>
    <h1>Visit History</h1>
    <p><?= htmlspecialchars($patient['name']) ?> &mdash; Age: <?= $age['age_full'] ?></p>
  </div>
  <div style="display:flex;gap:.5rem">
    <?= post_btn('Patient Profile', '../patients/view.php', ['id' => $pid], 'btn btn-outline') ?>
    <?= post_btn('+ New Visit',     'add.php',              ['patient_id' => $pid], 'btn btn-primary') ?>
  </div>
</div>

<?php if ($vstats['total_visits'] > 0): ?>
<div class="stats-grid">
  <div class="stat-card green">
    <div class="num"><?= $vstats['total_visits'] ?></div>
    <div class="lbl">Total Visits</div>
  </div>
  <div class="stat-card">
    <div class="num"><?= $vstats['span_days'] ?? 0 ?></div>
    <div class="lbl">Days (First→Last)</div>
  </div>
  <div class="stat-card amber">
    <div class="num"><?= $vstats['days_since'] ?? '—' ?></div>
    <div class="lbl">Days Since Last Visit</div>
  </div>
  <div class="stat-card">
    <div class="num">₹<?= number_format($vstats['total_spent'], 0) ?></div>
    <div class="lbl">Total Spent</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <div class="card">
    <div class="card-title">Visit Timeline (SQL)</div>
    <div class="detail-grid">
      <div class="detail-item">
        <div class="lbl">First Visit</div>
        <div class="val"><?= $vstats['first_visit_fmt'] ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Last Visit</div>
        <div class="val"><?= $vstats['last_visit_fmt'] ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Span Between</div>
        <div class="val"><?= $vstats['span_days'] ?> days</div>
      </div>
      <div class="detail-item">
        <div class="lbl">Avg Fee / Visit</div>
        <div class="val">₹<?= number_format($vstats['avg_fee'], 0) ?></div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-title">Next Follow-Up (SQL)</div>
    <div style="font-size:1.8rem;font-family:'DM Serif Display',serif;color:var(--teal);margin-bottom:.5rem"><?= $vstats['next_followup'] ?></div>
    <p style="color:var(--slate);font-size:.85rem">Computed by SQL: MAX(visit_date) + INTERVAL 7 DAY</p>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">All Visits for <?= htmlspecialchars($patient['name']) ?></div>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>Visit Date</th>
          <th>Days Ago</th>
          <th>Consultation (₹)</th>
          <th>Lab (₹)</th>
          <th>Total (₹)</th>
          <th>Follow-Up Due</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $vrows = [];
        while ($vr = $visits->fetch_assoc()) $vrows[] = $vr;
        if (empty($vrows)):
        ?>
        <tr><td colspan="7" class="empty"><div class="ico">📋</div>No visits recorded yet.</td></tr>
        <?php else: ?>
        <?php foreach ($vrows as $vr): ?>
        <tr>
          <td><?= $vr['visit_date_fmt'] ?></td>
          <td style="text-align:center"><?= $vr['days_ago'] ?> d</td>
          <td>₹<?= number_format($vr['consultation_fee'], 0) ?></td>
          <td>₹<?= number_format($vr['lab_fee'], 0) ?></td>
          <td style="font-weight:600">₹<?= number_format($vr['total_fee'], 0) ?></td>
          <td><?= $vr['follow_up_fmt'] ?></td>
          <td>
            <?php
            $sc = ['overdue'=>'badge-red','upcoming'=>'badge-amber','scheduled'=>'badge-green'];
            $sl = ['overdue'=>'Overdue','upcoming'=>'Upcoming','scheduled'=>'Scheduled'];
            $s = $vr['follow_status'];
            echo "<span class='badge {$sc[$s]}'>{$sl[$s]}</span>";
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
