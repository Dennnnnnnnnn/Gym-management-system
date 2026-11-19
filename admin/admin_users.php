<?php
require_once __DIR__ . '/../auth.php'; checkAccess(['admin']); require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../pagination.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$edit=null;
if (!empty($_GET['edit'])) { $s=$pdo->prepare('SELECT id,username,email,role,dob,height,weight FROM users WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$search=trim((string)($_GET['search'] ?? '')); $roleFilter=(string)($_GET['role'] ?? ''); $where=[];$params=[];
if ($search!=='') { $where[]='(username LIKE ? OR email LIKE ?)';$params[]="%$search%";$params[]="%$search%"; }
if (in_array($roleFilter,['member','coach','admin'],true)) { $where[]='role=?';$params[]=$roleFilter; }
$sql='SELECT id,username,email,role,dob,height,weight FROM users'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY id DESC';
$page=pagedQuery($pdo,$sql,$params); $pageTitle='User Management';require __DIR__ . '/../header.php';
?>
<?php if ($edit): ?><div class="card" id="edit-user"><h3>Edit <?= htmlspecialchars($edit['username']) ?></h3>
<form method="post" action="../manage_actions.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="update_user"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
<label>Username</label><br><input name="username" required maxlength="255" value="<?= htmlspecialchars($edit['username']) ?>"><br><br>
<label>Email</label><br><input type="email" name="email" required value="<?= htmlspecialchars($edit['email']) ?>"><br><br>
<p>Role: <strong><?= htmlspecialchars($edit['role']) ?></strong> (cannot be changed here)</p>
<?php if ($edit['role']==='member'): ?>
<label>Birth date</label><br><input type="date" name="dob" value="<?= htmlspecialchars($edit['dob'] ?? '') ?>"><br><br>
<label>Height (cm)</label><br><input type="number" name="height" step="0.1" min="0.1" max="300" value="<?= htmlspecialchars($edit['height'] ?? '') ?>"><br><br>
<label>Weight (kg)</label><br><input type="number" name="weight" step="0.1" min="0.1" max="600" value="<?= htmlspecialchars($edit['weight'] ?? '') ?>"><br><br>
<?php endif; ?><button>Save changes</button> <a href="admin_users.php">Cancel</a></form></div><?php endif; ?>
<div class="card"><h3>Users</h3><form method="get" class="search-form"><input name="search" placeholder="Username or email" value="<?= htmlspecialchars($search) ?>"><select name="role"><option value="">All roles</option><?php foreach (['member','coach','admin'] as $r): ?><option value="<?= $r ?>" <?= $roleFilter===$r?'selected':'' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select><button>Search</button><a href="admin_users.php">Clear</a></form>
<div class="table-wrap"><table><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Height</th><th>Weight</th><th>Actions</th></tr>
<?php foreach ($page['rows'] as $u): ?><tr><td><?= (int)$u['id'] ?></td><td><?= htmlspecialchars($u['username']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role']) ?></td><td><?= htmlspecialchars($u['height'] ?? '-') ?></td><td><?= htmlspecialchars($u['weight'] ?? '-') ?></td><td><a class="btn" href="?edit=<?= (int)$u['id'] ?>#edit-user">Edit</a></td></tr><?php endforeach; ?>
</table></div><?php paginationLinks($page); ?></div><?php require __DIR__ . '/../footer.php'; ?>
