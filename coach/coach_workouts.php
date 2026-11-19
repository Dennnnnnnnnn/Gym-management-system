<?php
require_once __DIR__ . '/../auth.php'; checkAccess(['coach']); require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../pagination.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$uid = (int)$_SESSION['user_id'];
$members = $pdo->query("SELECT id,username FROM users WHERE role='member' ORDER BY username")->fetchAll();
$templates = $pdo->query('SELECT id,title,description FROM workout_plans WHERE is_template=1 ORDER BY title')->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) { $s=$pdo->prepare('SELECT * FROM workout_plans WHERE id=? AND coach_id=? AND is_template=0'); $s->execute([(int)$_GET['edit'],$uid]); $edit=$s->fetch(); }
$search = trim((string)($_GET['search'] ?? ''));
$sql = 'SELECT wp.*,u.username AS member_name FROM workout_plans wp JOIN users u ON u.id=wp.member_id WHERE wp.coach_id=? AND wp.is_template=0';
$params=[$uid];
if ($search!=='') { $sql.=' AND (wp.title LIKE ? OR u.username LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; }
$page = pagedQuery($pdo,$sql.' ORDER BY wp.created_at DESC,wp.id DESC',$params);
$pageTitle='Workout Plans'; require __DIR__ . '/../header.php';
?>
<div class="card"><h3>Base workout templates (shared)</h3>
<p class="help-text">Use a template to make your own editable copy for a member.</p>
<div class="table-wrap"><table><tr><th>Template</th><th>Exercises</th><th>Copy to member</th></tr>
<?php foreach ($templates as $t): ?><tr><td><?= htmlspecialchars($t['title']) ?></td><td><?= nl2br(htmlspecialchars($t['description'])) ?></td>
<td><form method="post" action="../manage_actions.php" class="inline-form">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="copy_plan"><input type="hidden" name="source_id" value="<?= (int)$t['id'] ?>">
<select name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['username']) ?></option><?php endforeach; ?></select>
<button <?= !$members ? 'disabled' : '' ?>>Use template</button></form></td></tr><?php endforeach; ?>
<?php if (!$templates): ?><tr><td colspan="3">No global templates yet.</td></tr><?php endif; ?>
</table></div></div>
<div class="card" id="plan-form"><h3><?= $edit ? 'Edit my plan' : 'Create personalized plan' ?></h3>
<form method="post" action="../manage_actions.php">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="<?= $edit ? 'update_plan' : 'create_plan' ?>">
<input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<label>Member</label><br><select name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>" <?= (int)($edit['member_id'] ?? 0)===(int)$m['id']?'selected':'' ?>><?= htmlspecialchars($m['username']) ?></option><?php endforeach; ?></select><br><br>
<label>Title</label><br><input class="full-input" name="title" maxlength="255" required value="<?= htmlspecialchars($edit['title'] ?? '') ?>"><br><br>
<label>Exercises / description</label><br><textarea class="full-input" name="description" rows="6" required><?= htmlspecialchars($edit['description'] ?? '') ?></textarea><br><br>
<button <?= !$members ? 'disabled' : '' ?>><?= $edit ? 'Save changes' : 'Create plan' ?></button> <?php if ($edit): ?><a href="coach_workouts.php">Cancel</a><?php endif; ?>
</form></div>
<div class="card"><h3>Plans created by me</h3>
<form class="search-form" method="get"><input name="search" placeholder="Plan title or member" value="<?= htmlspecialchars($search) ?>"><button>Search</button><a href="coach_workouts.php">Clear</a></form>
<div class="table-wrap"><table><tr><th>Member</th><th>Plan</th><th>Description</th><th>Copy to member</th><th>Actions</th></tr>
<?php foreach ($page['rows'] as $plan): ?><tr><td><?= htmlspecialchars($plan['member_name']) ?></td><td><?= htmlspecialchars($plan['title']) ?></td><td><?= nl2br(htmlspecialchars($plan['description'])) ?></td>
<td><form method="post" action="../manage_actions.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="copy_plan"><input type="hidden" name="source_id" value="<?= (int)$plan['id'] ?>">
<select name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['username']) ?></option><?php endforeach; ?></select><button>Copy</button></form></td>
<td><a class="btn" href="?edit=<?= (int)$plan['id'] ?>#plan-form">Edit</a>
<form method="post" action="../manage_actions.php" class="inline-form" onsubmit="return confirm('Delete this plan?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete_plan"><input type="hidden" name="id" value="<?= (int)$plan['id'] ?>"><button class="btn-red">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$page['rows']): ?><tr><td colspan="5">No plans found.</td></tr><?php endif; ?>
</table></div><?php paginationLinks($page); ?></div>
<?php require __DIR__ . '/../footer.php'; ?>
