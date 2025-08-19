<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

// --- Filter Logic ---
$category = $_GET['category'] ?? 'leads';
$timeframe = $_GET['timeframe'] ?? 'month';

$allowed_categories = ['leads', 'tasks', 'modules'];
if (!in_array($category, $allowed_categories, true)) {
    $category = 'leads';
}

$allowed_timeframes = ['week', 'month', 'all'];
if (!in_array($timeframe, $allowed_timeframes, true)) {
    $timeframe = 'month';
}

// --- Dynamic Query Building ---
$score_column = '';
$table_join = '';
$date_column = '';
$score_column_name = 'Score';

switch ($category) {
    case 'tasks':
        $score_column = 'COUNT(ut.id)';
        $table_join = 'LEFT JOIN user_tasks ut ON ut.user_id = u.id';
        $date_column = 'ut.completed_at';
        $score_column_name = 'Tasks';
        break;
    case 'modules':
        $score_column = 'COUNT(mp.id)';
        $table_join = 'LEFT JOIN module_progress mp ON mp.user_id = u.id AND mp.progress_percent = 100';
        $date_column = 'mp.completed_at';
        $score_column_name = 'Modules';
        break;
    case 'leads':
    default:
        $score_column = 'COUNT(l.id)';
        $table_join = 'LEFT JOIN leads l ON l.user_id = u.id';
        $date_column = 'l.created_at';
        $score_column_name = 'Leads';
        break;
}

$where_clause = '1=1'; // Default to true, simplifies appending AND
$params = [];
if ($timeframe === 'week') {
    $where_clause .= " AND {$date_column} >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
} elseif ($timeframe === 'month') {
    $where_clause .= " AND {$date_column} >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
}

// --- Optimized Query using Window Function (MySQL 8+) ---
$sql = "
    WITH ranked_users AS (
        SELECT
            u.id,
            u.name,
            u.avatar_url,
            {$score_column} AS score,
            RANK() OVER (ORDER BY {$score_column} DESC) as user_rank
        FROM users u
        {$table_join}
        WHERE {$where_clause}
        GROUP BY u.id, u.name, u.avatar_url
    )
    SELECT *
    FROM ranked_users
    WHERE user_rank <= 20 OR id = :user_id
    ORDER BY user_rank ASC, name ASC
";

$params['user_id'] = $user['id'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$leaders = [];
$current_user_from_results = null;

foreach ($results as $row) {
    if ($row['user_rank'] <= 20) {
        $leaders[] = $row;
    }
    if ((int)$row['id'] === (int)$user['id']) {
        $current_user_from_results = $row;
    }
}

// In case the current user is not in the top 20, they are still fetched by the query
$user_score = $current_user_from_results['score'] ?? 0;
$current_user_rank = $current_user_from_results['user_rank'] ?? 0;
if ($user_score === 0) {
    $current_user_rank = 0; // Treat 0 score as unranked
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


