<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// ── SQL: All visits with date calculations ───────────────────────────────────
$result = $conn->query("
    SELECT
        v.visit_id,
        p.patient_id,
        p.name,
        DATE_FORMAT(v.visit_date, '%d %b %Y')          AS visit_date_fmt,
        DATEDIFF(CURDATE(), v.visit_date)              AS days_ago,
        v.consultation_fee,
        v.lab_fee,
        v.consultation_fee + v.lab_fee                 AS total_fee,
        DATE_FORMAT(v.follow_up_due, '%d %b %Y')       AS follow_up_fmt,
        v.follow_up_due,
        CASE
            WHEN v.follow_up_due < CURDATE()
                THEN 'overdue'
            WHEN v.follow_up_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                THEN 'upcoming'
            ELSE 'scheduled'
        END                                             AS follow_status,
        -- Days overdue or days until due
        CASE
            WHEN v.follow_up_due < CURDATE()
                THEN CONCAT(DATEDIFF(CURDATE(), v.follow_up_due), ' days overdue')
            ELSE CONCAT(DATEDIFF(v.follow_up_due, CURDATE()), ' days away')
        END                                             AS followup_delta
    FROM visits v
    JOIN patients p ON p.patient_id = v.patient_id
    ORDER BY v.visit_date DESC
");

// ── SQL: Summary ─────────────────────────────────────────────────────────────
$summary = $conn->query("
    SELECT
        COUNT(*)                                                     AS total,
        SUM(consultation_fee + lab_fee)                             AS total_revenue,
        SUM(CASE WHEN follow_up_due < CURDATE() THEN 1 ELSE 0 END) AS overdue,
        SUM(CASE WHEN follow_up_due BETWEEN CURDATE()
                  AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS upcoming
    FROM visits
")->fetch_assoc();

/* ── Helper: inline POST form button ── */
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
    <h1>All Visits</h1>
    <p>Listing all visits — days since visit &amp; follow-up status</p>
  </div>
  <a href="add.php" class="btn btn-primary">+ Record Visit</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $summary['total'] ?></div><div class="lbl">Total Visits</div></div>
  <div class="stat-card green"><div class="num">₹<?= number_format($summary['total_revenue'], 0) ?></div><div class="lbl">Total Revenue</div></div>
  <div class="stat-card red"><div class="num"><?= $summary['overdue'] ?></div><div class="lbl">Overdue Follow-Ups</div></div>
  <div class="stat-card amber"><div class="num"><?= $summary['upcoming'] ?></div><div class="lbl">Upcoming (7 days)</div></div>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Patient</th>
          <th>Visit Date</th>
          <th>Days Ago</th>
          <th>Consult (₹)</th>
          <th>Lab (₹)</th>
          <th>Total (₹)</th>
          <th>Follow-Up Due</th>
          <th>Delta</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <tr><td colspan="10" class="empty"><div class="ico">📋</div>No visits recorded.</td></tr>
        <?php else: ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td style="color:var(--slate);font-size:.8rem"><?= $r['visit_id'] ?></td>
          <td>
            <!-- Patient name → POST to patients/view.php -->
            <form method="POST" action="../patients/view.php" style="display:inline">
              <input type="hidden" name="id" value="<?= $r['patient_id'] ?>">
              <button type="submit" style="background:none;border:none;padding:0;color:var(--teal);font-weight:600;cursor:pointer;font-family:inherit;font-size:inherit"><?= htmlspecialchars($r['name']) ?></button>
            </form>
          </td>
          <td><?= $r['visit_date_fmt'] ?></td>
          <td style="text-align:center"><?= $r['days_ago'] ?> d</td>
          <td>₹<?= number_format($r['consultation_fee'], 0) ?></td>
          <td>₹<?= number_format($r['lab_fee'], 0) ?></td>
          <td style="font-weight:600">₹<?= number_format($r['total_fee'], 0) ?></td>
          <td><?= $r['follow_up_fmt'] ?></td>
          <td style="font-size:.78rem;color:var(--slate)"><?= $r['followup_delta'] ?></td>
          <td>
            <?php
            $sc = ['overdue'=>'badge-red','upcoming'=>'badge-amber','scheduled'=>'badge-green'];
            $sl = ['overdue'=>'Overdue','upcoming'=>'Upcoming','scheduled'=>'Scheduled'];
            $s = $r['follow_status'];
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
