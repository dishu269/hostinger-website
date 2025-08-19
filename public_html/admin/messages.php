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
      $title = sanitize_string($_POST['title'] ?? '');
      $body = sanitize_string($_POST['body'] ?? '');
      $type = sanitize_string($_POST['message_type'] ?? 'motivation');
      $active = isset($_POST['active']) ? 1 : 0;
      $stmt = $pdo->prepare('INSERT INTO messages (title, body, message_type, active, created_at) VALUES (?,?,?,?, NOW())');
      $stmt->execute([$title, $body, $type, $active]);
      set_flash('success', 'Message posted.');
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $title = sanitize_string($_POST['title'] ?? '');
      $body = sanitize_string($_POST['body'] ?? '');
      $type = sanitize_string($_POST['message_type'] ?? 'motivation');
      $active = isset($_POST['active']) ? 1 : 0;
      $stmt = $pdo->prepare('UPDATE messages SET title=?, body=?, message_type=?, active=? WHERE id=?');
      $stmt->execute([$title, $body, $type, $active, $id]);
      set_flash('success', 'Message updated.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
      set_flash('success', 'Message deleted.');
    }
  }
}

$messages = $pdo->query('SELECT * FROM messages ORDER BY id DESC')->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Broadcast Messages</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Create Message</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>Message</label>
      <textarea name="body" rows="3" required></textarea>
      <label>Type</label>
      <select name="message_type">
        <option value="motivation">Motivation</option>
        <option value="announcement">Announcement</option>
      </select>
      <label><input type="checkbox" name="active" checked> Active</label>
      <div style="margin-top:12px"><button class="btn" type="submit">Publish</button></div>
    </form>
  </div>
  <div class="card">
    <h3>All Messages</h3>
    <table>
      <thead><tr><th>Title</th><th>Type</th><th>Active</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($messages as $m): ?>
        <tr>
          <td>
            <form method="post" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <input type="text" name="title" value="<?= htmlspecialchars($m['title']) ?>">
          </td>
          <td>
            <select name="message_type">
              <option value="motivation" <?= $m['message_type']==='motivation'?'selected':'' ?>>Motivation</option>
              <option value="announcement" <?= $m['message_type']==='announcement'?'selected':'' ?>>Announcement</option>
            </select>
          </td>
          <td><input type="checkbox" name="active" <?= (int)$m['active'] ? 'checked' : '' ?>></td>
          <td>
              <button class="btn btn-outline">Save</button>
            </form>
            <form method="post" onsubmit="return confirm('Delete message?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
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


