<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

// ── SQL: Full patient profile with all date calculations ────────────────────
$patient = $conn->query("
    SELECT
        p.*,
        DATE_FORMAT(p.dob, '%d %b %Y')                          AS dob_fmt,
        DATE_FORMAT(p.join_date, '%d %b %Y')                    AS join_date_fmt,
        YEAR(p.join_date)                                        AS join_year,
        MONTHNAME(p.join_date)                                   AS join_month,
        DAY(p.join_date)                                         AS join_day,

        -- Age in years
        TIMESTAMPDIFF(YEAR, p.dob, CURDATE())                   AS age_years,

        -- Full age (years + months)
        CONCAT(
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()),               ' yrs ',
            MOD(TIMESTAMPDIFF(MONTH, p.dob, CURDATE()), 12),    ' mo'
        )                                                        AS age_full,

        -- Days since last visit (SQL)
        DATEDIFF(CURDATE(), MAX(v.visit_date))                  AS days_since_last,
        DATE_FORMAT(MAX(v.visit_date), '%d %b %Y')              AS last_visit_fmt,

        -- Next follow-up due
        DATE_FORMAT(
            (SELECT follow_up_due FROM visits
             WHERE patient_id = p.patient_id
             ORDER BY visit_date DESC LIMIT 1),
            '%d %b %Y'
        )                                                        AS next_followup_fmt,

        -- Is follow-up overdue? (SQL comparison)
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
            ELSE 'ok'
        END                                                      AS followup_status,

        COUNT(v.visit_id)                                        AS total_visits,
        IFNULL(SUM(v.consultation_fee + v.lab_fee), 0)          AS total_spent,

        -- Is patient inactive 180+ days?
        CASE
            WHEN MAX(v.visit_date) IS NULL THEN 'no_visits'
            WHEN DATEDIFF(CURDATE(), MAX(v.visit_date)) >= 180 THEN 'inactive'
            ELSE 'active'
        END                                                      AS activity_status

    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
    WHERE p.patient_id = $id
    GROUP BY p.patient_id
")->fetch_assoc();

if (!$patient) {
    echo "<div class='alert alert-error'>Patient not found.</div>";
    require_once '../includes/footer.php';
    exit;
}

// ── SQL: Visit history for this patient ─────────────────────────────────────
$visits = $conn->query("
    SELECT
        v.visit_id,
        DATE_FORMAT(v.visit_date, '%d %b %Y')                   AS visit_date_fmt,
        v.visit_date,
        v.consultation_fee,
        v.lab_fee,
        v.consultation_fee + v.lab_fee                          AS total_fee,
        DATE_FORMAT(v.follow_up_due, '%d %b %Y')               AS follow_up_fmt,
        DATEDIFF(CURDATE(), v.visit_date)                       AS days_ago,
        CASE
            WHEN v.follow_up_due < CURDATE()  THEN 'overdue'
            WHEN v.follow_up_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'upcoming'
            ELSE 'scheduled'
        END                                                      AS follow_status
    FROM visits v
    WHERE v.patient_id = $id
    ORDER BY v.visit_date DESC
");

// ── SQL: Days between first & last visit ─────────────────────────────────────
$span = $conn->query("
    SELECT
        DATE_FORMAT(MIN(visit_date),'%d %b %Y') AS first_visit,
        DATE_FORMAT(MAX(visit_date),'%d %b %Y') AS last_visit,
        DATEDIFF(MAX(visit_date), MIN(visit_date)) AS span_days
    FROM visits
    WHERE patient_id = $id
")->fetch_assoc();

/* ── Helper: render a POST-method navigation button styled like a link/btn ──
   Usage: post_btn(label, target_file, ['id'=>123], 'btn btn-primary btn-sm')
*/
function post_btn(string $label, string $action, array $fields = [], string $cls = 'btn btn-outline btn-sm'): string {
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
    <h1><?= htmlspecialchars($patient['name']) ?></h1>
    <p>Patient #<?= $patient['patient_id'] ?> &mdash; Registered <?= $patient['join_date_fmt'] ?></p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <?= post_btn('Edit', 'edit.php', ['id' => $id], 'btn btn-amber') ?>
    <?= post_btn('+ New Visit', '../visits/add.php', ['patient_id' => $id], 'btn btn-primary') ?>
    <?= post_btn('Visit History', '../visits/patient_visits.php', ['patient_id' => $id], 'btn btn-outline') ?>
  </div>
</div>

<!-- ── FOLLOW-UP ALERT ── -->
<?php if ($patient['followup_status'] === 'overdue'): ?>
  <div class="alert alert-error">⚠️ Follow-up is <strong>overdue</strong> — due <?= $patient['next_followup_fmt'] ?></div>
<?php elseif ($patient['followup_status'] === 'upcoming'): ?>
  <div class="alert alert-warn">🔔 Follow-up is <strong>upcoming</strong> — due <?= $patient['next_followup_fmt'] ?></div>
<?php elseif ($patient['activity_status'] === 'inactive'): ?>
  <div class="alert alert-warn">📋 Patient has been inactive for <strong><?= $patient['days_since_last'] ?> days</strong></div>
<?php endif; ?>

<!-- ── STATS ── -->
<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $patient['age_years'] ?></div><div class="lbl">Age (years)</div></div>
  <div class="stat-card green"><div class="num"><?= $patient['total_visits'] ?></div><div class="lbl">Total Visits</div></div>
  <div class="stat-card amber"><div class="num"><?= is_null($patient['days_since_last']) ? '—' : $patient['days_since_last'] ?></div><div class="lbl">Days Since Last Visit</div></div>
  <div class="stat-card"><div class="num">₹<?= number_format($patient['total_spent'], 0) ?></div><div class="lbl">Total Spent</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

  <!-- ── PROFILE CARD ── -->
  <div class="card">
    <div class="card-title">Patient Profile</div>
    <div class="detail-grid">
      <div class="detail-item">
        <div class="lbl">Date of Birth</div>
        <div class="val"><?= $patient['dob_fmt'] ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Age</div>
        <div class="val"><?= $patient['age_full'] ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Phone</div>
        <div class="val"><?= htmlspecialchars($patient['phone'] ?: '—') ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Joined</div>
        <div class="val"><?= $patient['join_date_fmt'] ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Join Month/Year</div>
        <div class="val"><?= $patient['join_month'] ?> <?= $patient['join_year'] ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Address</div>
        <div class="val"><?= htmlspecialchars($patient['address'] ?: '—') ?></div>
      </div>
    </div>
  </div>

  <!-- ── VISIT SUMMARY ── -->
  <div class="card">
    <div class="card-title">Visit Summary (SQL)</div>
    <div class="detail-grid">
      <div class="detail-item">
        <div class="lbl">First Visit</div>
        <div class="val"><?= $span['first_visit'] ?? '—' ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Last Visit</div>
        <div class="val"><?= $patient['last_visit_fmt'] ?? '—' ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Days Since Last Visit</div>
        <div class="val"><?= is_null($patient['days_since_last']) ? '—' : $patient['days_since_last'] . ' days' ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Span (First→Last)</div>
        <div class="val"><?= isset($span['span_days']) ? $span['span_days'] . ' days' : '—' ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Next Follow-Up Due</div>
        <div class="val"><?= $patient['next_followup_fmt'] ?? '—' ?></div>
      </div>
      <div class="detail-item">
        <div class="lbl">Follow-Up Status</div>
        <div class="val">
          <?php
          $fc = ['overdue'=>'badge-red','upcoming'=>'badge-amber','ok'=>'badge-green',''=>'badge-slate'];
          $fl = ['overdue'=>'Overdue','upcoming'=>'Upcoming','ok'=>'On Track',''=>'No Visit'];
          $fs = $patient['followup_status'] ?: '';
          echo "<span class='badge {$fc[$fs]}'>{$fl[$fs]}</span>";
          ?>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ── VISIT HISTORY TABLE ── -->
<div class="card">
  <div class="flex-between" style="margin-bottom:1rem">
    <div class="card-title" style="margin:0;border:0;padding:0">Visit History</div>
    <?= post_btn('+ Add Visit', '../visits/add.php', ['patient_id' => $id], 'btn btn-primary btn-sm') ?>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Visit Date</th>
          <th>Days Ago</th>
          <th>Consult Fee</th>
          <th>Lab Fee</th>
          <th>Total</th>
          <th>Follow-Up Due</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $visit_rows = [];
        while ($vr = $visits->fetch_assoc()) $visit_rows[] = $vr;
        if (empty($visit_rows)):
        ?>
        <tr><td colspan="8" class="empty"><div class="ico">📋</div>No visits recorded.</td></tr>
        <?php else: ?>
        <?php foreach ($visit_rows as $vr): ?>
        <tr>
          <td style="color:var(--slate);font-size:.8rem"><?= $vr['visit_id'] ?></td>
          <td><?= $vr['visit_date_fmt'] ?></td>
          <td><?= $vr['days_ago'] ?> d</td>
          <td>₹<?= number_format($vr['consultation_fee'], 0) ?></td>
          <td>₹<?= number_format($vr['lab_fee'], 0) ?></td>
          <td style="font-weight:600">₹<?= number_format($vr['total_fee'], 0) ?></td>
          <td><?= $vr['follow_up_fmt'] ?></td>
          <td>
            <?php
            $vc = ['overdue'=>'badge-red','upcoming'=>'badge-amber','scheduled'=>'badge-green'];
            $vl = ['overdue'=>'Overdue','upcoming'=>'Upcoming','scheduled'=>'Scheduled'];
            $vs = $vr['follow_status'];
            echo "<span class='badge {$vc[$vs]}'>{$vl[$vs]}</span>";
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
