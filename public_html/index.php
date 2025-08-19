<?php
require_once __DIR__ . '/includes/header.php';

if ($user) {
  header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard.php'));
  exit;
}

$pdo = get_db();
$today = (new DateTime('today'))->format('Y-m-d');
$motivation = $pdo->query("SELECT title, body FROM messages WHERE active = 1 AND message_type='motivation' ORDER BY id DESC LIMIT 1")->fetch();
$todayTaskStmt = $pdo->prepare('SELECT title, description FROM tasks WHERE task_date = ? OR is_daily = 1 ORDER BY task_date DESC LIMIT 1');
$todayTaskStmt->execute([$today]);
$todayTask = $todayTaskStmt->fetch();
$stats = [
  'modules' => (int)$pdo->query('SELECT COUNT(*) FROM learning_modules WHERE published = 1')->fetchColumn(),
  'resources' => (int)$pdo->query('SELECT COUNT(*) FROM resources WHERE published = 1')->fetchColumn(),
  'members' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
];
?>
<section class="hero">
  <div>
    <h1>Build, Learn, and Grow with <?= SITE_BRAND ?></h1>
    <p>Digital HQ + Personal CRM + AI Mentor + Training Hub — designed for daily momentum.</p>
    <div class="mt-3">
      <a class="btn" href="/register.php">Get Started</a>
      <a class="btn-outline ml-2" href="/login.php">Sign In</a>
    </div>
    <?php if ($todayTask): ?>
      <div class="card mt-3">
        <strong>Today's Task:</strong>
        <div><?= htmlspecialchars($todayTask['title']) ?></div>
        <p class="text-muted my-1"><?= nl2br(htmlspecialchars($todayTask['description'])) ?></p>
      </div>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="motivation">
      <h3><?= htmlspecialchars($motivation['title'] ?? 'Motivation of the Day') ?></h3>
      <p><?= htmlspecialchars($motivation['body'] ?? 'Small steps daily lead to massive results. Take action now.') ?></p>
    </div>
    <div class="mt-3">
      <img src="/assets/img/cover.jpg" alt="Team motivation video placeholder" class="w-full rounded-lg">
    </div>
  </div>
  </section>

<section class="my-4">
  <div class="grid cols-3">
    <div class="card feature-card">
      <span class="icon"><i data-feather="book-open"></i></span>
      <h3>Learning Hub</h3>
      <p>Direct selling basics, company info, products, plan. Track progress.</p>
      <a class="btn btn-outline" href="/login.php">Explore Modules</a>
    </div>
    <div class="card feature-card">
      <span class="icon"><i data-feather="users"></i></span>
      <h3>Personal CRM</h3>
      <p>Leads, follow-ups, reminders, and smart guidance. Offline + voice input.</p>
      <a class="btn btn-outline" href="/login.php">Manage Leads</a>
    </div>
    <div class="card feature-card">
      <span class="icon"><i data-feather="target"></i></span>
      <h3>Training + AI Mentor</h3>
      <p>Practice scripts, handle objections, and build consistency with streaks.</p>
      <a class="btn btn-outline" href="/login.php">Start Training</a>
    </div>
  </div>
</section>

<section class="grid cols-3 my-4">
  <div class="card kpi"><span>Published Modules</span><strong><?= $stats['modules'] ?></strong></div>
  <div class="card kpi"><span>Resources</span><strong><?= $stats['resources'] ?></strong></div>
  <div class="card kpi"><span>Members</span><strong><?= $stats['members'] ?></strong></div>
</section>

<section class="my-4">
  <div class="grid cols-2">
    <div class="card">
      <h3>Why teams love it</h3>
      <ul>
        <li>Daily action plan with reminders</li>
        <li>Lead CRM with offline save + auto-sync</li>
        <li>Gamified learning and progress tracking</li>
        <li>Ready-to-use WhatsApp templates</li>
      </ul>
    </div>
    <div class="card">
      <h3>How it works</h3>
      <ol>
        <li>Create your account</li>
        <li>Check today’s task and add 3 leads</li>
        <li>Complete one learning module</li>
        <li>Track progress and earn badges</li>
      </ol>
    </div>
  </div>
</section>

<section class="my-4">
  <div class="card text-center">
    <h3>Ready to grow with <?= SITE_BRAND ?>?</h3>
    <p>Join now and get your personal Digital HQ.</p>
    <a class="btn" href="/register.php">Create Free Account</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


