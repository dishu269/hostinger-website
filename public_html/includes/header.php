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
  <meta name="description" content="Digital HQ + Personal CRM + AI Mentor + Training Hub for Asclepius Wellness Team">
  <meta name="theme-color" content="#002147">
  <link rel="manifest" href="/manifest.json">
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
  <a href="#main-content" class="skip-link">Skip to main content</a>
  <div id="loader" class="loader">
    <div class="spinner"></div>
  </div>
  <header class="site-header">
    <div class="container header-inner">
      <a href="/" class="brand"><?= SITE_BRAND ?></a>
      <button id="mobile-menu-button" class="mobile-menu-button" aria-label="Menu" aria-expanded="false">
        <span class="line"></span>
        <span class="line"></span>
        <span class="line"></span>
      </button>
      <div id="mobile-menu" class="nav-wrapper">
        <nav class="nav">
          <?php if ($user): ?>
            <?php if ($user['role'] === 'admin'): ?>
              <a href="/admin/index.php">Admin Dashboard</a>
            <?php else: ?>
              <a href="/user/dashboard.php">Dashboard</a>
              <a href="/user/learning.php">Learning</a>
              <a href="/user/tasks.php">Tasks</a>
              <a href="/user/crm.php">CRM</a>
              <a href="/user/resources.php">Resources</a>
              <a href="/user/training.php">Training</a>
              <a href="/user/achievements.php">Badges</a>
              <a href="/user/leaderboard.php">Leaderboard</a>
            <?php endif; ?>

            <a href="/user/profile.php" class="nav-profile-link">
              <img src="<?= htmlspecialchars($user['avatar_url'] ?? 'https://placehold.co/32x32/EFEFEF/AAAAAA&text=') ?>" alt="Your Avatar">
              <span>My Profile</span>
            </a>
            <a class="btn btn-outline" href="/logout.php">Logout</a>
            <button id="theme-toggle" class="btn-outline" style="padding: 6px 10px;">🌙</button>
          <?php else: ?>
            <a href="/login.php">Login</a>
            <a class="btn" href="/register.php">Join</a>
            <button id="theme-toggle" class="btn-outline" style="padding: 6px 10px;">🌙</button>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
  <main class="container" id="main-content">
