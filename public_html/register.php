<?php
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
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
    if (mb_strlen($password) < 6) {
      $errors[] = 'Password must be at least 6 characters.';
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
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card mt-3" style="border-left:4px solid ' . $color . '">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<div class="card mt-4">
  <h2>Create Account <?= $defaultRole === 'admin' ? '(Admin)' : '' ?></h2>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <label>Full Name</label>
    <input type="text" name="name" required data-voice>
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Password</label>
    <input type="password" name="password" minlength="6" required>
    <div class="mt-3"><button class="btn" type="submit">Register</button></div>
  </form>
  <p class="mt-2"><a href="/login.php">Already have an account?</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


