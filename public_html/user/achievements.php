<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

$rows = $pdo->prepare('SELECT a.icon, a.name, a.description, ua.awarded_at FROM achievements a LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ? ORDER BY a.id ASC');
$rows->execute([$user['id']]);
$ach = $rows->fetchAll();
?>

<h2>My Badges</h2>
<div class="grid cols-3" style="margin-top:12px">
  <?php foreach($ach as $a): ?>
  <div class="card">
    <div style="font-size:28px"><?= htmlspecialchars($a['icon']) ?></div>
    <strong><?= htmlspecialchars($a['name']) ?></strong>
    <p><?= nl2br(htmlspecialchars($a['description'])) ?></p>
    <?php if ($a['awarded_at']): ?>
      <span class="badge">Earned</span>
    <?php else: ?>
      <span class="badge" style="background:#9ca3af">Locked</span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

