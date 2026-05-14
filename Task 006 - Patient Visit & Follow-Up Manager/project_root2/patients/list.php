<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// ── SQL: List all patients with age, visit count, join date parts ────────────
$sql = "
    SELECT
        p.patient_id,
        p.name,
        p.phone,
        p.dob,
        p.address,

        -- Age in years (SQL)
        TIMESTAMPDIFF(YEAR, p.dob, CURDATE())                       AS age_years,

        -- Full age: years + months
        CONCAT(
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()), ' yrs ',
            MOD(TIMESTAMPDIFF(MONTH, p.dob, CURDATE()), 12),        ' mo'
        )                                                            AS age_full,

        -- Join date breakdown
        YEAR(p.join_date)                                            AS join_year,
        MONTHNAME(p.join_date)                                       AS join_month,
        DAY(p.join_date)                                             AS join_day,
        DATE_FORMAT(p.join_date, '%d %b %Y')                        AS join_date_fmt,

        -- Total visits (SQL)
        COUNT(v.visit_id)                                            AS total_visits,

        -- Last visit date
        DATE_FORMAT(MAX(v.visit_date), '%d %b %Y')                  AS last_visit_fmt,

        -- Days since last visit
        DATEDIFF(CURDATE(), MAX(v.visit_date))                      AS days_since_last,

        -- Inactive 180+ days flag
        CASE
            WHEN MAX(v.visit_date) IS NULL THEN 'no_visits'
            WHEN DATEDIFF(CURDATE(), MAX(v.visit_date)) >= 180 THEN 'inactive'
            ELSE 'active'
        END                                                          AS activity_status
    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
    GROUP BY p.patient_id, p.name, p.phone, p.dob, p.address, p.join_date
    ORDER BY p.name
";

$result = $conn->query($sql);
$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

// ── SQL: Summary counts ─────────────────────────────────────────────────────
$summary = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN (SELECT COUNT(*) FROM visits v WHERE v.patient_id = p.patient_id) = 0 THEN 1 ELSE 0 END) AS no_visits,
        SUM(CASE WHEN DATEDIFF(CURDATE(),(SELECT MAX(visit_date) FROM visits v WHERE v.patient_id=p.patient_id)) >= 180 THEN 1 ELSE 0 END) AS inactive
    FROM patients p
")->fetch_assoc();

/* ── Helper: render an inline POST form button ── */
function post_btn(string $label, string $action, array $fields, string $cls = 'btn btn-sm btn-outline'): string {
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
    <h1>Patient List</h1>
    <p><?= count($rows) ?> registered patients — ages &amp; visit counts</p>
  </div>
  <a href="add.php" class="btn btn-primary">+ Register Patient</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $summary['total'] ?></div><div class="lbl">Total Patients</div></div>
  <div class="stat-card red"><div class="num"><?= $summary['no_visits'] ?></div><div class="lbl">No Visits Yet</div></div>
  <div class="stat-card amber"><div class="num"><?= $summary['inactive'] ?></div><div class="lbl">Inactive 180+ Days</div></div>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th style="min-width:140px">Name</th>
          <th>DOB</th>
          <th>Age</th>
          <th>Full Age</th>
          <th>Joined</th>
          <th>Total Visits</th>
          <th>Last Visit</th>
          <th>Days Since</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="11" class="empty"><div class="ico">🏥</div>No patients registered yet.</td></tr>
        <?php else: ?>
        <?php foreach ($rows as $i => $p): ?>
        <tr>
          <td style="color:var(--slate);font-size:.8rem"><?= $p['patient_id'] ?></td>
          <td style="white-space:nowrap">
            <div style="display:flex;flex-direction:column;align-items:flex-start;gap:.15rem;">
              <!-- Patient name: POST to view.php -->
              <form method="POST" action="view.php" style="display:inline">
                <input type="hidden" name="id" value="<?= $p['patient_id'] ?>">
                <button type="submit" style="background:none;border:none;padding:0;color:var(--teal);font-weight:600;cursor:pointer;font-family:inherit;font-size:inherit;text-align:left"><?= htmlspecialchars($p['name']) ?></button>
              </form>
              <div style="font-size:.75rem;color:var(--slate)"><?= htmlspecialchars($p['phone']) ?></div>
            </div>
          </td>
          <td><?= htmlspecialchars($p['dob']) ?></td>
          <td style="font-weight:600"><?= $p['age_years'] ?></td>
          <td style="font-size:.82rem;white-space:nowrap"><?= $p['age_full'] ?></td>
          <td style="font-size:.82rem">
            <?= $p['join_date_fmt'] ?>
            <div style="font-size:.72rem;color:var(--slate)"><?= $p['join_month'] ?> <?= $p['join_year'] ?></div>
          </td>
          <td style="text-align:center;font-weight:600"><?= $p['total_visits'] ?></td>
          <td style="font-size:.82rem"><?= $p['last_visit_fmt'] ?? '—' ?></td>
          <td style="text-align:center">
            <?= is_null($p['days_since_last']) ? '—' : $p['days_since_last'] . ' d' ?>
          </td>
          <td>
            <?php
            $sc = ['active'=>'badge-green','inactive'=>'badge-amber','no_visits'=>'badge-slate'];
            $sl = ['active'=>'Active','inactive'=>'Inactive','no_visits'=>'No Visits'];
            $s = $p['activity_status'];
            echo "<span class='badge {$sc[$s]}'>{$sl[$s]}</span>";
            ?>
          </td>
          <td style="white-space:nowrap">
            <div style="display:flex;gap:.35rem;flex-wrap:nowrap;align-items:center;">
              <?= post_btn('View',    'view.php',           ['id' => $p['patient_id']], 'btn btn-sm btn-outline') ?>
              <?= post_btn('Edit',    'edit.php',           ['id' => $p['patient_id']], 'btn btn-sm btn-amber') ?>
              <?= post_btn('+ Visit', '../visits/add.php',  ['patient_id' => $p['patient_id']], 'btn btn-sm btn-primary') ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>