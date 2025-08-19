<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

// Aaj ka task (today or daily)
$today = (new DateTime('today'))->format('Y-m-d');
$stmt = $pdo->prepare('SELECT * FROM tasks WHERE task_date = ? OR is_daily = 1 ORDER BY task_date DESC LIMIT 1');
$stmt->execute([$today]);
$todayTask = $stmt->fetch();

$motivation = $pdo->query("SELECT title, body FROM messages WHERE active = 1 AND message_type='motivation' ORDER BY id DESC LIMIT 1")->fetch();

// Follow-ups due
$dueLeads = $pdo->prepare('SELECT id, name, mobile, interest_level, follow_up_date FROM leads WHERE user_id = ? AND follow_up_date IS NOT NULL AND follow_up_date <= ? ORDER BY follow_up_date ASC LIMIT 5');
$dueLeads->execute([$user['id'], $today]);
$due = $dueLeads->fetchAll();

// Progress & KPIs
$completedTasks = (int)$pdo->query('SELECT COUNT(*) FROM user_tasks WHERE user_id = ' . (int)$user['id'])->fetchColumn();
$totalModules = (int)$pdo->query('SELECT COUNT(*) FROM learning_modules WHERE published = 1')->fetchColumn();
$completedModules = (int)$pdo->query('SELECT COUNT(*) FROM module_progress WHERE user_id = ' . (int)$user['id'] . ' AND progress_percent = 100')->fetchColumn();
$streak = $pdo->prepare('SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?');
$streak->execute([$user['id']]);
$streakRow = $streak->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0];
$leadCount = (int)$pdo->prepare('SELECT COUNT(*) FROM leads WHERE user_id = ?')->execute([$user['id']]) ?: 0;
$leadCount = (int)$pdo->query('SELECT COUNT(*) FROM leads WHERE user_id = ' . (int)$user['id'])->fetchColumn();

// Notifications (latest 3)
$notif = $pdo->prepare('SELECT title, body, created_at FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 3');
$notif->execute([$user['id']]);
$notifications = $notif->fetchAll();

// Weekly funnel (last 7 days)
$funnel = $pdo->prepare('SELECT COALESCE(SUM(attempts),0) AS attempts, COALESCE(SUM(successes),0) AS successes FROM user_task_logs WHERE user_id = ? AND log_date >= (CURRENT_DATE - INTERVAL 6 DAY)');
$funnel->execute([$user['id']]);
$f = $funnel->fetch() ?: ['attempts'=>0,'successes'=>0];
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


