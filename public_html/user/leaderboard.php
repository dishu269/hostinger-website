<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

// --- Filter Logic ---
$category = $_GET['category'] ?? 'leads';
$timeframe = $_GET['timeframe'] ?? 'month';

$allowed_categories = ['leads', 'tasks', 'modules'];
if (!in_array($category, $allowed_categories)) {
    $category = 'leads';
}

$allowed_timeframes = ['week', 'month', 'all'];
if (!in_array($timeframe, $allowed_timeframes)) {
    $timeframe = 'month';
}

// --- Dynamic Query Building ---
$select_column = '';
$table_join = '';
$where_clause = '';
$score_column_name = 'Score';

switch ($category) {
    case 'tasks':
        $select_column = 'COUNT(ut.id)';
        $table_join = 'LEFT JOIN user_tasks ut ON ut.user_id = u.id';
        $score_column_name = 'Tasks';
        $date_column = 'ut.completed_at';
        break;
    case 'modules':
        $select_column = 'COUNT(mp.id)';
        $table_join = 'LEFT JOIN module_progress mp ON mp.user_id = u.id AND mp.progress_percent = 100';
        $score_column_name = 'Modules';
        $date_column = 'mp.completed_at';
        break;
    case 'leads':
    default:
        $select_column = 'COUNT(l.id)';
        $table_join = 'LEFT JOIN leads l ON l.user_id = u.id';
        $score_column_name = 'Leads';
        $date_column = 'l.created_at';
        break;
}

$params = [];
switch ($timeframe) {
    case 'week':
        // Assumes MySQL. Starts from Monday.
        $where_clause = "WHERE $date_column >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
        break;
    case 'month':
        $where_clause = "WHERE $date_column >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        break;
    case 'all':
    default:
        $where_clause = '';
        break;
}

$sql = "
    SELECT u.id, u.name, u.avatar_url, {$select_column} AS score
    FROM users u
    {$table_join}
    {$where_clause}
    GROUP BY u.id, u.name, u.avatar_url
    ORDER BY score DESC, u.name ASC
    LIMIT 20
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Bonus: Get current user's rank ---
$current_user_rank = null;
if ($user) {
    // Note: This is a simplified ranking query. For large datasets, a window function like RANK() is better.
    // This subquery calculates the score for the current user.
    $user_score_sql = "SELECT {$select_column} AS score FROM users u {$table_join} {$where_clause} AND u.id = ?";
    $user_score_stmt = $pdo->prepare($user_score_sql);
    $user_score_params = array_merge($params, [$user['id']]);
    // The WHERE clause from the main query might be empty, so we need to adjust the SQL
    if (strpos($user_score_sql, 'WHERE') === false) {
        $user_score_sql = str_replace("AND u.id = ?", "WHERE u.id = ?", $user_score_sql);
    }
    $user_score_stmt = $pdo->prepare($user_score_sql);
    $user_score_stmt->execute($user_score_params);
    $user_score = (int)$user_score_stmt->fetchColumn();

    // This subquery counts how many users have a better score.
    $rank_sql = "
        SELECT COUNT(*) + 1 FROM (
            SELECT u.id, {$select_column} as score
            FROM users u
            {$table_join}
            {$where_clause}
            GROUP BY u.id
            HAVING score > ?
        ) as ranked_users
    ";
    $rank_stmt = $pdo->prepare($rank_sql);
    $rank_params = array_merge($params, [$user_score]);
    $rank_stmt->execute($rank_params);
    $current_user_rank = (int)$rank_stmt->fetchColumn();
    if ($user_score === 0) { // If user has 0 score, their rank is effectively last
        $current_user_rank = 0;
    }
}
?>

<h2>Leaderboard</h2>

<div class="card" style="margin-top:12px">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div class="tabs">
      <a href="?category=leads&timeframe=<?= $timeframe ?>" class="btn <?= $category === 'leads' ? '' : 'btn-outline' ?>">Leads</a>
      <a href="?category=tasks&timeframe=<?= $timeframe ?>" class="btn <?= $category === 'tasks' ? '' : 'btn-outline' ?>">Tasks</a>
      <a href="?category=modules&timeframe=<?= $timeframe ?>" class="btn <?= $category === 'modules' ? '' : 'btn-outline' ?>">Modules</a>
    </div>
    <div class="tabs">
      <a href="?category=<?= $category ?>&timeframe=week" class="btn <?= $timeframe === 'week' ? '' : 'btn-outline' ?>">This Week</a>
      <a href="?category=<?= $category ?>&timeframe=month" class="btn <?= $timeframe === 'month' ? '' : 'btn-outline' ?>">This Month</a>
      <a href="?category=<?= $category ?>&timeframe=all" class="btn <?= $timeframe === 'all' ? '' : 'btn-outline' ?>">All Time</a>
    </div>
  </div>
</div>

<div class="card" style="margin-top:12px">
  <table>
    <thead><tr><th>#</th><th>Name</th><th><?= htmlspecialchars($score_column_name) ?></th></tr></thead>
    <tbody>
      <?php if (empty($leaders)): ?>
        <tr><td colspan="3" style="text-align:center;">No data for this period.</td></tr>
      <?php endif; ?>
      <?php
        $medals = ['🥇', '🥈', '🥉'];
        foreach($leaders as $i => $row):
      ?>
      <tr <?= $row['id'] === $user['id'] ? 'style="background-color: #fefce8;"' : '' ?>>
        <td><?= $i + 1 ?> <?= $medals[$i] ?? '' ?></td>
        <td>
            <img src="<?= htmlspecialchars($row['avatar_url'] ?? 'https://placehold.co/32x32/EFEFEF/AAAAAA&text=') ?>" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; vertical-align: middle; margin-right: 8px;">
            <?= htmlspecialchars($row['name']) ?>
        </td>
        <td><strong><?= (int)$row['score'] ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($current_user_rank > 20 || $current_user_rank === 0): ?>
<div class="card" style="margin-top:12px; text-align: center; background-color: #f0f9ff;">
  Your Rank:
  <?php if ($current_user_rank === 0): ?>
    <strong>Not Ranked (Score: 0)</strong>
  <?php else: ?>
    <strong>#<?= $current_user_rank ?> (Score: <?= $user_score ?>)</strong>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


