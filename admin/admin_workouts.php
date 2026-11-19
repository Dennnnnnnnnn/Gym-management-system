<?php
require_once __DIR__ . '/../auth.php';checkAccess(['admin']);require_once __DIR__ . '/../db.php';require_once __DIR__ . '/../pagination.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$sql='SELECT wp.*,c.username AS coach_name,m.username AS member_name FROM workout_plans wp JOIN users c ON c.id=wp.coach_id JOIN users m ON m.id=wp.member_id WHERE wp.is_template=0 ORDER BY wp.created_at DESC,wp.id DESC';
$page=pagedQuery($pdo,$sql);$pageTitle='All Personalized Plans';require __DIR__ . '/../header.php';
?>
<div class="card"><h3>Plans created by coaches</h3><div class="table-wrap"><table><tr><th>Coach</th><th>Member</th><th>Title</th><th>Description</th><th>Action</th></tr>
<?php foreach ($page['rows'] as $p): ?><tr><td><?= htmlspecialchars($p['coach_name']) ?></td><td><?= htmlspecialchars($p['member_name']) ?></td><td><?= htmlspecialchars($p['title']) ?></td><td><?= nl2br(htmlspecialchars($p['description'])) ?></td><td>
<form method="post" action="../manage_actions.php" onsubmit="return confirm('Delete plan?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="admin_delete_plan"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn-red">Delete</button></form>
</td></tr><?php endforeach; ?></table></div><?php paginationLinks($page); ?></div><?php require __DIR__ . '/../footer.php'; ?>
