<?php
require_once __DIR__ . '/../auth.php';
checkAccess(['member']);
$pageTitle = 'My Workout Plans';
require __DIR__ . '/../header.php';
require_once __DIR__ . '/../pagination.php';
?>
    <div class="card">
        <h3>My assigned workout plans</h3>
        <div class="table-wrap"><table>
            <tr><th>Plan title</th><th>Coach</th><th>Description</th><th>Assigned date</th></tr>
            <?php
            $sql = "SELECT wp.*, u.username as coach_name FROM workout_plans wp JOIN users u ON wp.coach_id = u.id WHERE wp.member_id = ? AND wp.is_template = 0";
            $plansPage = pagedQuery($pdo, $sql . ' ORDER BY wp.created_at DESC', [$_SESSION['user_id']]);
            foreach ($plansPage['rows'] as $plan): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($plan['title']) ?></strong></td>
                    <td><?= htmlspecialchars($plan['coach_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($plan['description'])) ?></td>
                    <td><?= htmlspecialchars($plan['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <?php paginationLinks($plansPage); ?>
    </div>

<?php require __DIR__ . '/../footer.php'; ?>
