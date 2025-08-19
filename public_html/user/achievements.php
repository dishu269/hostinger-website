<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

$stmt = $pdo->prepare('SELECT a.id, a.icon, a.name, a.description, ua.awarded_at FROM achievements a LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ? ORDER BY a.id ASC');
$stmt->execute([$user['id']]);
$all_achievements_raw = $stmt->fetchAll();

$achievements = [];
foreach ($all_achievements_raw as $a) {
    $achievements[] = [
        'icon' => $a['icon'],
        'name' => $a['name'],
        'description' => $a['description'],
        'unlocked' => ($a['awarded_at'] !== null),
    ];
}
?>

<h2>My Badges</h2>
<div class="grid cols-3" style="margin-top:12px">
  <?php foreach($achievements as $a): ?>
  <div class="card <?= !$a['unlocked'] ? 'achievement-locked' : '' ?>">
    <div style="font-size:28px"><?= $a['unlocked'] ? htmlspecialchars($a['icon']) : '🔒' ?></div>
    <strong><?= htmlspecialchars($a['name']) ?></strong>
    <p><?= nl2br(htmlspecialchars($a['description'])) ?></p>
    <?php if ($a['unlocked']): ?>
      <span class="badge">Earned</span>
    <?php else: ?>
      <span class="badge" style="background-color: var(--text-muted)">Locked</span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

