<?php
require_once __DIR__ . '/includes/header-enhanced.php';

// If already logged in, send to dashboard/admin
if ($user) {
  header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard-enhanced.php'));
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $errors[] = 'Invalid CSRF token.';
  } elseif (!empty($_POST['hp'])) { // honeypot
    $errors[] = 'Invalid submission.';
  } else {
    $email = sanitize_email($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if ($email === '') { $errors[] = 'Please enter a valid email.'; }
    if ($password === '') { $errors[] = 'Please enter your password.'; }

    if (!$errors && login_user($email, $password)) {
      // Use fresh session user after successful login
      $u = current_user();
      $role = $u['role'] ?? 'member';

      // Safe redirect: only allow relative internal paths
      $next = $_GET['next'] ?? '';
      if (!is_string($next) || $next === '' || !str_starts_with($next, '/')) {
        $next = ($role === 'admin') ? '/admin/index.php' : '/user/dashboard-enhanced.php';
      }

      header('Location: ' . $next);
      exit;
    }
  }
}

// Flash errors
foreach ($errors as $msg) set_flash('error', $msg);
foreach (get_flashes() as $f) {
  $alertClass = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
  echo '<div class="alert ' . $alertClass . ' mt-3">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<div class="card mt-4">
  <h2>Sign In</h2>
  <form method="post" novalidate data-validate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="text" name="hp" value="" class="d-none" tabindex="-1" autocomplete="off">

    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" autocomplete="email" required autofocus>
      <div class="form-error"></div>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" autocomplete="current-password" required>
      <div class="form-error"></div>
    </div>

    <div class="mt-3">
      <button class="btn" type="submit">Login</button>
    </div>
  </form>

  <p class="mt-2"><a href="/register.php">Create an account</a></p>
  <p class="mt-1">
    <a href="/forgot.php">Forgot password?</a> ·
    <a href="#" data-resend>Resend verification</a>
  </p>

  <!-- Resend verification (hidden form populated from email field) -->
  <form id="resendForm" method="post" action="/resend.php" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="email" value="">
  </form>
</div>

<script>
  (function () {
    const emailInput = document.getElementById('email');
    const form = document.getElementById('resendForm');
    const link = document.querySelector('[data-resend]');
    if (link && form && emailInput) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        form.querySelector('input[name="email"]').value = (emailInput.value || '').trim();
        form.submit();
      });
    }
  })();
</script>

<?php require_once __DIR__ . '/includes/footer-enhanced.php'; ?>