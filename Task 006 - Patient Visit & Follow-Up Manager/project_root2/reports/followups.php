<?php
require_once '../config/db.php';
require_once '../includes/header.php';

/* ── Helper: inline POST form button ── */
function post_btn(string $label, string $action, array $fields, string $cls = 'btn btn-sm btn-primary'): string {
    $inputs = '';
    foreach ($fields as $name => $val) {
        $inputs .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($val) . '">';
    }
    return '<form method="POST" action="' . htmlspecialchars($action) . '" style="display:inline">'
         . $inputs
         . '<button type="submit" class="' . $cls . '">' . $label . '</button>'
         . '</form>';
}

/* ── Inline patient name as a POST submit button ── */
function patient_link(string $name, int $id): string {
    return '<form method="POST" action="../patients/view.php" style="display:inline">'
         . '<input type="hidden" name="id" value="' . $id . '">'
         . '<button type="submit" style="background:none;border:none;padding:0;color:var(--teal);font-weight:600;cursor:pointer;font-family:inherit;font-size:inherit">' . htmlspecialchars($name) . '</button>'
         . '</form>';
}
?>

<div class="page-header">
  <h1>Follow-Up Report</h1>
</div>

<div class="section-tabs">
  <a href="#overdue" class="active">Overdue</a>
  <a href="#upcoming">Upcoming (7 days)</a>
  <a href="#missed">Missed</a>
  <a href="#inactive">Inactive 180+ days</a>
  <a href="#novisit">No Visits</a>
</div>

<?php
// ── SQL: Summary counts ──────────────────────────────────────────────────────
$s = $conn->query("
    SELECT
        SUM(CASE WHEN follow_up_due < CURDATE() THEN 1 ELSE 0 END)             AS overdue,
        SUM(CASE WHEN follow_up_due BETWEEN CURDATE()
                  AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) THEN 1 ELSE 0 END)    AS upcoming,
        COUNT(*)                                                                 AS total
    FROM visits
")->fetch_assoc();

$no_visit = $conn->query("
    SELECT COUNT(*) AS cnt
    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
    WHERE v.visit_id IS NULL
")->fetch_assoc();

$inactive_cnt = $conn->query("
    SELECT COUNT(DISTINCT p.patient_id) AS cnt
    FROM patients p
    JOIN visits v ON p.patient_id = v.patient_id
    GROUP BY p.patient_id
    HAVING DATEDIFF(CURDATE(), MAX(v.visit_date)) >= 180
")->num_rows;
?>

<div class="stats-grid">
  <div class="stat-card red">  <div class="num"><?= $s['overdue'] ?></div><div class="lbl">Overdue Follow-Ups</div></div>
  <div class="stat-card amber"><div class="num"><?= $s['upcoming'] ?></div><div class="lbl">Upcoming This Week</div></div>
  <div class="stat-card">     <div class="num"><?= $no_visit['cnt'] ?></div><div class="lbl">No Visits Yet</div></div>
  <div class="stat-card amber"><div class="num"><?= $inactive_cnt ?></div><div class="lbl">Inactive 180+ Days</div></div>
</div>

<!-- ── OVERDUE ── -->
<div class="card" id="overdue">
  <div class="card-title" style="color:var(--red)">🔴 Overdue Follow-Ups</div>
  <?php
  $overdue = $conn->query("
      SELECT
          p.patient_id,
          p.name,
          p.phone,
          DATE_FORMAT(v.visit_date,'%d %b %Y')            AS visit_date_fmt,
          DATE_FORMAT(v.follow_up_due,'%d %b %Y')        AS due_fmt,
          DATEDIFF(CURDATE(), v.follow_up_due)            AS days_overdue,
          v.follow_up_due
      FROM visits v
      JOIN patients p ON p.patient_id = v.patient_id
      WHERE v.follow_up_due < CURDATE()
      ORDER BY days_overdue DESC
  ");
  ?>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Phone</th><th>Last Visit</th><th>Follow-Up Due</th><th>Days Overdue</th><th>Action</th></tr></thead>
      <tbody>
        <?php
        $rows = [];
        while ($r = $overdue->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <tr><td colspan="6" class="empty">✅ No overdue follow-ups!</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= patient_link($r['name'], $r['patient_id']) ?></td>
          <td><?= htmlspecialchars($r['phone']) ?></td>
          <td><?= $r['visit_date_fmt'] ?></td>
          <td style="color:var(--red);font-weight:600"><?= $r['due_fmt'] ?></td>
          <td><span class="badge badge-red"><?= $r['days_overdue'] ?> days</span></td>
          <td><?= post_btn('Schedule Visit', '../visits/add.php', ['patient_id' => $r['patient_id']]) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── UPCOMING ── -->
<div class="card" id="upcoming">
  <div class="card-title" style="color:var(--amber)">🟡 Upcoming Follow-Ups (next 7 days)</div>
  <?php
  $upcoming = $conn->query("
      SELECT
          p.patient_id,
          p.name,
          p.phone,
          DATE_FORMAT(v.visit_date,'%d %b %Y')            AS visit_date_fmt,
          DATE_FORMAT(v.follow_up_due,'%d %b %Y')        AS due_fmt,
          DATEDIFF(v.follow_up_due, CURDATE())            AS days_until
      FROM visits v
      JOIN patients p ON p.patient_id = v.patient_id
      WHERE v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
      ORDER BY v.follow_up_due ASC
  ");
  ?>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Phone</th><th>Last Visit</th><th>Follow-Up Due</th><th>Days Away</th></tr></thead>
      <tbody>
        <?php
        $rows = [];
        while ($r = $upcoming->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <tr><td colspan="5" class="empty">No upcoming follow-ups in the next 7 days.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= patient_link($r['name'], $r['patient_id']) ?></td>
          <td><?= htmlspecialchars($r['phone']) ?></td>
          <td><?= $r['visit_date_fmt'] ?></td>
          <td style="color:var(--amber);font-weight:600"><?= $r['due_fmt'] ?></td>
          <td><span class="badge badge-amber"><?= $r['days_until'] ?> days</span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── MISSED ── -->
<div class="card" id="missed">
  <div class="card-title">⚠️ Missed Follow-Ups (overdue &amp; no new visit after due date)</div>
  <?php
  $missed = $conn->query("
      SELECT
          p.patient_id,
          p.name,
          p.phone,
          DATE_FORMAT(v.visit_date,'%d %b %Y')            AS visit_date_fmt,
          DATE_FORMAT(v.follow_up_due,'%d %b %Y')        AS due_fmt,
          DATEDIFF(CURDATE(), v.follow_up_due)            AS days_overdue
      FROM visits v
      JOIN patients p ON p.patient_id = v.patient_id
      WHERE v.follow_up_due < CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM visits v2
            WHERE v2.patient_id = v.patient_id
              AND v2.visit_date > v.follow_up_due
        )
      ORDER BY days_overdue DESC
  ");
  ?>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Phone</th><th>Original Visit</th><th>Follow-Up Due</th><th>Days Missed</th></tr></thead>
      <tbody>
        <?php
        $rows = [];
        while ($r = $missed->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <tr><td colspan="5" class="empty">✅ No missed follow-ups found.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= patient_link($r['name'], $r['patient_id']) ?></td>
          <td><?= htmlspecialchars($r['phone']) ?></td>
          <td><?= $r['visit_date_fmt'] ?></td>
          <td style="color:var(--red);font-weight:600"><?= $r['due_fmt'] ?></td>
          <td><span class="badge badge-red"><?= $r['days_overdue'] ?> days</span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── INACTIVE ── -->
<div class="card" id="inactive">
  <div class="card-title">⏸️ Patients Inactive 180+ Days</div>
  <?php
  $inactive = $conn->query("
      SELECT
          p.patient_id,
          p.name,
          p.phone,
          DATE_FORMAT(MAX(v.visit_date),'%d %b %Y')  AS last_visit_fmt,
          DATEDIFF(CURDATE(), MAX(v.visit_date))      AS days_inactive
      FROM patients p
      JOIN visits v ON p.patient_id = v.patient_id
      GROUP BY p.patient_id, p.name, p.phone
      HAVING DATEDIFF(CURDATE(), MAX(v.visit_date)) >= 180
      ORDER BY days_inactive DESC
  ");
  ?>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Phone</th><th>Last Visit</th><th>Days Inactive</th></tr></thead>
      <tbody>
        <?php
        $rows = [];
        while ($r = $inactive->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <tr><td colspan="4" class="empty">✅ No patients inactive 180+ days.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= patient_link($r['name'], $r['patient_id']) ?></td>
          <td><?= htmlspecialchars($r['phone']) ?></td>
          <td><?= $r['last_visit_fmt'] ?></td>
          <td><span class="badge badge-amber"><?= $r['days_inactive'] ?> days</span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── NO VISITS ── -->
<div class="card" id="novisit">
  <div class="card-title">👤 Patients With No Visits</div>
  <?php
  $novisit = $conn->query("
      SELECT
          p.patient_id,
          p.name,
          p.phone,
          DATE_FORMAT(p.join_date,'%d %b %Y')          AS join_date_fmt,
          DATEDIFF(CURDATE(), p.join_date)              AS days_since_join,
          TIMESTAMPDIFF(YEAR, p.dob, CURDATE())         AS age_years
      FROM patients p
      LEFT JOIN visits v ON p.patient_id = v.patient_id
      WHERE v.visit_id IS NULL
      ORDER BY p.join_date
  ");
  ?>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Age</th><th>Phone</th><th>Join Date</th><th>Days Since Join</th><th>Action</th></tr></thead>
      <tbody>
        <?php
        $rows = [];
        while ($r = $novisit->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <tr><td colspan="6" class="empty">✅ All patients have at least one visit.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= patient_link($r['name'], $r['patient_id']) ?></td>
          <td><?= $r['age_years'] ?></td>
          <td><?= htmlspecialchars($r['phone']) ?></td>
          <td><?= $r['join_date_fmt'] ?></td>
          <td><span class="badge badge-slate"><?= $r['days_since_join'] ?> days</span></td>
          <td><?= post_btn('+ Visit', '../visits/add.php', ['patient_id' => $r['patient_id']]) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
