<?php
require_once __DIR__ . '/../auth.php'; checkAccess(['admin']); require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../pagination.php';
$coaches=$pdo->query("SELECT id,username FROM users WHERE role='coach' ORDER BY username")->fetchAll();
$coach=(int)($_GET['coach_id'] ?? 0); $status=$_GET['status'] ?? 'all';
$sql='SELECT s.*,u.username AS coach_name FROM coach_schedules s JOIN users u ON u.id=s.coach_id WHERE 1=1'; $params=[];
if ($coach>0) { $sql.=' AND s.coach_id=?'; $params[]=$coach; }
if ($status==='booked' || $status==='available') { $sql.=' AND s.is_booked=?'; $params[]=$status==='booked'?1:0; }
$page=pagedQuery($pdo,$sql.' ORDER BY s.available_time DESC,s.id DESC',$params);
$pageTitle='Coach Availability'; require __DIR__ . '/../header.php';
?>
<div class="card"><h3>All coach schedule slots</h3>
<form method="get" class="search-form"><select name="coach_id"><option value="0">All coaches</option><?php foreach ($coaches as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $coach===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['username']) ?></option><?php endforeach; ?></select>
<select name="status"><option value="all">All statuses</option><option value="available" <?= $status==='available'?'selected':'' ?>>Available</option><option value="booked" <?= $status==='booked'?'selected':'' ?>>Booked</option></select><button>Filter</button><a href="admin_availability.php">Clear</a></form>
<div class="table-wrap"><table><tr><th>Coach</th><th>Start</th><th>Duration</th><th>Status</th></tr>
<?php foreach ($page['rows'] as $s): ?><tr><td><?= htmlspecialchars($s['coach_name']) ?></td><td><?= htmlspecialchars($s['available_time']) ?></td><td><?= (int)$s['duration_minutes'] ?> min</td><td><?= $s['is_booked']?'Booked':'Available' ?></td></tr><?php endforeach; ?>
<?php if (!$page['rows']): ?><tr><td colspan="4">No slots match the filters.</td></tr><?php endif; ?>
</table></div><?php paginationLinks($page); ?></div><?php require __DIR__ . '/../footer.php'; ?>
