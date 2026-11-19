<?php
require_once 'auth.php';
require_once 'db.php';
checkAccess();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST required'); }
if (!isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    http_response_code(403); exit('Invalid security token. Reload the page.');
}
function redirectTo(string $page, string $message): never {
    header('Location: ' . $page . '?msg=' . urlencode($message)); exit;
}
function memberExists(PDO $pdo, int $id): bool {
    $s = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='member'");
    $s->execute([$id]); return (bool)$s->fetchColumn();
}
$action = (string)($_POST['action'] ?? '');
$role = $_SESSION['role']; $uid = (int)$_SESSION['user_id'];
$admin = ['save_template', 'delete_template', 'update_user', 'admin_delete_plan'];
$coach = ['create_plan', 'update_plan', 'copy_plan', 'delete_plan'];
$member = ['update_metrics'];
if (!(($role === 'admin' && in_array($action, $admin, true)) ||
      ($role === 'coach' && in_array($action, $coach, true)) ||
      ($role === 'member' && in_array($action, $member, true)))) {
    http_response_code(403); exit('Not allowed');
}
$destination = $role === 'admin' ? 'admin/admin_users.php' : ($role === 'coach' ? 'coach/coach_workouts.php' : 'dashboard.php');
try {
    if ($action === 'save_template') {
        $destination = 'admin/admin_templates.php';
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($title === '' || $desc === '' || mb_strlen($title) > 255) redirectTo($destination, 'Title and description required.');
        if ($id) {
            $s = $pdo->prepare('UPDATE workout_plans SET title=?, description=? WHERE id=? AND is_template=1');
            $s->execute([$title, $desc, $id]);
        } else {
            $s = $pdo->prepare('INSERT INTO workout_plans (coach_id,member_id,title,description,is_template) VALUES (?,NULL,?,?,1)');
            $s->execute([$uid, $title, $desc]);
        }
        redirectTo($destination, 'Template saved.');
    }
    if ($action === 'delete_template') {
        $destination = 'admin/admin_templates.php';
        $s = $pdo->prepare('DELETE FROM workout_plans WHERE id=? AND is_template=1');
        $s->execute([(int)($_POST['id'] ?? 0)]);
        redirectTo($destination, 'Template deleted. Existing copies remain.');
    }
    if ($action === 'create_plan' || $action === 'update_plan') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        if (!memberExists($pdo, $memberId) || $title === '' || $desc === '' || mb_strlen($title) > 255) redirectTo($destination, 'Invalid plan details.');
        if ($action === 'create_plan') {
            $s = $pdo->prepare('INSERT INTO workout_plans (coach_id,member_id,title,description,is_template) VALUES (?,?,?,?,0)');
            $s->execute([$uid, $memberId, $title, $desc]);
        } else {
            $s = $pdo->prepare('UPDATE workout_plans SET member_id=?, title=?, description=? WHERE id=? AND coach_id=? AND is_template=0');
            $s->execute([$memberId, $title, $desc, (int)($_POST['id'] ?? 0), $uid]);
        }
        redirectTo($destination, 'Plan saved.');
    }
    if ($action === 'copy_plan') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        $id = (int)($_POST['source_id'] ?? 0);
        if (!memberExists($pdo, $memberId)) redirectTo($destination, 'Select a valid member.');
        $s = $pdo->prepare('SELECT title,description FROM workout_plans WHERE id=? AND (is_template=1 OR (is_template=0 AND coach_id=?))');
        $s->execute([$id, $uid]); $source = $s->fetch();
        if (!$source) redirectTo($destination, 'Source plan not found.');
        $s = $pdo->prepare('INSERT INTO workout_plans (coach_id,member_id,title,description,is_template) VALUES (?,?,?,?,0)');
        $s->execute([$uid, $memberId, $source['title'], $source['description']]);
        redirectTo($destination, 'Independent copy created.');
    }
    if ($action === 'delete_plan') {
        $s = $pdo->prepare('DELETE FROM workout_plans WHERE id=? AND coach_id=? AND is_template=0');
        $s->execute([(int)($_POST['id'] ?? 0), $uid]);
        redirectTo($destination, 'Plan deleted.');
    }
    if ($action === 'admin_delete_plan') {
        $destination = 'admin/admin_workouts.php';
        $s = $pdo->prepare('DELETE FROM workout_plans WHERE id=? AND is_template=0');
        $s->execute([(int)($_POST['id'] ?? 0)]);
        redirectTo($destination, 'Plan deleted.');
    }
    if ($action === 'update_user') {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        if ($id < 1 || $username === '' || mb_strlen($username) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) redirectTo($destination, 'Invalid username or email.');
        $s = $pdo->prepare('SELECT role FROM users WHERE id=?'); $s->execute([$id]); $userRole = $s->fetchColumn();
        if (!$userRole) redirectTo($destination, 'User not found.');
        $dob = null; $height = null; $weight = null;
        if ($userRole === 'member') {
            $dob = trim((string)($_POST['dob'] ?? '')) ?: null;
            if ($dob !== null) {
                $d = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
                if (!$d || $d->format('Y-m-d') !== $dob || $dob > date('Y-m-d')) redirectTo($destination, 'Invalid birth date.');
            }
            $height = metric('height'); $weight = metric('weight');
        }
        $s = $pdo->prepare('UPDATE users SET username=?,email=?,dob=?,height=?,weight=? WHERE id=?');
        $s->execute([$username,$email,$dob,$height,$weight,$id]);
        if ($id === $uid) $_SESSION['username'] = $username;
        redirectTo($destination, 'User updated.');
    }
    if ($action === 'update_metrics') {
        $height = metric('height'); $weight = metric('weight');
        $s = $pdo->prepare("UPDATE users SET height=?,weight=? WHERE id=? AND role='member'");
        $s->execute([$height,$weight,$uid]);
        redirectTo($destination, 'Your height and weight were updated.');
    }
    http_response_code(400); exit('Unknown action');
} catch (InvalidArgumentException $e) {
    redirectTo($destination, $e->getMessage());
} catch (PDOException $e) {
    error_log($e->getMessage()); redirectTo($destination, 'Database error. Check values and try again.');
}
function metric(string $field): ?float {
    $v = trim((string)($_POST[$field] ?? ''));
    if ($v === '') return null;
    $max = $field === 'height' ? 300 : 600;
    if (!is_numeric($v) || !is_finite((float)$v) || (float)$v <= 0 || (float)$v > $max) throw new InvalidArgumentException('Invalid ' . $field . '.');
    return (float)$v;
}
