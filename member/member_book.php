<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['member']);
$pageTitle = 'Book a Session';
require __DIR__ . '/../header.php';
?>
    <div class="card">
        <h3>Book a coaching session</h3>
        
        <?php $selected_coach_id = isset($_GET['filter_coach']) ? (int)$_GET['filter_coach'] : 0; ?>
        
        <form method="GET" action="member_book.php" style="margin-bottom: 15px;">
            <label><strong>1. Choose a coach:</strong></label><br>
            <select name="filter_coach" onchange="this.form.submit()" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="0">Select a coach to view available slots</option>
                <?php
                $coaches = $pdo->query("SELECT id, username FROM users WHERE role = 'coach'")->fetchAll();
                foreach ($coaches as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selected_coach_id === $c['id'] ? 'selected' : '' ?>>
                        Coach <?= htmlspecialchars($c['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selected_coach_id > 0): ?>
            <form action="../actions.php?action=book_session_slot" method="POST">
                <label><strong>2. Select available time slot:</strong></label><br>
                <select name="slot_id" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    <?php
                    $stmt = $pdo->prepare("SELECT id, available_time, duration_minutes FROM coach_schedules WHERE coach_id = ? AND is_booked = 0 AND available_time > NOW() ORDER BY available_time ASC");
                    $stmt->execute([$selected_coach_id]);
                    $slots = $stmt->fetchAll();

                    if (empty($slots)): ?>
                        <option value="">No open slots available for this coach</option>
                    <?php else:
                        foreach ($slots as $slot): ?>
                            <option value="<?= $slot['id'] ?>">
                                <?= htmlspecialchars($slot['available_time']) ?> (Duration: <?= (int)$slot['duration_minutes'] ?> mins)
                            </option>
                        <?php endforeach;
                    endif; ?>
                </select><br><br>

                <label>Notes for coach:</label><br>
                <textarea name="notes" rows="3" placeholder="e.g. Focus on chest and triceps" style="width: 100%;"></textarea><br><br>

                <button type="submit" <?= empty($slots) ? 'disabled' : '' ?>>Submit booking request</button>
            </form>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../footer.php'; ?>
