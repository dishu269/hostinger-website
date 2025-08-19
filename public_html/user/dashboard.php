<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php'; // Include the new functions file
require_member();

$pdo = get_db();
$userId = (int)$user['id'];

// Fetch all dashboard data using the new functions
$todayTask = get_today_task($pdo);
$motivation = get_motivation_message($pdo);
$due = get_due_leads($pdo, $userId);
$kpis = get_dashboard_kpis($pdo, $userId);
$streakRow = get_user_streak($pdo, $userId);
$notifications = get_notifications($pdo, $userId);
$weekly_chart_data = get_weekly_funnel_chart_data($pdo, $userId);

// We still need the total funnel stats for the KPI card
$f = [
    'attempts' => array_sum($weekly_chart_data['datasets'][0]['data']),
    'successes' => array_sum($weekly_chart_data['datasets'][1]['data']),
];

// Extract KPI values for use in the template
$completedTasks = $kpis['completed_tasks'];
$totalModules = $kpis['total_modules'];
$completedModules = $kpis['completed_modules'];
?>

<h2>Namaste <?= htmlspecialchars($user['name']) ?> 👋</h2>

<div class="dashboard-grid mt-3">
  <!-- Main Content Column -->
  <div class="grid" style="align-content: start;">
    <div class="card">
      <h3>Aaj ka Action</h3>
      <?php if ($todayTask): ?>
        <strong><?= htmlspecialchars($todayTask['title']) ?></strong>
        <p class="text-muted"><?= nl2br(htmlspecialchars($todayTask['description'])) ?></p>
      <?php else: ?>
        <p>Aaj koi specific task nahi hai. Learning Hub se start karein.</p>
      <?php endif; ?>
      <a class="btn mt-2" href="/user/tasks.php">Tasks khole</a>
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
            <span class="text-muted">(<?= htmlspecialchars($d['follow_up_date']) ?>)</span>
            <a class="btn btn-outline ml-2" target="_blank" href="<?= $wa ?>">WhatsApp</a>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>Aaj koi due follow-up nahi hai.</p>
      <?php endif; ?>
      <a class="btn mt-2" href="/user/crm.php">CRM khole</a>
    </div>

    <div class="card">
      <h3>Last 7 Days Activity</h3>
      <canvas id="weeklyActivityChart"></canvas>
      <script id="weeklyActivityData" type="application/json"><?= json_encode($weekly_chart_data) ?></script>
    </div>

    <?php if ($notifications): ?>
    <div class="card">
      <h3>Updates</h3>
      <ul>
        <?php foreach($notifications as $n): ?>
        <li><strong><?= htmlspecialchars($n['title']) ?></strong> — <?= htmlspecialchars($n['body']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sidebar Column -->
  <div class="grid" style="align-content: start;">
    <div class="card motivation">
      <h3><?= htmlspecialchars($motivation['title'] ?? 'Keep Going!') ?></h3>
      <p><?= htmlspecialchars($motivation['body'] ?? 'Roz thoda, life me bada. Bas aaj ka step lein!') ?></p>
    </div>

    <div class="card">
      <h3>Key Stats</h3>
      <div class="grid cols-1 mt-2">
        <div class="card kpi"><span>Tasks Complete</span><strong><?= $completedTasks ?></strong></div>
        <div class="card kpi"><span>Learning Progress</span><strong><?= $completedModules ?>/<?= $totalModules ?></strong></div>
        <div class="card kpi"><span>Streak</span><strong><?= (int)$streakRow['current_streak'] ?> din 🔥 (Best <?= (int)$streakRow['longest_streak'] ?>)</strong></div>
        <div class="card kpi"><span>7 Day Attempts</span><strong><?= (int)$f['attempts'] ?></strong></div>
        <div class="card kpi"><span>7 Day Success</span><strong><?= (int)$f['successes'] ?></strong></div>
      </div>
    </div>

    <div class="card">
      <h3>Quick Links</h3>
      <div class="grid cols-1 mt-2">
        <a class="card" href="/user/learning.php"><strong>Learning Hub</strong><p>Modules complete karke skill badhao.</p></a>
        <a class="card" href="/user/community.php"><strong>Community</strong><p>Questions poochho, help lo.</p></a>
        <a class="card" href="/user/resources.php"><strong>Resources</strong><p>PDFs, scripts aur social content ready.</p></a>
        <a class="card" href="/user/tasks.php?view=kanban"><strong>Kanban Board</strong><p>To‑do → Doing → Done drag & drop.</p></a>
      </div>
    </div>

    <div class="card">
      <h3>Quick Start (Naye member ke liye)</h3>
      <ol>
        <li>3 naye contacts CRM me add karo</li>
        <li>Aaj ka Task complete karo</li>
        <li>Learning Hub se 1 module 100% karo</li>
      </ol>
      <div class="mt-2">
        <a class="btn" href="/user/crm.php">CRM me Lead Add karo</a>
        <a class="btn btn-outline ml-2" href="/user/learning.php">Learning start karo</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


