<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

$user_id = (int)($_GET['id'] ?? 0);
if ($user_id === 0) {
    set_flash('error', 'Invalid user ID.');
    header('Location: /admin/users.php');
    exit;
}

// Fetch the user to edit
$stmt = $pdo->prepare('SELECT id, name, email, city, phone FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$edit_user = $stmt->fetch();

if (!$edit_user) {
    set_flash('error', 'User not found.');
    header('Location: /admin/users.php');
    exit;
}

// Display flashes for update messages
foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit User: <?= htmlspecialchars($edit_user['name']) ?></h2>

<div class="card" style="margin-top:12px">
  <form method="post" id="edit-user-form" action="/admin/ajax_admin_actions.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="edit_user">
    <input type="hidden" name="id" value="<?= (int)$edit_user['id'] ?>">

    <div>
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit_user['name']) ?>" required>
    </div>

    <div style="margin-top:12px">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
    </div>

    <div style="margin-top:12px">
      <label for="city">City</label>
      <input type="text" id="city" name="city" value="<?= htmlspecialchars($edit_user['city'] ?? '') ?>">
    </div>

    <div style="margin-top:12px">
      <label for="phone">Phone</label>
      <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($edit_user['phone'] ?? '') ?>">
    </div>

    <div style="margin-top:16px">
      <button class="btn">Save Changes</button>
      <a href="/admin/users.php" style="margin-left: 8px;">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
