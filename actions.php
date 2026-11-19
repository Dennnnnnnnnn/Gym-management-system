<?php
require_once 'db.php';
require_once 'auth.php';
$action = $_GET['action'] ?? '';

//user registration
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    //check role creation key for coach and admin
    if (in_array($role, ['coach', 'admin'])) {
        $role_key = $_POST['role_key'] ?? '';
        if ($role_key !== '1234') {
            header("Location: index.php?msg=" . urlencode("invalid role key"));
            exit;
        }
    }

    //get member metrics
    $dob = ($role === 'member' && !empty($_POST['dob'])) ? $_POST['dob'] : null;
    $height = ($role === 'member' && !empty($_POST['height'])) ? (float)$_POST['height'] : null;
    $weight = ($role === 'member' && !empty($_POST['weight'])) ? (float)$_POST['weight'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, dob, height, weight) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role, $dob, $height, $weight]);
        header("Location: index.php?msg=" . urlencode("registration successful"));
    } catch (Exception $e) {
        header("Location: index.php?msg=" . urlencode("account already exists"));
    }
    exit;
}

//user login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
    } else {
        header("Location: index.php?msg=" . urlencode("invalid email or password"));
    }
    exit;
}

//coach slots generation
if (in_array($action, ['add_schedule', 'generate_schedule'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkAccess(['coach']);
    $coach_id = (int)$_SESSION['user_id'];
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $candidates = [];
    $error = '';

    if ($duration < 15 || $duration > 240) {
        $error = 'Session duration must be between 15 and 240 minutes.';
    } elseif ($action === 'add_schedule') {
        $raw = $_POST['available_time'] ?? '';
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw);
        if (!$date || $date->format('Y-m-d\TH:i') !== $raw || $date->getTimestamp() <= time()) {
            $error = 'Choose a valid future start time.';
        } else {
            $candidates[] = $date;
        }
    } else {
        $day = $_POST['schedule_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $break = filter_var($_POST['break_minutes'] ?? null, FILTER_VALIDATE_INT);
        $dayDate = DateTimeImmutable::createFromFormat('!Y-m-d', $day);
        $from = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$day $start");
        $until = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$day $end");
        if (!$dayDate || $dayDate->format('Y-m-d') !== $day ||
            !$from || $from->format('Y-m-d H:i') !== "$day $start" ||
            !$until || $until->format('Y-m-d H:i') !== "$day $end" ||
            $break === false || $break < 0 || $break > 120 || $until <= $from) {
            $error = 'Enter a valid date, time range and break duration.';
        } else {
            $step = $duration + $break;
            for ($time = $from; $time->modify("+$duration minutes") <= $until; $time = $time->modify("+$step minutes")) {
                if ($time->getTimestamp() > time()) $candidates[] = $time;
                if (count($candidates) > 100) break;
            }
            if (count($candidates) > 100) $error = 'Please generate no more than 100 slots at once.';
            elseif (!$candidates) $error = 'No future sessions fit within these working hours.';
        }
    }

    if ($error !== '') {
        header('Location: coach/coach_availability.php?msg=' . urlencode($error));
        exit;
    }

    $lockName = 'gym_schedule_' . $coach_id;
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
    $lockStmt->execute([$lockName]);
    if ((int)$lockStmt->fetchColumn() !== 1) {
        header('Location: coach/coach_availability.php?msg=' . urlencode('Schedule is busy. Please try again.'));
        exit;
    }
    $added = 0;
    $skipped = 0;
    try {
        $pdo->beginTransaction();
        $existingStmt = $pdo->prepare('SELECT available_time, duration_minutes FROM coach_schedules WHERE coach_id = ?');
        $existingStmt->execute([$coach_id]);
        $existing = $existingStmt->fetchAll();
        $insert = $pdo->prepare('INSERT INTO coach_schedules (coach_id, available_time, duration_minutes) VALUES (?, ?, ?)');
        foreach ($candidates as $candidate) {
            $begin = $candidate->getTimestamp();
            $finish = $begin + $duration * 60;
            $overlap = false;
            foreach ($existing as $slot) {
                $otherBegin = strtotime($slot['available_time']);
                $otherFinish = $otherBegin + (int)$slot['duration_minutes'] * 60;
                if ($begin < $otherFinish && $finish > $otherBegin) {
                    $overlap = true;
                    break;
                }
            }
            if ($overlap) { $skipped++; continue; }
            $formatted = $candidate->format('Y-m-d H:i:s');
            $insert->execute([$coach_id, $formatted, $duration]);
            $existing[] = ['available_time' => $formatted, 'duration_minutes' => $duration];
            $added++;
        }
        $pdo->commit();
        $message = "$added slot(s) added; $skipped overlapping slot(s) skipped.";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Could not save schedule. Please try again.';
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
    header('Location: coach/coach_availability.php?msg=' . urlencode($message));
    exit;
}

//delete schedule slot
if ($action === 'delete_schedule' && isset($_GET['id'])) {
    checkAccess(['coach']);
    $slot_id = (int)$_GET['id'];
    $coach_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM coach_schedules WHERE id = ? AND coach_id = ? AND is_booked = 0");
    $stmt->execute([$slot_id, $coach_id]);

    header("Location: dashboard.php?msg=" . urlencode("time slot removed"));
    exit;
}

// Reserve the slot under a row lock to prevent double booking.
if ($action === 'book_session_slot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkAccess(['member']);
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $member_id = (int)$_SESSION['user_id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM coach_schedules WHERE id = ? FOR UPDATE');
        $stmt->execute([$slot_id]);
        $slot = $stmt->fetch();
        if (!$slot || $slot['is_booked'] || strtotime($slot['available_time']) <= time()) {
            $pdo->rollBack();
            $message = 'Slot is no longer available.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO coaching_sessions (member_id, coach_id, session_date, member_notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$member_id, $slot['coach_id'], $slot['available_time'], $notes]);
            $stmt = $pdo->prepare('UPDATE coach_schedules SET is_booked = 1 WHERE id = ?');
            $stmt->execute([$slot_id]);
            $pdo->commit();
            $message = 'Session requested.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Booking failed. Please try again.';
    }
    header('Location: member/member_sessions.php?msg=' . urlencode($message));
    exit;
}

// Cancel a pending booking and release its availability.
if ($action === 'cancel_session' && isset($_GET['id'])) {
    checkAccess(['member']);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM coaching_sessions WHERE id = ? AND member_id = ? AND status = 'pending' FOR UPDATE");
        $stmt->execute([(int)$_GET['id'], $_SESSION['user_id']]);
        $session = $stmt->fetch();
        if (!$session) {
            $pdo->rollBack();
            $message = 'Only pending sessions can be cancelled.';
        } else {
            $pdo->prepare('DELETE FROM coaching_sessions WHERE id = ?')->execute([$session['id']]);
            $pdo->prepare('UPDATE coach_schedules SET is_booked = 0 WHERE coach_id = ? AND available_time = ?')
                ->execute([$session['coach_id'], $session['session_date']]);
            $pdo->commit();
            $message = 'Session cancelled.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Cancellation failed.';
    }
    header('Location: member/member_sessions.php?msg=' . urlencode($message));
    exit;
}

// Only allow pending -> approved/rejected and approved -> completed.
if ($action === 'update_status' && isset($_GET['id'], $_GET['status'])) {
    checkAccess(['coach', 'admin']);
    $new = $_GET['status'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM coaching_sessions WHERE id = ? FOR UPDATE');
        $stmt->execute([(int)$_GET['id']]);
        $session = $stmt->fetch();
        $authorized = $session && ($_SESSION['role'] === 'admin' || (int)$session['coach_id'] === (int)$_SESSION['user_id']);
        $valid = $authorized && (
            ($session['status'] === 'pending' && in_array($new, ['approved', 'rejected'], true)) ||
            ($session['status'] === 'approved' && $new === 'completed')
        );
        // A session may be completed only after its scheduled end time.
        // Use the saved slot duration and MySQL's current time consistently.
        if ($valid && $new === 'completed') {
            $timeCheck = $pdo->prepare("SELECT DATE_ADD(cs.session_date, INTERVAL sch.duration_minutes MINUTE) <= NOW() AS has_ended
                FROM coaching_sessions cs
                LEFT JOIN coach_schedules sch ON sch.coach_id = cs.coach_id
                    AND sch.available_time = cs.session_date
                WHERE cs.id = ? AND sch.id IS NOT NULL");
            $timeCheck->execute([(int)$session['id']]);
            $ended = $timeCheck->fetchColumn();
            if ($ended === false || (int)$ended !== 1) {
                $pdo->rollBack();
                $destination = $_SESSION['role'] === 'admin' ? 'admin/admin_sessions.php' : 'coach/coach_sessions.php';
                header('Location: ' . $destination . '?msg=' . urlencode('Cannot complete session before its scheduled end time (or schedule slot is missing).'));
                exit;
            }
        }
        if (!$valid) {
            $pdo->rollBack();
            $message = 'This status change is not allowed.';
        } else {
            $pdo->prepare('UPDATE coaching_sessions SET status = ? WHERE id = ?')->execute([$new, $session['id']]);
            if ($new === 'rejected') {
                $pdo->prepare('UPDATE coach_schedules SET is_booked = 0 WHERE coach_id = ? AND available_time = ?')
                    ->execute([$session['coach_id'], $session['session_date']]);
            }
            $pdo->commit();
            $message = 'Session status updated.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Could not update session.';
    }
    $destination = $_SESSION['role'] === 'admin' ? 'admin/admin_sessions.php' : 'coach/coach_sessions.php';
    header('Location: ' . $destination . '?msg=' . urlencode($message));
    exit;
}

// Coach or admin cancels an upcoming pending/approved session.
// Keep the record for history; release the schedule slot.
if ($action === 'cancel_coaching_session') {
    checkAccess(['coach', 'admin']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    if (!isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $destination = $_SESSION['role'] === 'admin' ? 'admin/admin_sessions.php' : 'coach/coach_sessions.php';
    $sessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    $message = 'Cannot cancel this session.';

    if ($sessionId && $sessionId > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM coaching_sessions WHERE id = ? FOR UPDATE');
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            $authorized = $session && (
                $_SESSION['role'] === 'admin' ||
                (int)$session['coach_id'] === (int)$_SESSION['user_id']
            );
            $cancellable = $authorized &&
                in_array($session['status'], ['pending', 'approved'], true) &&
                strtotime($session['session_date']) > time();

            if ($cancellable) {
                $stmt = $pdo->prepare(
                    "UPDATE coaching_sessions SET status = 'cancelled'
                     WHERE id = ? AND status IN ('pending', 'approved')"
                );
                $stmt->execute([$sessionId]);

                // Release only when no other active booking uses the same slot.
                $stmt = $pdo->prepare(
                    "UPDATE coach_schedules sch
                     SET sch.is_booked = 0
                     WHERE sch.coach_id = ? AND sch.available_time = ?
                     AND NOT EXISTS (
                         SELECT 1 FROM coaching_sessions cs
                         WHERE cs.coach_id = sch.coach_id
                           AND cs.session_date = sch.available_time
                           AND cs.status IN ('pending', 'approved')
                     )"
                );
                $stmt->execute([$session['coach_id'], $session['session_date']]);
                $message = 'Session cancelled. The time slot is available again.';
            } else {
                $message = 'Only upcoming pending or approved sessions can be cancelled.';
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Session cancellation failed: ' . $e->getMessage());
            $message = 'Could not cancel session.';
        }
    }
    header('Location: ' . $destination . '?msg=' . urlencode($message));
    exit;
}

//delete user by admin
if ($action === 'delete_user' && isset($_GET['id'])) {
    checkAccess(['admin']);
    $user_id = (int)$_GET['id'];

    if ($user_id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    header("Location: dashboard.php");
    exit;
}

// Workout plan actions moved to manage_actions.php (POST + CSRF).
if (in_array($action, ['create_plan','reassign_plan','copy_plan','delete_plan','admin_delete_plan'], true)) {
    http_response_code(405);
    exit('Use the workout plan forms.');
}

// Admin removal releases a slot only for an active reservation.
if ($action === 'admin_delete_session' && isset($_GET['id'])) {
    checkAccess(['admin']);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM coaching_sessions WHERE id = ? FOR UPDATE');
        $stmt->execute([(int)$_GET['id']]);
        $session = $stmt->fetch();
        if ($session) {
            $pdo->prepare('DELETE FROM coaching_sessions WHERE id = ?')->execute([$session['id']]);
            if (in_array($session['status'], ['pending', 'approved'], true)) {
                $pdo->prepare('UPDATE coach_schedules SET is_booked = 0 WHERE coach_id = ? AND available_time = ?')
                    ->execute([$session['coach_id'], $session['session_date']]);
            }
        }
        $pdo->commit();
        $message = 'Session removed.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Could not remove session.';
    }
    header('Location: admin/admin_sessions.php?msg=' . urlencode($message));
    exit;
}

//submit member feedback
if ($action === 'submit_feedback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkAccess(['member']);
    $session_id = (int)$_POST['session_id'];
    $rating = (int)$_POST['rating'];
    $feedback = trim($_POST['feedback']);
    $member_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE coaching_sessions SET rating = ?, feedback = ? WHERE id = ? AND member_id = ? AND status = 'completed'");
    $stmt->execute([$rating, $feedback, $session_id, $member_id]);

    header("Location: dashboard.php?msg=" . urlencode("thank you for feedback"));
    exit;
}
?>