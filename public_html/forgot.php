<?php
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $email = sanitize_email($_POST['email'] ?? '');
    if ($email !== '') {
      send_password_reset_email($email);
    }
    set_flash('success', 'If the email exists, a reset link has been sent.');
    header('Location: /login.php');
    exit;
  }
}

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<div class="card" style="margin-top:16px">
  <h2>Forgot Password</h2>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <label>Email</label>
    <input type="email" name="email" required>
    <div style="margin-top:12px"><button class="btn" type="submit">Send Reset Link</button></div>
  </form>
  <p style="margin-top:8px"><a href="/login.php">Back to login</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


