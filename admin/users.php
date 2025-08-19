<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
  } else {
    $action = $_POST['action'] ?? '';
    if ($action === 'role' && isset($_POST['id'], $_POST['role'])) {
      $id = (int)$_POST['id'];
      $role = $_POST['role'] === 'admin' ? 'admin' : 'member';
      $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
      set_flash('success', 'Role updated.');
    } elseif ($action === 'delete') {
      $id = (int)$_POST['id'];
      if ($id === (int)$user['id']) {
        set_flash('error', 'Cannot delete your own account.');
      } else {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        set_flash('success', 'User deleted.');
      }
    }
  }
}

$users = $pdo->query('SELECT id, name, email, role, created_at, last_login FROM users ORDER BY id DESC')->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Team Members</h2>
<div class="card" style="margin-top:12px">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Last Login</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <form method="post" style="display:inline-flex; gap:6px; align-items:center">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="role">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <select name="role" onchange="this.form.submit()">
              <option value="member" <?= $u['role']==='member'?'selected':'' ?>>Member</option>
              <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
            </select>
          </form>
        </td>
        <td><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
        <td><?= htmlspecialchars($u['last_login'] ?? '-') ?></td>
        <td style="display:flex; gap: 6px;">
          <a href="/admin/edit_user.php?id=<?= (int)$u['id'] ?>" class="btn btn-outline">Edit</a>
          <form method="post" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-outline">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


