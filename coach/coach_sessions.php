<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['coach']);
$pageTitle = 'Sessions';
require __DIR__ . '/../header.php';
require_once __DIR__ . '/../pagination.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Load both paginated result sets before rendering either table.
$pendingPage = pagedQuery($pdo, "SELECT cs.*, u.username as member_name FROM coaching_sessions cs JOIN users u ON cs.member_id = u.id WHERE cs.coach_id = ? AND cs.status = 'pending' ORDER BY cs.session_date ASC", [$_SESSION['user_id']], 'pending_page');
            $pending_sessions = $pendingPage['rows'];
$approvedPage = pagedQuery($pdo, "SELECT cs.*, u.username as member_name, EXISTS (SELECT 1 FROM coach_schedules sch WHERE sch.coach_id = cs.coach_id AND sch.available_time = cs.session_date AND DATE_ADD(cs.session_date, INTERVAL sch.duration_minutes MINUTE) <= NOW()) AS can_complete FROM coaching_sessions cs JOIN users u ON cs.member_id = u.id WHERE cs.coach_id = ? AND cs.status = 'approved' ORDER BY cs.session_date ASC", [$_SESSION['user_id']], 'approved_page');
            $approved_sessions = $approvedPage['rows'];
?>
    <div class="card">
        <h3>Pending session requests</h3>
        <div class="table-wrap"><table>
            <tr>
                <th>Member</th>
                <th>Date</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
            <?php


            if (empty($pending_sessions)): ?>
                <tr><td colspan="4" style="text-align: center; color: #888;">No pending session requests.</td></tr>
            <?php else:
                foreach ($pending_sessions as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['member_name']) ?></td>
                        <td><?= htmlspecialchars($s['session_date']) ?></td>
                        <td><?= htmlspecialchars($s['member_notes']) ?></td>
                        <td>
                            <a class="btn btn-green" href="../actions.php?action=update_status&id=<?= $s['id'] ?>&status=approved">Approve</a>
                            <a class="btn btn-red" href="../actions.php?action=update_status&id=<?= $s['id'] ?>&status=rejected">Reject</a>
                            <?php if (strtotime($s['session_date']) > time()): ?>
                            <form method="POST" action="../actions.php?action=cancel_coaching_session" style="display:inline" onsubmit="return confirm('Cancel this session and release the slot?');">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
    <button type="submit" class="btn btn-red">Cancel</button>
</form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; 
            endif; ?>
        </table></div>
        <?php paginationLinks($pendingPage); ?>
    </div>

    <div class="card">
        <h3>My approved sessions</h3>
        <div class="table-wrap"><table>
            <tr>
                <th>Member</th>
                <th>Date</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
            <?php


            if (empty($approved_sessions)): ?>
                <tr><td colspan="4" style="text-align: center; color: #888;">No upcoming booked sessions.</td></tr>
            <?php else:
                foreach ($approved_sessions as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['member_name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['session_date']) ?></td>
                        <td><?= htmlspecialchars($s['member_notes']) ?></td>
                        <td>
                            <?php if ((int)$s['can_complete'] === 1): ?>
                                <a class="btn btn-blue" href="../actions.php?action=update_status&id=<?= $s['id'] ?>&status=completed">Mark completed</a>
                            <?php else: ?>
                                <span style="color: #777;">Not finished yet</span>
                            <?php endif; ?>
                            <?php if (strtotime($s['session_date']) > time()): ?>
                            <form method="POST" action="../actions.php?action=cancel_coaching_session" style="display:inline" onsubmit="return confirm('Cancel this session and release the slot?');">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
    <button type="submit" class="btn btn-red">Cancel</button>
</form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; 
            endif; ?>
        </table></div>
        <?php paginationLinks($approvedPage); ?>
    </div>

<?php
$historyPage = pagedQuery(
    $pdo,
    "SELECT cs.*, u.username AS member_name
     FROM coaching_sessions cs
     JOIN users u ON u.id = cs.member_id
     WHERE cs.coach_id = ? AND cs.status IN ('completed', 'rejected', 'cancelled')
     ORDER BY cs.session_date DESC, cs.id DESC",
    [(int)$_SESSION['user_id']],
    'history_page'
);
?>
<div class="card">
    <h3>Session history</h3>
    <div class="table-wrap"><table>
        <tr><th>Member</th><th>Date</th><th>Status</th></tr>
        <?php foreach ($historyPage['rows'] as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['member_name']) ?></td>
            <td><?= htmlspecialchars($s['session_date']) ?></td>
            <td><?= htmlspecialchars(ucfirst($s['status'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($historyPage['rows'])): ?>
        <tr><td colspan="3">No session history yet.</td></tr>
        <?php endif; ?>
    </table></div>
    <?php paginationLinks($historyPage); ?>
</div>
<?php require __DIR__ . '/../footer.php'; ?>
