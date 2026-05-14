<?php
require_once '../config/db.php';
require_once '../includes/header.php';

/* ── Inline patient name as a POST submit button ── */
function patient_link(string $name, int $id): string {
    return '<form method="POST" action="../patients/view.php" style="display:inline">'
         . '<input type="hidden" name="id" value="' . $id . '">'
         . '<button type="submit" style="background:none;border:none;padding:0;color:var(--teal);font-weight:600;cursor:pointer;font-family:inherit;font-size:inherit">' . htmlspecialchars($name) . '</button>'
         . '</form>';
}

/* ── Inline patient name as a plain text-styled POST button (for milestone cards) ── */
function patient_link_plain(string $name, int $id): string {
    return '<form method="POST" action="../patients/view.php" style="display:inline">'
         . '<input type="hidden" name="id" value="' . $id . '">'
         . '<button type="submit" style="background:none;border:none;padding:0;color:var(--navy);font-weight:600;cursor:pointer;font-family:inherit;font-size:.9rem">' . htmlspecialchars($name) . '</button>'
         . '</form>';
}
?>

<div class="page-header">
  <h1>Birthday Report</h1>
</div>

<?php
// ── SQL: Birthdays in next 30 days ──────────────────────────────────────────
$upcoming_bdays = $conn->query("
    SELECT
        p.patient_id,
        p.name,
        p.phone,
        p.dob,
        DATE_FORMAT(p.dob, '%d %b') AS bday_short,
        TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS current_age,
        TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) + 1 AS turning_age,
        DATEDIFF(
            CASE
                WHEN (MAKEDATE(YEAR(CURDATE()),1) + INTERVAL (MONTH(p.dob)-1) MONTH
                     + INTERVAL (LEAST(DAY(p.dob), DAY(LAST_DAY(MAKEDATE(YEAR(CURDATE()),1) + INTERVAL (MONTH(p.dob)-1) MONTH)))-1) DAY)
                     >= CURDATE()
                THEN (MAKEDATE(YEAR(CURDATE()),1) + INTERVAL (MONTH(p.dob)-1) MONTH
                     + INTERVAL (LEAST(DAY(p.dob), DAY(LAST_DAY(MAKEDATE(YEAR(CURDATE()),1) + INTERVAL (MONTH(p.dob)-1) MONTH)))-1) DAY)
                ELSE (MAKEDATE(YEAR(CURDATE())+1,1) + INTERVAL (MONTH(p.dob)-1) MONTH
                     + INTERVAL (LEAST(DAY(p.dob), DAY(LAST_DAY(MAKEDATE(YEAR(CURDATE())+1,1) + INTERVAL (MONTH(p.dob)-1) MONTH)))-1) DAY)
            END,
            CURDATE()
        ) AS days_until
    FROM patients p
    HAVING days_until BETWEEN 0 AND 30
    ORDER BY days_until
");

if (!$upcoming_bdays) {
    echo "<div class='alert alert-error'>Birthday query error: " . htmlspecialchars($conn->error) . "</div>";
}

// ── SQL: Today's birthdays ───────────────────────────────────────────────────
$today_bdays = $conn->query("
    SELECT patient_id, name,
           TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS turning_age
    FROM patients
    WHERE MONTH(dob) = MONTH(CURDATE()) AND DAY(dob) = DAY(CURDATE())
");

// ── SQL: Milestone ages this year ────────────────────────────────────────────
$age40 = $conn->query("SELECT patient_id, name, dob, DATE_FORMAT(dob,'%d %b') AS bday_short, 40 AS turning_age FROM patients WHERE YEAR(CURDATE()) - YEAR(dob) = 40 ORDER BY MONTH(dob), DAY(dob)");
$age50 = $conn->query("SELECT patient_id, name, dob, DATE_FORMAT(dob,'%d %b') AS bday_short, 50 AS turning_age FROM patients WHERE YEAR(CURDATE()) - YEAR(dob) = 50 ORDER BY MONTH(dob), DAY(dob)");
$age60 = $conn->query("SELECT patient_id, name, dob, DATE_FORMAT(dob,'%d %b') AS bday_short, 60 AS turning_age FROM patients WHERE YEAR(CURDATE()) - YEAR(dob) = 60 ORDER BY MONTH(dob), DAY(dob)");

// ── SQL: Patients grouped by birth month ─────────────────────────────────────
$by_month = $conn->query("
    SELECT MONTH(dob) AS birth_month_num, MONTHNAME(dob) AS birth_month_name, COUNT(*) AS patient_count
    FROM patients
    GROUP BY MONTH(dob), MONTHNAME(dob)
    ORDER BY MONTH(dob)
");
?>

<?php
$tb = [];
if ($today_bdays) { while ($r = $today_bdays->fetch_assoc()) $tb[] = $r; }
if (!empty($tb)):
?>
<div class="alert alert-success">
  🎂 <strong>Today's Birthdays:</strong>
  <?php foreach ($tb as $i => $r): ?>
    <?= htmlspecialchars($r['name']) ?> (turning <?= $r['turning_age'] ?>)<?= ($i < count($tb)-1) ? ', ' : '' ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">🎂 Birthdays in Next 30 Days</div>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Phone</th><th>DOB</th><th>Birthday</th><th>Current Age</th><th>Turning</th><th>Days Away</th></tr>
      </thead>
      <tbody>
        <?php
        $rows = [];
        if ($upcoming_bdays) { while ($r = $upcoming_bdays->fetch_assoc()) $rows[] = $r; }
        if (empty($rows)):
        ?>
        <tr><td colspan="7" class="empty">No birthdays in the next 30 days.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= patient_link($r['name'], $r['patient_id']) ?></td>
          <td><?= htmlspecialchars($r['phone']) ?></td>
          <td><?= $r['dob'] ?></td>
          <td style="font-weight:600">🎂 <?= $r['bday_short'] ?></td>
          <td style="text-align:center"><?= $r['current_age'] ?></td>
          <td style="text-align:center"><span class="badge badge-teal"><?= $r['turning_age'] ?></span></td>
          <td style="text-align:center">
            <?= $r['days_until'] == 0 ? '<span class="badge badge-green">Today!</span>' : '<span class="badge badge-amber">'.$r['days_until'].' days</span>' ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem">
<?php
$milestones = [
    ['query'=>$age40,'age'=>40,'color'=>'teal'],
    ['query'=>$age50,'age'=>50,'color'=>'amber'],
    ['query'=>$age60,'age'=>60,'color'=>'red'],
];
foreach ($milestones as $m):
?>
  <div class="card">
    <div class="card-title">Turning <?= $m['age'] ?> This Year</div>
    <?php
    $mrows = [];
    if ($m['query']) { while ($r = $m['query']->fetch_assoc()) $mrows[] = $r; }
    if (empty($mrows)):
    ?>
    <p style="color:var(--slate);font-size:.85rem">No patients turning <?= $m['age'] ?> this year.</p>
    <?php else: foreach ($mrows as $r): ?>
    <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)">
      <div>
        <?= patient_link_plain($r['name'], $r['patient_id']) ?>
        <div style="font-size:.75rem;color:var(--slate)">DOB: <?= $r['dob'] ?></div>
      </div>
      <span class="badge badge-<?= $m['color'] ?>">🎂 <?= $r['bday_short'] ?></span>
    </div>
    <?php endforeach; endif; ?>
  </div>
<?php endforeach; ?>
</div>

<div class="card">
  <div class="card-title">📊 Patients Grouped by Birth Month</div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Month</th><th>#</th><th>Patients</th><th>Distribution</th></tr></thead>
      <tbody>
        <?php
        $bm_rows = [];
        if ($by_month) { while ($r = $by_month->fetch_assoc()) $bm_rows[] = $r; }
        $max_bm = $bm_rows ? max(array_column($bm_rows,'patient_count')) : 1;
        foreach ($bm_rows as $r):
          $pct = round($r['patient_count']/$max_bm*100);
        ?>
        <tr>
          <td style="font-weight:600"><?= $r['birth_month_name'] ?></td>
          <td style="color:var(--slate);font-size:.82rem"><?= $r['birth_month_num'] ?></td>
          <td style="text-align:center"><span class="badge badge-teal"><?= $r['patient_count'] ?></span></td>
          <td style="width:200px">
            <div style="background:var(--sage);border-radius:4px;height:10px">
              <div style="background:var(--teal);width:<?= $pct ?>%;height:10px;border-radius:4px"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($bm_rows)): ?>
        <tr><td colspan="4" class="empty">No patient data.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
