<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$numUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$numLeads = (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$numTasks = (int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
$numModules = (int)$pdo->query('SELECT COUNT(*) FROM learning_modules')->fetchColumn();

// Top performers (by leads in last 30 days)
$top = $pdo->query("SELECT u.name, COUNT(l.id) as leads30 FROM users u LEFT JOIN leads l ON l.user_id = u.id AND l.created_at > (NOW() - INTERVAL 30 DAY) GROUP BY u.id ORDER BY leads30 DESC LIMIT 5")->fetchAll();

// Less active (no login in 14 days)
$struggling = $pdo->query("SELECT name, COALESCE(DATEDIFF(NOW(), last_login), 999) as days FROM users WHERE (last_login IS NULL OR last_login < (NOW() - INTERVAL 14 DAY)) ORDER BY days DESC LIMIT 5")->fetchAll();
?>

<h2>Admin Dashboard</h2>
<div class="grid cols-4" style="margin-top:12px">
  <div class="card kpi"><span>Members</span><strong><?= $numUsers ?></strong></div>
  <div class="card kpi"><span>Leads</span><strong><?= $numLeads ?></strong></div>
  <div class="card kpi"><span>Tasks</span><strong><?= $numTasks ?></strong></div>
  <div class="card kpi"><span>Modules</span><strong><?= $numModules ?></strong></div>
</div>

<div class="grid cols-3" style="margin-top:12px">
  <a class="card" href="/admin/tasks.php"><strong>Manage Daily Tasks</strong><p>Create and schedule daily tasks.</p></a>
  <a class="card" href="/admin/modules.php"><strong>Manage Learning Modules</strong><p>Videos, PDFs, articles.</p></a>
  <a class="card" href="/admin/whatsapp_templates.php"><strong>WhatsApp Templates</strong><p>Persona-based messages for quick follow-ups.</p></a>
  <a class="card" href="/admin/resources.php"><strong>Resources</strong><p>Brochures, scripts, social content.</p></a>
  <a class="card" href="/admin/users.php"><strong>Team Members</strong><p>View and manage users.</p></a>
  <a class="card" href="/admin/achievements.php"><strong>Achievements</strong><p>Badges and thresholds.</p></a>
  <a class="card" href="/admin/events.php"><strong>Events</strong><p>Announce trainings and meets.</p></a>
  <a class="card" href="/admin/messages.php"><strong>Broadcast</strong><p>Motivation and announcements.</p></a>
</div>

<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Top Performers (30d)</h3>
    <ul>
      <?php foreach($top as $row): ?>
        <li><?= htmlspecialchars($row['name']) ?> — <?= (int)$row['leads30'] ?> leads</li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card">
    <h3>Needs Attention</h3>
    <ul>
      <?php foreach($struggling as $s): ?>
        <li><?= htmlspecialchars($s['name']) ?> — inactive <?= (int)$s['days'] ?> days</li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


