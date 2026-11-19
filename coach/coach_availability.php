<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['coach']);
$pageTitle = 'Availability';
require __DIR__ . '/../header.php';
require_once __DIR__ . '/../pagination.php';
?>
    <div class="card">
        <h3>Add one time slot</h3>
        <form action="../actions.php?action=add_schedule" method="POST" style="margin-bottom: 15px;">
            <label>Start Time:</label><br>
            <input type="datetime-local" name="available_time" min="<?= date('Y-m-d\TH:i') ?>" required><br><br>
            
            <label>Duration (minutes):</label><br>
            <input type="number" name="duration_minutes" value="60" min="15" step="15" required style="padding: 6px;"><br><br>

            <button type="submit">Add slot</button>
        </form>

        <h3>Generate multiple time slots</h3>
        <p>Choose your working hours. The system will create all sessions that fit in the time range.</p>
        <form action="../actions.php?action=generate_schedule" method="POST" class="schedule-form">
            <label for="schedule_date">Date</label>
            <input id="schedule_date" type="date" name="schedule_date" min="<?= date('Y-m-d') ?>" required>
            <label for="start_time">Start time</label>
            <input id="start_time" type="time" name="start_time" value="09:00" required>
            <label for="end_time">End time</label>
            <input id="end_time" type="time" name="end_time" value="17:00" required>
            <label for="duration_minutes">Session duration (minutes)</label>
            <input id="duration_minutes" type="number" name="duration_minutes" value="60" min="15" max="240" required>
            <label for="break_minutes">Break between sessions (minutes)</label>
            <input id="break_minutes" type="number" name="break_minutes" value="15" min="0" max="120" required>
            <button type="submit">Generate slots</button>
        </form>
        <p class="help-text">Existing overlapping slots will be skipped. Sessions must fit completely before the end time.</p>
        <hr>
        <h4>My schedule slots</h4>
        <form method="GET" class="search-form">
            <label>Status <select name="status"><option value="all">All</option><option value="available" <?= ($_GET['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option><option value="booked" <?= ($_GET['status'] ?? '') === 'booked' ? 'selected' : '' ?>>Booked</option></select></label>
            <label>From <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>"></label>
            <label>To <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>"></label>
            <button type="submit">Filter</button> <a href="coach_availability.php">Clear</a>
        </form>
        <div class="table-wrap"><table>
            <tr><th>Date</th><th>Duration</th><th>Status</th><th>Booked by</th><th>Action</th></tr>
            <?php
            $sql = "SELECT cs.*, (SELECT u.username FROM coaching_sessions sess
 JOIN users u ON u.id = sess.member_id
 WHERE sess.coach_id = cs.coach_id AND sess.session_date = cs.available_time
 AND sess.status IN ('pending','approved','completed')
 ORDER BY CASE WHEN sess.status='approved' THEN 1 WHEN sess.status='pending' THEN 2 ELSE 3 END, sess.id DESC
 LIMIT 1) AS booked_by FROM coach_schedules cs WHERE cs.coach_id = ?";
            $params = [$_SESSION['user_id']];
            $status = $_GET['status'] ?? 'all';
            if ($status === 'available' || $status === 'booked') {
                $sql .= ' AND cs.is_booked = ?';
                $params[] = $status === 'booked' ? 1 : 0;
            }
            foreach (['from', 'to'] as $field) {
                $date = $_GET[$field] ?? '';
                $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if ($parsed && $parsed->format('Y-m-d') === $date) {
                    $sql .= $field === 'from' ? ' AND cs.available_time >= ?' : ' AND cs.available_time < ?';
                    $params[] = $field === 'from' ? $date . ' 00:00:00' : $parsed->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
                }
            }
            $slotsPage = pagedQuery($pdo, $sql . ' ORDER BY cs.available_time ASC', $params);
            $my_slots = $slotsPage['rows'];

            if (empty($my_slots)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">No schedule slots added yet.</td></tr>
            <?php else:
                foreach ($my_slots as $slot): ?>
                    <tr>
                        <td><?= htmlspecialchars($slot['available_time']) ?></td>
                        <td><?= (int)$slot['duration_minutes'] ?> mins</td>
                        <td>
                            <?php if ($slot['is_booked']): ?>
                                <strong style="color: #28a745;">BOOKED</strong>
                            <?php else: ?>
                                <span style="color: #007bff;">AVAILABLE</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $slot['is_booked'] ? htmlspecialchars($slot['booked_by'] ?? 'Unknown member') : '—' ?></td>
                        <td>
                            <?php if (!$slot['is_booked']): ?>
                                <a class="btn btn-red" href="../actions.php?action=delete_schedule&id=<?= $slot['id'] ?>" onclick="return confirm('Remove slot?');">Delete</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </table></div>
        <?php paginationLinks($slotsPage); ?>
    </div>

<?php require __DIR__ . '/../footer.php'; ?>
