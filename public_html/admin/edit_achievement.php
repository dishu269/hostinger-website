<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

$achievement_id = (int)($_GET['id'] ?? 0);
if ($achievement_id === 0) {
    set_flash('error', 'Invalid achievement ID.');
    header('Location: /admin/achievements.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM achievements WHERE id = ?');
$stmt->execute([$achievement_id]);
$achievement = $stmt->fetch();

if (!$achievement) {
    set_flash('error', 'Achievement not found.');
    header('Location: /admin/achievements.php');
    exit;
}

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit Achievement</h2>
<div class="card" style="margin-top:12px">
  <form method="post" id="edit-achievement-form" action="/admin/ajax_admin_actions.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="edit_achievement">
    <input type="hidden" name="id" value="<?= (int)$achievement['id'] ?>">

    <label>Code (cannot be changed)</label>
    <input type="text" name="code" value="<?= htmlspecialchars($achievement['code']) ?>" disabled>

    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($achievement['name']) ?>" required>

    <label>Description</label>
    <textarea name="description" rows="3"><?= htmlspecialchars($achievement['description']) ?></textarea>

    <label>Icon (emoji)</label>
    <input type="text" name="icon" value="<?= htmlspecialchars($achievement['icon']) ?>">

    <label>Threshold Type</label>
    <select name="threshold_type">
      <option value="leads" <?= $achievement['threshold_type'] === 'leads' ? 'selected' : '' ?>>Leads</option>
      <option value="tasks" <?= $achievement['threshold_type'] === 'tasks' ? 'selected' : '' ?>>Tasks</option>
      <option value="modules" <?= $achievement['threshold_type'] === 'modules' ? 'selected' : '' ?>>Modules</option>
      <option value="streak" <?= $achievement['threshold_type'] === 'streak' ? 'selected' : '' ?>>Streak</option>
    </select>

    <label>Threshold Value</label>
    <input type="number" name="threshold_value" value="<?= (int)$achievement['threshold_value'] ?>">

    <div style="margin-top:12px">
        <button class="btn" type="submit">Save Changes</button>
        <a href="/admin/achievements.php" style="margin-left: 8px;">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
