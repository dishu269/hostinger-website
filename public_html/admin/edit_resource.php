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
        $description = sanitize_string($_POST['description'] ?? '');
        $fileUrl = sanitize_string($_POST['file_url'] ?? '');
        $type = sanitize_string($_POST['type'] ?? 'pdf');
        $published = isset($_POST['published']) ? 1 : 0;

        if (empty($title)) {
            set_flash('error', 'Title is required.');
        } else {
            $stmt = $pdo->prepare('UPDATE resources SET title=?, description=?, file_url=?, type=?, published=? WHERE id=?');
            $stmt->execute([$title, $description, $fileUrl, $type, $published, $id]);
            set_flash('success', 'Resource updated successfully.');
            header('Location: /admin/resources.php');
            exit;
        }
    }
}

$resource_id = (int)($_GET['id'] ?? 0);
if ($resource_id === 0) {
    set_flash('error', 'Invalid resource ID.');
    header('Location: /admin/resources.php');
    exit;
}

// Fetch the resource to edit
$stmt = $pdo->prepare('SELECT * FROM resources WHERE id = ?');
$stmt->execute([$resource_id]);
$resource = $stmt->fetch();

if (!$resource) {
    set_flash('error', 'Resource not found.');
    header('Location: /admin/resources.php');
    exit;
}

// Display flashes for update messages
foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit Resource: <?= htmlspecialchars($resource['title']) ?></h2>

<div class="card" style="margin-top:12px">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= (int)$resource['id'] ?>">

    <div>
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= htmlspecialchars($resource['title']) ?>" required>
    </div>

    <div style="margin-top:12px">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="3"><?= htmlspecialchars($resource['description']) ?></textarea>
    </div>

    <div style="margin-top:12px">
      <label for="file_url">URL</label>
      <input type="url" id="file_url" name="file_url" value="<?= htmlspecialchars($resource['file_url']) ?>" placeholder="https://...">
    </div>

    <div style="margin-top:12px">
      <label for="type">Type</label>
      <select id="type" name="type">
        <option value="pdf" <?= $resource['type'] === 'pdf' ? 'selected' : '' ?>>PDF</option>
        <option value="image" <?= $resource['type'] === 'image' ? 'selected' : '' ?>>Image</option>
        <option value="video" <?= $resource['type'] === 'video' ? 'selected' : '' ?>>Video</option>
        <option value="script" <?= $resource['type'] === 'script' ? 'selected' : '' ?>>Script</option>
        <option value="social" <?= $resource['type'] === 'social' ? 'selected' : '' ?>>Social</option>
      </select>
    </div>

    <div style="margin-top:12px">
      <label><input type="checkbox" name="published" <?= (int)$resource['published'] ? 'checked' : '' ?>> Published</label>
    </div>

    <div style="margin-top:16px">
      <button class="btn">Save Changes</button>
      <a href="/admin/resources.php" style="margin-left: 8px;">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
