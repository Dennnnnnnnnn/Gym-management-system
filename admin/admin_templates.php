<?php
require_once __DIR__ . '/../auth.php'; checkAccess(['admin']); require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../pagination.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$edit = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM workout_plans WHERE id=? AND is_template=1');
    $s->execute([(int)$_GET['edit']]); $edit = $s->fetch();
}
$page = pagedQuery($pdo, 'SELECT * FROM workout_plans WHERE is_template=1 ORDER BY id DESC');
$pageTitle = 'Global Workout Templates'; require __DIR__ . '/../header.php';
?>
<div class="card"><h3><?= $edit ? 'Edit template' : 'Create global template' ?></h3>
<form action="../manage_actions.php" method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="save_template">
<input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<label>Title</label><br><input name="title" required maxlength="255" class="full-input" value="<?= htmlspecialchars($edit['title'] ?? '') ?>"><br><br>
<label>Exercises / description</label><br><textarea name="description" required class="full-input" rows="6"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea><br><br>
<button>Save template</button> <?php if ($edit): ?><a href="admin_templates.php">Cancel</a><?php endif; ?>
</form></div>
<div class="card"><h3>Templates available to all coaches</h3><div class="table-wrap"><table>
<tr><th>Title</th><th>Description</th><th>Actions</th></tr>
<?php foreach ($page['rows'] as $t): ?><tr>
<td><?= htmlspecialchars($t['title']) ?></td><td><?= nl2br(htmlspecialchars($t['description'])) ?></td>
<td><a class="btn" href="?edit=<?= (int)$t['id'] ?>">Edit</a>
<form method="post" action="../manage_actions.php" class="inline-form" onsubmit="return confirm('Delete template? Existing copies will remain.')">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete_template"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn-red">Delete</button></form></td></tr><?php endforeach; ?>
</table></div><?php paginationLinks($page); ?></div>
<?php require __DIR__ . '/../footer.php'; ?>
