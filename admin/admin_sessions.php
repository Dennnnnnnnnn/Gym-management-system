<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['admin']);
$pageTitle = 'All Sessions';
require __DIR__ . '/../header.php';
require_once __DIR__ . '/../pagination.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
    <div class="card">
        <h3>All system coaching sessions</h3>

<!-- status filter form for admin -->
        <form method="GET" action="admin_sessions.php" class="filter-form">
            <label>Filter by Status:</label>
            <select name="admin_status_filter" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?= ($_GET['admin_status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($_GET['admin_status_filter'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($_GET['admin_status_filter'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="completed" <?= ($_GET['admin_status_filter'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($_GET['admin_status_filter'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </form>

        <form method="GET" class="search-form"><input name="search" placeholder="Search member or coach" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"><input type="hidden" name="admin_status_filter" value="<?= htmlspecialchars($_GET['admin_status_filter'] ?? '') ?>"><button type="submit">Search</button></form>
        <div class="table-wrap"><table>
            <tr>
                <th>Member</th>
                <th>Coach</th>
                <th>Date</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Rating</th>
                <th>Action</th>
            </tr>
            <?php
            $where = [];
            $params = [];

            if (!empty($_GET['admin_status_filter'])) {
                $where[] = "cs.status = ?";
                $params[] = $_GET['admin_status_filter'];
            }

            $search = trim($_GET['search'] ?? '');
            if ($search !== '') { $where[] = '(u1.username LIKE ? OR u2.username LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
            $where_clause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

            $sql = "SELECT cs.*, u1.username as member_name, u2.username as coach_name, EXISTS (SELECT 1 FROM coach_schedules sch WHERE sch.coach_id = cs.coach_id AND sch.available_time = cs.session_date AND DATE_ADD(cs.session_date, INTERVAL sch.duration_minutes MINUTE) <= NOW()) AS can_complete 
                    FROM coaching_sessions cs 
                    JOIN users u1 ON cs.member_id = u1.id 
                    JOIN users u2 ON cs.coach_id = u2.id" . $where_clause . " 
                    ORDER BY cs.session_date DESC";

            $sessionsPage = pagedQuery($pdo, $sql, $params);
            foreach ($sessionsPage['rows'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['member_name']) ?></td>
                    <td><?= htmlspecialchars($s['coach_name']) ?></td>
                    <td><?= htmlspecialchars($s['session_date']) ?></td>
                    <td><strong><?= strtoupper($s['status']) ?></strong></td>
                    <td><?= htmlspecialchars($s['member_notes']) ?></td>
                    <td>
                        <?= !empty($s['rating']) ? $s['rating'] . '/5★' : '-' ?>
                    </td>
                    <td>
                        <?php if ($s['status'] === 'pending'): ?>
                            <a class="btn btn-green" href="../actions.php?action=update_status&id=<?= $s['id'] ?>&status=approved">Approve</a>
                            <a class="btn btn-red" href="../actions.php?action=update_status&id=<?= $s['id'] ?>&status=rejected">Reject</a>
                        <?php elseif ($s['status'] === 'approved'): ?>
                            <?php if ((int)$s['can_complete'] === 1): ?>
                                <a class="btn btn-blue" href="../actions.php?action=update_status&id=<?= $s['id'] ?>&status=completed">Mark completed</a>
                            <?php else: ?>
                                <span style="color: #777;">Not finished yet</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (in_array($s['status'], ['pending', 'approved'], true) && strtotime($s['session_date']) > time()): ?>
                            <form method="POST" action="../actions.php?action=cancel_coaching_session" style="display:inline" onsubmit="return confirm('Cancel this session and release the slot?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                                <button type="submit" class="btn btn-red">Cancel</button>
                            </form>
                        <?php endif; ?>

                        <a class="btn btn-red" href="../actions.php?action=admin_delete_session&id=<?= $s['id'] ?>" onclick="return confirm('Delete this session record?');">Delete session</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <?php paginationLinks($sessionsPage); ?>
    </div>

<?php require __DIR__ . '/../footer.php'; ?>
