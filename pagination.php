<?php
// Server-side pagination. Each table can have its own page parameter.
function pagedQuery(PDO $pdo, string $sql, array $params = [], string $pageKey = 'page', int $perPage = 10): array {
    $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS result_count';
    $count = $pdo->prepare($countSql);
    $count->execute(array_values($params));
    $total = (int)$count->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $requested = filter_input(INPUT_GET, $pageKey, FILTER_VALIDATE_INT);
    $page = max(1, min($pages, $requested ?: 1));
    $query = $pdo->prepare($sql . ' LIMIT ? OFFSET ?');
    $i = 1;
    foreach ($params as $value) $query->bindValue($i++, $value);
    $query->bindValue($i++, $perPage, PDO::PARAM_INT);
    $query->bindValue($i, ($page - 1) * $perPage, PDO::PARAM_INT);
    $query->execute();
    return ['rows' => $query->fetchAll(), 'page' => $page, 'pages' => $pages, 'total' => $total, 'key' => $pageKey];
}
function paginationLinks(array $result): void {
    if ($result['pages'] <= 1) return;
    echo '<div class="pagination"><span>Page ' . $result['page'] . ' of ' . $result['pages'] . ' (' . $result['total'] . ' records)</span>';
    $first = max(1, $result['page'] - 2);
    $last = min($result['pages'], $result['page'] + 2);
    for ($p = $first; $p <= $last; $p++) {
        $query = $_GET; $query[$result['key']] = $p;
        $href = htmlspecialchars('?' . http_build_query($query), ENT_QUOTES, 'UTF-8');
        echo '<a ' . ($p === $result['page'] ? 'class="current" ' : '') . 'href="' . $href . '">' . $p . '</a>';
    }
    echo '</div>';
}
