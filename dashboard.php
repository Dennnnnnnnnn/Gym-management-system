<?php
require_once 'auth.php';
checkAccess();
$pageTitle = 'Dashboard';
require 'header.php';
?>
<div class="card">
 <h3>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h3>
 <p>Role: <strong><?= htmlspecialchars(strtoupper($_SESSION['role'])) ?></strong></p>
 <p>Select a section to manage your gym activities.</p>
 <div class="quick-links">
 <?php foreach (($navigation[$role] ?? []) as $label => $url): ?>
   <a class="btn" href="<?= htmlspecialchars(appUrl($url)) ?>"><?= htmlspecialchars($label) ?></a>
 <?php endforeach; ?>
 </div>
</div>
<?php if ($role === 'member'):
 $stmt = $pdo->prepare('SELECT dob, height, weight FROM users WHERE id = ?');
 $stmt->execute([$_SESSION['user_id']]);
 $profile = $stmt->fetch();
?>
<div class="card">
 <h3>My physical stats</h3>
 <p>Birth date: <?= htmlspecialchars($profile['dob'] ?? 'N/A') ?> | Height: <?= htmlspecialchars($profile['height'] ?? 'N/A') ?> cm | Weight: <?= htmlspecialchars($profile['weight'] ?? 'N/A') ?> kg</p>
</div>
<?php endif; ?>
<?php if ($role === 'member'): ?>
<div class="card"><h3>Update my height and weight</h3>
<?php if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); ?>
<form method="post" action="manage_actions.php">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="update_metrics">
<label>Height (cm)</label><br><input type="number" name="height" step="0.1" min="0.1" max="300" value="<?= htmlspecialchars($profile['height'] ?? '') ?>"><br><br>
<label>Weight (kg)</label><br><input type="number" name="weight" step="0.1" min="0.1" max="600" value="<?= htmlspecialchars($profile['weight'] ?? '') ?>"><br><br>
<button>Save measurements</button>
</form></div>
<?php endif; ?>
<?php require 'footer.php'; ?>
