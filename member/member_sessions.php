<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['member']);
$pageTitle = 'My Sessions';
require __DIR__ . '/../header.php';
require_once __DIR__ . '/../pagination.php';
?>
    <div class="card">
        <h3>My booked sessions</h3>

<!--status filter form-->
        <form method="GET" action="member_sessions.php" class="filter-form">
            <label>Filter by Status:</label>
            <select name="member_status_filter" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?= ($_GET['member_status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($_GET['member_status_filter'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($_GET['member_status_filter'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="completed" <?= ($_GET['member_status_filter'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($_GET['member_status_filter'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </form>

        <div class="table-wrap"><table>
            <tr>
                <th>Coach</th>
                <th>Date</th>
                <th>Notes</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
            $params = [$_SESSION['user_id']];
            $where = ["cs.member_id = ?"];

            if (!empty($_GET['member_status_filter'])) {
                $where[] = "cs.status = ?";
                $params[] = $_GET['member_status_filter'];
            }

            $sql = "SELECT cs.*, u.username as coach_name 
                    FROM coaching_sessions cs 
                    JOIN users u ON cs.coach_id = u.id 
                    WHERE " . implode(" AND ", $where) . " 
                    ORDER BY cs.session_date DESC";

            $sessionsPage = pagedQuery($pdo, $sql, $params);
            $my_sessions = $sessionsPage['rows'];

            if (empty($my_sessions)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">No sessions found.</td></tr>
            <?php else:
                foreach ($my_sessions as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['coach_name']) ?></td>
                        <td><?= htmlspecialchars($s['session_date']) ?></td>
                        <td><?= htmlspecialchars($s['member_notes']) ?></td>
                        <td><strong><?= strtoupper($s['status']) ?></strong></td>
                        <td>
                            <?php if ($s['status'] === 'pending'): ?>
                                <a class="btn btn-red" href="../actions.php?action=cancel_session&id=<?= $s['id'] ?>" onclick="return confirm('Cancel request?');">Cancel request</a>
                            <?php elseif ($s['status'] === 'completed' && empty($s['rating'])): ?>
                                <form action="../actions.php?action=submit_feedback" method="POST" style="margin-top: 5px;">
                                    <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                    <div style="margin-bottom: 5px; font-size: 14px;">
                                        <label><input type="radio" name="rating" value="1" required> 1★</label>
                                        <label><input type="radio" name="rating" value="2"> 2★</label>
                                        <label><input type="radio" name="rating" value="3"> 3★</label>
                                        <label><input type="radio" name="rating" value="4"> 4★</label>
                                        <label><input type="radio" name="rating" value="5" checked> 5★</label>
                                    </div>
                                    <input type="text" name="feedback" placeholder="Leave a comment" style="width: 140px; padding: 4px;">
                                    <button type="submit" style="font-size: 11px; padding: 4px 8px;">Send</button>
                                </form>
                            <?php elseif (!empty($s['rating'])): ?>
                                <span>Rated: <strong><?= $s['rating'] ?>/5★</strong></span>
                            <?php else: ?>
                                <span style="color: #6c757d; font-style: italic;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </table></div>
        <?php paginationLinks($sessionsPage); ?>
    </div>

<?php require __DIR__ . '/../footer.php'; ?>
