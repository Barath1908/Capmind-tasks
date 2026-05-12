<?php
session_start();
require_once '../config/db.php';
$pageTitle = 'Patient List';

// ── Inputs ──
$src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$type  = $src['search_type'] ?? 'all';
$name  = trim($src['search_name'] ?? '');
$above = trim($src['age_above']   ?? '');
$from  = trim($src['age_from']    ?? '');
$to    = trim($src['age_to']      ?? '');
$sort  = in_array($src['sort'] ?? '', ['id','patient_name','age','created_at']) ? $src['sort'] : 'id';
$order = strtoupper($src['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$page  = max(1, (int)($src['page'] ?? 1));
$limit = 5;

// ── WHERE clause ──
$where = ''; $params = []; $types = '';
switch ($type) {
    case 'age_above':
        if ($above !== '' && is_numeric($above)) { $where = 'WHERE p.age > ?'; $params = [(int)$above]; $types = 'i'; }
        break;
    case 'age_between':
        if ($from !== '' && $to !== '' && is_numeric($from) && is_numeric($to)) { $where = 'WHERE p.age BETWEEN ? AND ?'; $params = [(int)$from,(int)$to]; $types = 'ii'; }
        break;
    case 'name':
        if ($name !== '') { $where = 'WHERE p.patient_name LIKE ?'; $params = ["%$name%"]; $types = 's'; }
        break;
    default: $type = 'all';
}

// ── Count & paginate ──
$cs = $conn->prepare("SELECT COUNT(*) FROM patients p $where");
if ($params) $cs->bind_param($types, ...$params);
$cs->execute();
$total      = $cs->get_result()->fetch_row()[0];//1
$totalPages = max(1, (int)ceil($total / $limit));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $limit;

// ── Fetch ──
$sql  = "SELECT p.*, d.doctor_name, d.specialization FROM patients p LEFT JOIN doctors d ON p.doctor_id=d.id $where ORDER BY p.$sort $order LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
$stmt->execute();
$rows = $stmt->get_result();

// ── Stats ──
$stats = $conn->query("SELECT COUNT(*) total, SUM(gender='Male') males, SUM(gender='Female') females, ROUND(AVG(age),1) avg_age FROM patients")->fetch_assoc();


function icon($col,$cur,$ord) {
    if($col!==$cur) return '<i class="bi bi-arrow-down-up ms-1 opacity-50"></i>';
    return $ord==='ASC'?'<i class="bi bi-sort-up ms-1"></i>':'<i class="bi bi-sort-down ms-1"></i>';
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1><span class="header-icon"><i class="bi bi-people-fill"></i></span> Patient Records</h1>
    <a href="create.php" class="btn-teal"><i class="bi bi-person-plus-fill"></i> Add New Patient</a>
</div>

<!-- Stats -->
<div class="stats-grid mb-4">
    <?php
    $cards = [
        ['teal','bi-people-fill',(int)$stats['total'],'Total Patients'],
        ['green','bi-gender-male',(int)$stats['males'],'Male'],
        ['amber','bi-gender-female',(int)$stats['females'],'Female'],
        ['red','bi-graph-up',$stats['avg_age']??'—','Avg Age'],
    ];
    foreach ($cards as [$color,$icon,$val,$label]): ?>
    <div class="stat-card">
        <div class="stat-icon <?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
        <div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-header-custom"><i class="bi bi-funnel-fill"></i> Search &amp; Filter</div>
    <div class="card-body p-3">
        <div class="query-tabs mb-3">
            <?php
            $tabs = [
                ['panel-name',       'bi-search',   'Search by Name', $type==='name'],
                ['panel-age-above',  'bi-person-up','Age Above',      $type==='age_above'],
                ['panel-age-between','bi-sliders',  'Age Between',    $type==='age_between'],
            ];
            foreach ($tabs as [$panel,$ico,$label,$active]): ?>
            <a href="#" onclick="showPanel('<?= $panel ?>');return false"
               class="query-tab <?= $active?'active':'' ?>">
                <i class="bi <?= $ico ?>"></i> <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Name -->
        <form method="POST" action="list.php#results" id="panel-name" class="filter-panel d-none">
            <input type="hidden" name="search_type" value="name">
            <input type="hidden" name="sort" value="<?= $sort ?>">
            <input type="hidden" name="order" value="<?= $order ?>">
            <div class="form-group">
                <label class="form-label">Patient Name</label>
                <div class="search-wrap">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search_name" class="form-control" placeholder="e.g. Kumar, Priya…" value="<?= htmlspecialchars($name) ?>">
                </div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                <button class="btn-teal"><i class="bi bi-search me-1"></i>Search</button>
                <a href="list.php" class="btn-outline">Clear</a>
            </div>
        </form>

        <!-- Age Above -->
        <form method="POST" action="list.php#results" id="panel-age-above" class="filter-panel d-none">
            <input type="hidden" name="search_type" value="age_above">
            <input type="hidden" name="sort" value="<?= $sort ?>">
            <input type="hidden" name="order" value="<?= $order ?>">
            <div class="form-group">
                <label class="form-label">Patients older than</label>
                <div class="input-group">
                    <span class="input-group-text">Age &gt;</span>
                    <input type="number" name="age_above" class="form-control" placeholder="e.g. 40" min="0" max="130" value="<?= htmlspecialchars($above) ?>">
                    <span class="input-group-text">yrs</span>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                <button class="btn-teal"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
                <a href="list.php" class="btn-outline">Clear</a>
            </div>
        </form>

        <!-- Age Between -->
        <form method="POST" action="list.php#results" id="panel-age-between" class="filter-panel d-none">
            <input type="hidden" name="search_type" value="age_between">
            <input type="hidden" name="sort" value="<?= $sort ?>">
            <input type="hidden" name="order" value="<?= $order ?>">
            <div class="form-group">
                <label class="form-label">Age range</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" name="age_from" class="form-control" placeholder="From" min="0" max="130" value="<?= htmlspecialchars($from) ?>">
                    <span class="text-muted fw-bold">—</span>
                    <input type="number" name="age_to" class="form-control" placeholder="To" min="0" max="130" value="<?= htmlspecialchars($to) ?>">
                    <span class="text-muted" style="white-space:nowrap">yrs</span>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                <button class="btn-teal"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
                <a href="list.php" class="btn-outline">Clear</a>
            </div>
        </form>

        <!-- Sort -->
        <div class="sort-controls">
            <span class="sort-label"><i class="bi bi-sort-alpha-down me-1"></i>Order By:</span>
            <?php foreach (['patient_name'=>'Name','age'=>'Age','created_at'=>'Date Registered'] as $col=>$label):
                $newOrder = ($sort===$col && $order==='ASC') ? 'DESC' : 'ASC';
            ?>
            <form method="POST" action="list.php#results" style="display:inline">
                <input type="hidden" name="search_type" value="<?= $type ?>">
                <input type="hidden" name="search_name" value="<?= htmlspecialchars($name) ?>">
                <input type="hidden" name="age_above"   value="<?= htmlspecialchars($above) ?>">
                <input type="hidden" name="age_from"    value="<?= htmlspecialchars($from) ?>">
                <input type="hidden" name="age_to"      value="<?= htmlspecialchars($to) ?>">
                <input type="hidden" name="sort"        value="<?= $col ?>">
                <input type="hidden" name="order"       value="<?= $newOrder ?>">
                <input type="hidden" name="page"        value="1">
                <button type="submit" class="<?= $sort===$col?'btn-sort-active':'btn-sort' ?>">
                    <?= $label ?> <?= icon($col,$sort,$order) ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($type !== 'all'): ?>
<div class="mb-3"><a href="list.php" class="btn-outline" style="font-size:.8rem">✕ Clear filter</a></div>
<?php endif; ?>

<!-- Table -->
<div class="table-wrapper mb-3" id="results">
    <table>
        <thead><tr>
            <th>#</th><th>Patient Name</th><th>Contact</th><th>Age</th>
            <th>Gender</th><th>Diagnosis</th><th>Assigned Doctor</th><th>Registered</th>
            <th style="width:90px">Actions</th>
        </tr></thead>
        <tbody>
        <?php $n = $offset+1; $has = false; 
              while ($r = $rows->fetch_assoc()): $has = true; ?>
        <tr>
            <td class="text-muted"><?= $n++ ?></td>
            <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['email']) ?><br><span class="pagination-info"><?= htmlspecialchars($r['phone']) ?></span></td>
            <td><?= $r['age'] ?> yrs</td>
            <td><span class="badge-gender badge-<?= strtolower($r['gender']) ?>"><?= $r['gender'] ?></span></td>
            <td><span class="badge-diagnosis"><?= htmlspecialchars($r['diagnosis']) ?></span></td>
            <td>
                <?php if (!empty($r['doctor_name'])): ?>
                    <?= htmlspecialchars($r['doctor_name']) ?><br><span class="pagination-info"><?= htmlspecialchars($r['specialization']) ?></span>
                <?php else: ?>
                    <span class="text-muted fst-italic" style="font-size:.8rem">Not assigned</span>
                <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:.8rem"><?= date('d M Y',strtotime($r['created_at'])) ?></td>
            <td>
                <div class="d-flex gap-1">
                    <form method="POST" action="edit.php" style="display:inline">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn-action btn-edit" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </form>
                    <form method="POST" action="delete.php" style="display:inline"
                        onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($r['patient_name'])) ?>? This cannot be undone.')">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn-action btn-delete" title="Delete">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?>
        <tr><td colspan="9"><div class="no-records"><i class="bi bi-inbox"></i>No patients match your filter.</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination-wrapper">
    <span class="pagination-info">Showing <?= $offset+1 ?>–<?= min($offset+$limit,$total) ?> of <?= $total ?> patients</span>
    <nav><ul class="pagination">
        <?php
        // Helper: renders a hidden-field POST form as a pagination button
        function pageForm($label, $targetPage, $type, $name, $above, $from, $to, $sort, $order, $disabled, $active) {
            $cls = $disabled ? 'page-link disabled' : ($active ? 'page-link active-page' : 'page-link');
            if ($disabled) { echo "<li class='page-item disabled'><span class='page-link'>$label</span></li>"; return; }
            echo "
            <li class='page-item'>
            <form method='POST' action='list.php#results' style='display:inline'>
                <input type='hidden' name='search_type' value='$type'>
                <input type='hidden' name='search_name' value='" . htmlspecialchars($name) . "'>
                <input type='hidden' name='age_above'   value='" . htmlspecialchars($above) . "'>
                <input type='hidden' name='age_from'    value='" . htmlspecialchars($from) . "'>
                <input type='hidden' name='age_to'      value='" . htmlspecialchars($to) . "'>
                <input type='hidden' name='sort'        value='$sort'>
                <input type='hidden' name='order'       value='$order'>
                <input type='hidden' name='page'        value='$targetPage'>
                <button type='submit' class='$cls'>$label</button>
            </form></li>";
        }
        ?>
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <?php pageForm('<i class="bi bi-chevron-left"></i> Prev', $page-1, $type,$name,$above,$from,$to,$sort,$order, $page<=1, false); ?>
        </li>
        <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
            <?php pageForm($i, $i, $type,$name,$above,$from,$to,$sort,$order, false, $i===$page); ?>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
            <?php pageForm('Next <i class="bi bi-chevron-right"></i>', $page+1, $type,$name,$above,$from,$to,$sort,$order, $page>=$totalPages, false); ?>
        </li>
    </ul></nav>
</div>
<?php endif; ?>

<script>
function showPanel(id) {
    document.querySelectorAll('.filter-panel').forEach(p=>p.classList.add('d-none'));
    document.querySelectorAll('.query-tab').forEach(t=>t.classList.remove('active'));
    const p = document.getElementById(id);
    if (!p) return;
    p.classList.remove('d-none');
    p.querySelector('input:not([type=hidden])')?.focus();
    const i = {'panel-name':0,'panel-age-above':1,'panel-age-between':2}[id];
    if (i !== undefined) document.querySelectorAll('.query-tab')[i].classList.add('active');
}
(()=>{
    const m = {name:'panel-name',age_above:'panel-age-above',age_between:'panel-age-between'};
    const p = m['<?= $type ?>'];
    if (p) document.getElementById(p)?.classList.remove('d-none');
})();
</script>

<?php require_once '../includes/footer.php'; ?>
