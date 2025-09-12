<?php
require_once __DIR__ . '/includes/header-enhanced.php';

$pdo = get_db();
$countResult = $pdo->query('SELECT COUNT(*) FROM users');
$totalUsers = $countResult ? (int)$countResult->fetchColumn() : 0;
$defaultRole = $totalUsers === 0 ? 'admin' : 'member';

// Redirect if already logged in
if ($user) {
  header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard.php'));
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
  } else {
    $name = sanitize_text($_POST['name'] ?? '', 120);
    $email = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $defaultRole;
    if ($name === '' || mb_strlen($name) < 2) {
      $errors[] = 'Please enter your full name (min 2 characters).';
    }
    if ($email === '') {
      $errors[] = 'Please enter a valid email address.';
    }
    if (mb_strlen($password) < 8) {
      $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors && register_user($name, $email, $password, $role)) {
      set_flash('success', 'Registered successfully. Please sign in.');
      header('Location: /login.php');
      exit;
    } elseif ($errors) {
      foreach ($errors as $err) set_flash('error', $err);
    }
  }
}

foreach (get_flashes() as $f) {
  $alertClass = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
  echo '<div class="alert ' . $alertClass . ' mt-3">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<div class="card mt-4">
  <h2>Create Account <?= $defaultRole === 'admin' ? '(Admin)' : '' ?></h2>
  <form method="post" data-validate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <div class="form-group">
      <label for="name">Full Name</label>
      <input id="name" type="text" name="name" required data-voice autocomplete="name">
      <div class="form-error"></div>
    </div>
    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" required autocomplete="email">
      <div class="form-error"></div>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" minlength="8" required autocomplete="new-password">
      <div class="form-error"></div>
      <small class="text-muted">Minimum 8 characters</small>
    </div>
    <div class="mt-3"><button class="btn" type="submit">Register</button></div>
  </form>
  <p class="mt-2"><a href="/login.php">Already have an account?</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer-enhanced.php'; ?>


