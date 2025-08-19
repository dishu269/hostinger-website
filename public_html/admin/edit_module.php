<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid CSRF token.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = sanitize_string($_POST['title'] ?? '');
        $category = sanitize_string($_POST['category'] ?? '');
        $description = sanitize_string($_POST['description'] ?? '');
        $contentUrl = sanitize_string($_POST['content_url'] ?? '');
        $type = sanitize_string($_POST['type'] ?? 'video');
        $published = isset($_POST['published']) ? 1 : 0;
        $orderIndex = (int)($_POST['order_index'] ?? 0);

        $stmt = $pdo->prepare('UPDATE learning_modules SET title=?, category=?, description=?, content_url=?, type=?, order_index=?, published=? WHERE id = ?');
        $stmt->execute([$title, $category, $description, $contentUrl, $type, $orderIndex, $published, $id]);

        set_flash('success', 'Module updated successfully.');
        header('Location: /admin/modules.php');
        exit;
    }
}

$module_id = (int)($_GET['id'] ?? 0);
if ($module_id === 0) {
    set_flash('error', 'Invalid module ID.');
    header('Location: /admin/modules.php');
    exit;
}

// Fetch the module to edit
$stmt = $pdo->prepare('SELECT * FROM learning_modules WHERE id = ?');
$stmt->execute([$module_id]);
$module = $stmt->fetch();

if (!$module) {
    set_flash('error', 'Module not found.');
    header('Location: /admin/modules.php');
    exit;
}

// Display flashes for update messages
foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit Module: <?= htmlspecialchars($module['title']) ?></h2>

<div class="card" style="margin-top:12px">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= (int)$module['id'] ?>">

    <div>
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= htmlspecialchars($module['title']) ?>" required>
    </div>

    <div style="margin-top:12px">
      <label for="category">Category</label>
      <select id="category" name="category">
        <option <?= $module['category'] === 'Direct Selling Basics' ? 'selected' : '' ?>>Direct Selling Basics</option>
        <option <?= $module['category'] === 'Company Info' ? 'selected' : '' ?>>Company Info</option>
        <option <?= $module['category'] === 'Product Knowledge' ? 'selected' : '' ?>>Product Knowledge</option>
        <option <?= $module['category'] === 'Business Plan' ? 'selected' : '' ?>>Business Plan</option>
        <option <?= $module['category'] === 'Sales & Networking' ? 'selected' : '' ?>>Sales & Networking</option>
      </select>
    </div>

    <div style="margin-top:12px">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="3"><?= htmlspecialchars($module['description']) ?></textarea>
    </div>

    <div style="margin-top:12px">
      <label for="content_url">Content URL (video/pdf/article)</label>
      <input type="url" id="content_url" name="content_url" value="<?= htmlspecialchars($module['content_url']) ?>">
    </div>

    <div style="margin-top:12px">
      <label for="type">Type</label>
      <select id="type" name="type">
        <option value="video" <?= $module['type'] === 'video' ? 'selected' : '' ?>>Video</option>
        <option value="pdf" <?= $module['type'] === 'pdf' ? 'selected' : '' ?>>PDF</option>
        <option value="article" <?= $module['type'] === 'article' ? 'selected' : '' ?>>Article</option>
      </select>
    </div>

    <div style="margin-top:12px">
      <label for="order_index">Order</label>
      <input type="number" id="order_index" name="order_index" value="<?= (int)$module['order_index'] ?>">
    </div>

    <div style="margin-top:12px">
      <label><input type="checkbox" name="published" <?= (int)$module['published'] ? 'checked' : '' ?>> Published</label>
    </div>

    <div style="margin-top:16px">
      <button class="btn">Save Changes</button>
      <a href="/admin/modules.php" style="margin-left: 8px;">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
