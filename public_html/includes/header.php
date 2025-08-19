<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$user = current_user();
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
  <title><?= SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script>
    // Theme switcher to prevent FOUC
    (function() {
      const theme = localStorage.getItem('theme');
      if (theme === 'dark') {
        document.body.classList.add('dark-mode');
      }
    })();
  </script>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="/" class="brand"><?= SITE_BRAND ?></a>
      <nav class="nav">
        <?php if ($user): ?>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="/admin/index.php">Admin</a>
          <?php endif; ?>
          <a href="/user/profile.php" style="display: inline-flex; align-items: center; gap: 8px;">
            <img src="<?= htmlspecialchars($user['avatar_url'] ?? 'https://placehold.co/32x32/EFEFEF/AAAAAA&text=') ?>" alt="Your Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
            <span>My Profile</span>
          </a>
          <a href="/user/dashboard.php">Dashboard</a>
          <a href="/user/learning.php">Learning</a>
          <a href="/user/tasks.php">Tasks</a>
          <a href="/user/crm.php">CRM</a>
          <a href="/user/resources.php">Resources</a>
          <a href="/user/training.php">Training</a>
          <a href="/user/achievements.php">Badges</a>
          <a href="/user/leaderboard.php">Leaderboard</a>
          <button id="theme-toggle" class="btn-outline" style="padding: 6px 10px;">🌙</button>
          <a class="btn btn-outline" href="/logout.php">Logout</a>
        <?php else: ?>
          <a href="/login.php">Login</a>
          <a class="btn" href="/register.php">Join</a>
          <button id="theme-toggle" class="btn-outline" style="padding: 6px 10px;">🌙</button>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <main class="container">
