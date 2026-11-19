<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['coach']);
$pageTitle = 'Feedback';
require __DIR__ . '/../header.php';
require_once __DIR__ . '/../pagination.php';
?>
    <div class="card">
        <h3>Completed sessions</h3>
        <div class="table-wrap"><table>
            <tr>
                <th>Member</th>
                <th>Date</th>
                <th>Feedback</th>
            </tr>
            <?php
            $feedbackPage = pagedQuery($pdo, "SELECT cs.*, u.username as member_name FROM coaching_sessions cs JOIN users u ON cs.member_id = u.id WHERE cs.coach_id = ? AND cs.status = 'completed' ORDER BY cs.session_date DESC", [$_SESSION['user_id']]);
            $completed_sessions = $feedbackPage['rows'];

            if (empty($completed_sessions)): ?>
                <tr><td colspan="3" style="text-align: center; color: #888;">No completed sessions yet</td></tr>
            <?php else:
                foreach ($completed_sessions as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['member_name']) ?></td>
                        <td><?= htmlspecialchars($s['session_date']) ?></td>
                        <td>
                            <?php if (!empty($s['rating'])): ?>
                                <strong><?= $s['rating'] ?>/5★</strong>  <em>"<?= htmlspecialchars($s['feedback']) ?>"</em>
                            <?php else: ?>
                                <span style="color: #888;">No feedback yet</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; 
            endif; ?>
        </table></div>
        <?php paginationLinks($feedbackPage); ?>
    </div>

<?php require __DIR__ . '/../footer.php'; ?>
