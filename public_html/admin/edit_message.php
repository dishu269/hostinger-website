<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid CSRF token.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = sanitize_string($_POST['title'] ?? '');
        $body = sanitize_string($_POST['body'] ?? '');
        $type = sanitize_string($_POST['message_type'] ?? 'motivation');
        $active = isset($_POST['active']) ? 1 : 0;

        $stmt = $pdo->prepare('UPDATE messages SET title=?, body=?, message_type=?, active=? WHERE id=?');
        $stmt->execute([$title, $body, $type, $active, $id]);
        set_flash('success', 'Message updated.');
        header('Location: /admin/messages.php');
        exit;
    }
}

$message_id = (int)($_GET['id'] ?? 0);
if ($message_id === 0) {
    set_flash('error', 'Invalid message ID.');
    header('Location: /admin/messages.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM messages WHERE id = ?');
$stmt->execute([$message_id]);
$message = $stmt->fetch();

if (!$message) {
    set_flash('error', 'Message not found.');
    header('Location: /admin/messages.php');
    exit;
}

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit Message</h2>
<div class="card" style="margin-top:12px">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= (int)$message['id'] ?>">

    <label>Title</label>
    <input type="text" name="title" value="<?= htmlspecialchars($message['title']) ?>" required>

    <label>Message</label>
    <textarea name="body" rows="3" required><?= htmlspecialchars($message['body']) ?></textarea>

    <label>Type</label>
    <select name="message_type">
      <option value="motivation" <?= $message['message_type'] === 'motivation' ? 'selected' : '' ?>>Motivation</option>
      <option value="announcement" <?= $message['message_type'] === 'announcement' ? 'selected' : '' ?>>Announcement</option>
    </select>

    <label><input type="checkbox" name="active" <?= (int)$message['active'] ? 'checked' : '' ?>> Active</label>

    <div style="margin-top:12px">
        <button class="btn" type="submit">Save Changes</button>
        <a href="/admin/messages.php" style="margin-left: 8px;">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
