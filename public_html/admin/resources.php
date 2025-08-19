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
      $description = sanitize_string($_POST['description'] ?? '');
      $fileUrl = sanitize_string($_POST['file_url'] ?? '');
      $type = sanitize_string($_POST['type'] ?? 'pdf');
      $published = isset($_POST['published']) ? 1 : 0;
      $stmt = $pdo->prepare('INSERT INTO resources (title, description, file_url, type, published) VALUES (?,?,?,?,?)');
      $stmt->execute([$title, $description, $fileUrl, $type, $published]);
      set_flash('success', 'Resource added.');
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $title = sanitize_string($_POST['title'] ?? '');
      $description = sanitize_string($_POST['description'] ?? '');
      $fileUrl = sanitize_string($_POST['file_url'] ?? '');
      $type = sanitize_string($_POST['type'] ?? 'pdf');
      $published = isset($_POST['published']) ? 1 : 0;
      $stmt = $pdo->prepare('UPDATE resources SET title=?, description=?, file_url=?, type=?, published=? WHERE id=?');
      $stmt->execute([$title, $description, $fileUrl, $type, $published, $id]);
      set_flash('success', 'Resource updated.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM resources WHERE id = ?')->execute([$id]);
      set_flash('success', 'Resource deleted.');
    }
  }
}

$resources = $pdo->query('SELECT * FROM resources ORDER BY id DESC')->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Resources</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Add Resource</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>Description</label>
      <textarea name="description" rows="3"></textarea>
      <label>URL</label>
      <input type="url" name="file_url" placeholder="https://...">
      <label>Type</label>
      <select name="type">
        <option value="pdf">PDF</option>
        <option value="image">Image</option>
        <option value="video">Video</option>
        <option value="script">Script</option>
        <option value="social">Social</option>
      </select>
      <label><input type="checkbox" name="published" checked> Published</label>
      <div style="margin-top:12px"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
  <div class="card">
    <h3>All Resources</h3>
    <p style="color:#6b7280">Tip: Manage WhatsApp templates under <a href="/admin/whatsapp_templates.php">WhatsApp Templates</a>.</p>
    <table>
      <thead><tr><th>Title</th><th>Type</th><th>URL</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($resources as $r): ?>
        <tr>
          <td>
            <form method="post" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="text" name="title" value="<?= htmlspecialchars($r['title']) ?>">
          </td>
          <td>
            <select name="type">
              <option value="pdf" <?= $r['type']==='pdf'?'selected':'' ?>>PDF</option>
              <option value="image" <?= $r['type']==='image'?'selected':'' ?>>Image</option>
              <option value="video" <?= $r['type']==='video'?'selected':'' ?>>Video</option>
              <option value="script" <?= $r['type']==='script'?'selected':'' ?>>Script</option>
              <option value="social" <?= $r['type']==='social'?'selected':'' ?>>Social</option>
            </select>
          </td>
          <td><input type="url" name="file_url" value="<?= htmlspecialchars($r['file_url']) ?>"></td>
          <td>
              <button class="btn btn-outline">Save</button>
            </form>
            <form method="post" onsubmit="return confirm('Delete resource?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
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


