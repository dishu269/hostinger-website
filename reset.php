<?php
require_once __DIR__ . '/includes/header.php';

$token = $_GET['token'] ?? '';
$valid = false;
if ($token) {
  $pdo = get_db();
  $stmt = $pdo->prepare('SELECT email, expires_at FROM password_resets WHERE token = ?');
  $stmt->execute([$token]);
  $row = $stmt->fetch();
  if ($row && strtotime($row['expires_at']) > time()) {
    $valid = true;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $token = $_POST['token'] ?? '';
    $password = (string)($_POST['password'] ?? '');
    if (mb_strlen($password) < 6) {
      set_flash('error', 'Password must be at least 6 characters.');
    } else {
      $pdo = get_db();
      $stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()');
      $stmt->execute([$token]);
      $row = $stmt->fetch();
      if ($row) {
        $pwd = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([$pwd, $row['email']]);
        $pdo->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
        set_flash('success', 'Password reset. Please login.');
        header('Location: /login.php');
        exit;
      } else {
        set_flash('error', 'Reset link is invalid or expired.');
      }
    }
  }
}

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<div class="card" style="margin-top:16px">
  <h2>Reset Password</h2>
  <?php if ($valid): ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <label>New Password</label>
    <input type="password" name="password" minlength="6" required>
    <div style="margin-top:12px"><button class="btn" type="submit">Reset</button></div>
  </form>
  <?php else: ?>
    <p>Reset link is invalid or expired.</p>
  <?php endif; ?>
  <p style="margin-top:8px"><a href="/forgot.php">Request new link</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


