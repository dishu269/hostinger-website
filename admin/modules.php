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
      $category = sanitize_string($_POST['category'] ?? '');
      $description = sanitize_string($_POST['description'] ?? '');
      $contentUrl = sanitize_string($_POST['content_url'] ?? '');
      $type = sanitize_string($_POST['type'] ?? 'video');
      $published = isset($_POST['published']) ? 1 : 0;
      $orderIndex = (int)($_POST['order_index'] ?? 0);
      $stmt = $pdo->prepare('INSERT INTO learning_modules (title, category, description, content_url, type, order_index, published) VALUES (?,?,?,?,?,?,?)');
      $stmt->execute([$title, $category, $description, $contentUrl, $type, $orderIndex, $published]);
      set_flash('success', 'Module created.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM learning_modules WHERE id = ?')->execute([$id]);
      set_flash('success', 'Module deleted.');
    }
  }
}

$modules = $pdo->query('SELECT * FROM learning_modules ORDER BY order_index ASC, id DESC')->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Learning Modules</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Create Module</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>Category</label>
      <select name="category">
        <option>Direct Selling Basics</option>
        <option>Company Info</option>
        <option>Product Knowledge</option>
        <option>Business Plan</option>
        <option>Sales & Networking</option>
      </select>
      <label>Description</label>
      <textarea name="description" rows="3"></textarea>
      <label>Content URL (video/pdf/article)</label>
      <input type="url" name="content_url">
      <label>Type</label>
      <select name="type">
        <option value="video">Video</option>
        <option value="pdf">PDF</option>
        <option value="article">Article</option>
      </select>
      <label>Order</label>
      <input type="number" name="order_index" value="0">
      <label><input type="checkbox" name="published" checked> Published</label>
      <div style="margin-top:12px"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
  <div class="card">
    <h3>All Modules</h3>
    <table>
      <thead><tr><th>Order</th><th>Title</th><th>Category</th><th>Type</th><th>Published</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($modules)): ?>
          <tr><td colspan="6" style="text-align: center;">No modules created yet.</td></tr>
        <?php endif; ?>
        <?php foreach($modules as $m): ?>
        <tr>
          <td><?= (int)$m['order_index'] ?></td>
          <td><?= htmlspecialchars($m['title']) ?></td>
          <td><?= htmlspecialchars($m['category']) ?></td>
          <td><span class="badge"><?= htmlspecialchars(strtoupper($m['type'])) ?></span></td>
          <td><?= (int)$m['published'] ? 'Yes' : 'No' ?></td>
          <td style="display:flex; gap: 6px;">
            <a href="/admin/edit_module.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline">Edit</a>
            <form method="post" onsubmit="return confirm('Are you sure you want to delete this module?')">
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


