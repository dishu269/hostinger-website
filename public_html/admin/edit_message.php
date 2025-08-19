<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

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
  <form method="post" id="edit-message-form" action="/admin/ajax_admin_actions.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="edit_message">
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
