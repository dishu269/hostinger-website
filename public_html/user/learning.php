<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

$modules = $pdo->query('SELECT * FROM learning_modules WHERE published = 1 ORDER BY order_index ASC, id DESC')->fetchAll();

// Completion lookup
$stmt = $pdo->prepare('SELECT module_id, progress_percent FROM module_progress WHERE user_id = ?');
$stmt->execute([$user['id']]);
$progressByModule = [];
foreach ($stmt->fetchAll() as $row) { $progressByModule[(int)$row['module_id']] = (int)$row['progress_percent']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $moduleId = (int)($_POST['module_id'] ?? 0);
    $progress = min(100, max(0, (int)($_POST['progress'] ?? 0)));
    $pdo->prepare('INSERT INTO module_progress (user_id, module_id, progress_percent, completed_at) VALUES (?,?,?, CASE WHEN ? = 100 THEN NOW() ELSE NULL END) ON DUPLICATE KEY UPDATE progress_percent = VALUES(progress_percent), completed_at = VALUES(completed_at)')
        ->execute([$user['id'], $moduleId, $progress, $progress]);
    if ($progress === 100) {
      award_achievements_for_user((int)$user['id']);
    }
    header('Location: /user/learning.php');
    exit;
  }
}
?>

<h2>Learning Hub</h2>
<div class="grid cols-2" style="margin-top:12px">
  <?php foreach($modules as $m): $p = $progressByModule[(int)$m['id']] ?? 0; ?>
  <div class="card">
    <strong><?= htmlspecialchars($m['title']) ?></strong>
    <p style="color:#6b7280; margin:6px 0"><?= htmlspecialchars($m['category']) ?></p>
    <p><?= nl2br(htmlspecialchars($m['description'])) ?></p>
    <?php if ($m['content_url']): ?><p><a class="btn btn-outline" href="<?= htmlspecialchars($m['content_url']) ?>" target="_blank">Open Content</a></p><?php endif; ?>
    <div class="progress" style="margin-top:8px"><span style="width:<?= (int)$p ?>%"></span></div>
    <form method="post" style="margin-top:8px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="module_id" value="<?= (int)$m['id'] ?>">
      <label>Progress (%)</label>
      <input type="number" name="progress" min="0" max="100" value="<?= (int)$p ?>">
      <button class="btn" style="margin-top:8px">Update</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


