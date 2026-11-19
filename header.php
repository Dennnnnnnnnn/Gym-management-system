<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
checkAccess();
$role = $_SESSION['role'];
$navigation = [
 'member' => ['Book Session' => 'member/member_book.php', 'My Sessions' => 'member/member_sessions.php', 'Workout Plans' => 'member/member_workouts.php'],
 'coach' => ['Availability' => 'coach/coach_availability.php', 'Sessions' => 'coach/coach_sessions.php', 'Workout Plans' => 'coach/coach_workouts.php', 'Feedback' => 'coach/coach_feedback.php'],
 'admin' => ['Users' => 'admin/admin_users.php', 'Sessions' => 'admin/admin_sessions.php', 'Coach Availability' => 'admin/admin_availability.php', 'Templates' => 'admin/admin_templates.php', 'Workout Plans' => 'admin/admin_workouts.php']
];
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> - Gym Coaching Portal</title>
<link rel="stylesheet" href="<?= htmlspecialchars(appUrl('style.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="site-header-inner">
    <h2>Gym Coaching Portal</h2>
    <nav aria-label="Main navigation">
      <a href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>" <?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
      <?php foreach (($navigation[$role] ?? []) as $label => $url): ?>
      <a href="<?= htmlspecialchars(appUrl($url)) ?>" <?= str_ends_with($_SERVER['SCRIPT_NAME'], '/' . $url) ? 'class="active"' : '' ?>><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= htmlspecialchars(appUrl('logout.php')) ?>">Logout</a>
    </nav>
  </div>
</header>
<main class="container">
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php if (isset($_GET['msg'])): ?><div class="message"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
