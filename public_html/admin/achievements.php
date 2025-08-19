<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
  } else {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
      $code = sanitize_string($_POST['code'] ?? '');
      $name = sanitize_string($_POST['name'] ?? '');
      $description = sanitize_string($_POST['description'] ?? '');
      $icon = sanitize_string($_POST['icon'] ?? '🏆');
      $thresholdType = sanitize_string($_POST['threshold_type'] ?? 'leads');
      $thresholdValue = (int)($_POST['threshold_value'] ?? 0);
      $stmt = $pdo->prepare('INSERT INTO achievements (code, name, description, icon, threshold_type, threshold_value) VALUES (?,?,?,?,?,?)');
      $stmt->execute([$code, $name, $description, $icon, $thresholdType, $thresholdValue]);
      set_flash('success', 'Achievement added.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM achievements WHERE id = ?')->execute([$id]);
      set_flash('success', 'Achievement deleted.');
    }
  }
}

$achievements = $pdo->query('SELECT * FROM achievements ORDER BY id DESC')->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Achievements</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Create Achievement</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Code</label>
      <input type="text" name="code" placeholder="e.g., FIRST_LEAD" required>
      <label>Name</label>
      <input type="text" name="name" required>
      <label>Description</label>
      <textarea name="description" rows="3"></textarea>
      <label>Icon (emoji)</label>
      <input type="text" name="icon" value="🏆">
      <label>Threshold Type</label>
      <select name="threshold_type">
        <option value="leads">Leads</option>
        <option value="tasks">Tasks</option>
        <option value="modules">Modules</option>
        <option value="streak">Streak</option>
      </select>
      <label>Threshold Value</label>
      <input type="number" name="threshold_value" value="1">
      <div style="margin-top:12px"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
  <div class="card">
    <h3>All Achievements</h3>
    <table>
      <thead><tr><th>Icon</th><th>Name</th><th>Type</th><th>Value</th><th></th></tr></thead>
      <tbody>
        <?php foreach($achievements as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['icon']) ?></td>
          <td><?= htmlspecialchars($a['name']) ?></td>
          <td><?= htmlspecialchars($a['threshold_type']) ?></td>
          <td><?= (int)$a['threshold_value'] ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Delete achievement?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button class="btn btn-outline">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


