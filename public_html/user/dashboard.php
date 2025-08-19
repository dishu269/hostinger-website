<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php'; // Include the new functions file
require_login();

$pdo = get_db();
$userId = (int)$user['id'];

// Fetch all dashboard data using the new functions
$todayTask = get_today_task($pdo);
$motivation = get_motivation_message($pdo);
$due = get_due_leads($pdo, $userId);
$kpis = get_dashboard_kpis($pdo, $userId);
$streakRow = get_user_streak($pdo, $userId);
$notifications = get_notifications($pdo, $userId);
$f = get_weekly_funnel($pdo, $userId);

// Extract KPI values for use in the template
$completedTasks = $kpis['completed_tasks'];
$totalModules = $kpis['total_modules'];
$completedModules = $kpis['completed_modules'];
?>

<h2>Namaste <?= htmlspecialchars($user['name']) ?> 👋</h2>
<div class="grid cols-3" style="margin-top:12px">
  <div class="card">
    <h3>Aaj ka Action</h3>
    <?php if ($todayTask): ?>
      <strong><?= htmlspecialchars($todayTask['title']) ?></strong>
      <p style="color:#6b7280"><?= nl2br(htmlspecialchars($todayTask['description'])) ?></p>
    <?php else: ?>
      <p>Aaj koi specific task nahi hai. Learning Hub se start karein.</p>
    <?php endif; ?>
    <a class="btn" href="/user/tasks.php" style="margin-top:8px">Tasks khole</a>
  </div>
  <div class="card">
    <h3>Follow-ups Due Aaj</h3>
    <?php if ($due): ?>
      <ul>
        <?php foreach ($due as $d): 
          $msg = rawurlencode('Hi ' . $d['name'] . ', ' . SITE_BRAND . ' se. Quick follow-up 🙂');
          $wa = 'https://wa.me/' . rawurlencode($d['mobile']) . '?text=' . $msg;
        ?>
        <li>
          <strong><?= htmlspecialchars($d['name']) ?></strong>
          — <a href="tel:<?= htmlspecialchars($d['mobile']) ?>"><?= htmlspecialchars($d['mobile']) ?></a>
          <span style="color:#6b7280">(<?= htmlspecialchars($d['follow_up_date']) ?>)</span>
          <a class="btn btn-outline" style="margin-left:6px" target="_blank" href="<?= $wa ?>">WhatsApp</a>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Aaj koi due follow-up nahi hai.</p>
    <?php endif; ?>
    <a class="btn" href="/user/crm.php" style="margin-top:8px">CRM khole</a>
  </div>
  <div class="card motivation">
    <h3><?= htmlspecialchars($motivation['title'] ?? 'Keep Going!') ?></h3>
    <p><?= htmlspecialchars($motivation['body'] ?? 'Roz thoda, life me bada. Bas aaj ka step lein!') ?></p>
  </div>
</div>

<div class="grid cols-3" style="margin-top:12px">
  <div class="card kpi"><span>Tasks Complete</span><strong><?= $completedTasks ?></strong></div>
  <div class="card kpi"><span>Learning Progress</span><strong><?= $completedModules ?>/<?= $totalModules ?></strong></div>
  <div class="card kpi"><span>Streak</span><strong><?= (int)$streakRow['current_streak'] ?> din 🔥 (Best <?= (int)$streakRow['longest_streak'] ?>)</strong></div>
</div>

<?php if ($notifications): ?>
<div class="card" style="margin-top:12px">
  <h3>Updates</h3>
  <ul>
    <?php foreach($notifications as $n): ?>
    <li><strong><?= htmlspecialchars($n['title']) ?></strong> — <?= htmlspecialchars($n['body']) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="grid cols-3" style="margin-top:12px">
  <a class="card" href="/user/learning.php"><strong>Learning Hub</strong><p>Modules complete karke skill badhao.</p></a>
  <a class="card" href="/user/community.php"><strong>Community</strong><p>Questions poochho, help lo.</p></a>
  <a class="card" href="/user/resources.php"><strong>Resources</strong><p>PDFs, scripts aur social content ready.</p></a>
</div>

<div class="grid cols-3" style="margin-top:12px">
  <div class="card kpi"><span>7 din Attempts</span><strong><?= (int)$f['attempts'] ?></strong></div>
  <div class="card kpi"><span>7 din Success</span><strong><?= (int)$f['successes'] ?></strong></div>
  <a class="card" href="/user/tasks.php?view=kanban"><strong>Kanban Board</strong><p>To‑do → Doing → Done drag & drop.</p></a>
</div>

<div class="card" style="margin-top:12px">
  <h3>Quick Start (Naye member ke liye)</h3>
  <ol>
    <li>3 naye contacts CRM me add karo</li>
    <li>Aaj ka Task complete karo</li>
    <li>Learning Hub se 1 module 100% karo</li>
  </ol>
  <div style="margin-top:8px">
    <a class="btn" href="/user/crm.php">CRM me Lead Add karo</a>
    <a class="btn btn-outline" style="margin-left:6px" href="/user/learning.php">Learning start karo</a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


