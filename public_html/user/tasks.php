<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

// Tabs and filters
$today = (new DateTime('today'))->format('Y-m-d');
$weekEnd = (new DateTime('+6 days'))->format('Y-m-d');
$tab = $_GET['tab'] ?? 'today';
$view = $_GET['view'] ?? 'list'; // list | calendar | kanban
$typeFilter = $_GET['type'] ?? '';

$where = [];
$params = [];

// Assigned tasks default filter
$where[] = '(assigned_to IS NULL OR assigned_to = ?)';
$params[] = $user['id'];

if ($tab === 'today') {
  $where[] = '(task_date = ? OR is_daily = 1)';
  $params[] = $today;
} elseif ($tab === 'week') {
  $where[] = '((task_date BETWEEN ? AND ?) OR is_daily = 1)';
  $params[] = $today;
  $params[] = $weekEnd;
} elseif ($tab === 'backlog') {
  $where[] = 'task_date IS NULL';
} elseif ($tab === 'assigned') {
  $where[] = 'assigned_to = ?';
  $params[] = $user['id'];
}

if (in_array($typeFilter, ['prospecting','followup','training','event','custom'], true)) {
  $where[] = 'type = ?';
  $params[] = $typeFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$stmt = $pdo->prepare("SELECT * FROM tasks $whereSql ORDER BY COALESCE(task_date, DATE('1000-01-01')) ASC, priority DESC, id DESC");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Completed lookup
$stmt = $pdo->prepare('SELECT task_id FROM user_tasks WHERE user_id = ?');
$stmt->execute([$user['id']]);
$completed = array_column($stmt->fetchAll(), 'task_id');

// Today logs for progress
$logsStmt = $pdo->prepare('SELECT task_id, attempts, successes FROM user_task_logs WHERE user_id = ? AND log_date = ?');
$logsStmt->execute([$user['id'], $today]);
$logs = [];
foreach ($logsStmt->fetchAll() as $r) { $logs[(int)$r['task_id']] = ['attempts'=>(int)$r['attempts'], 'successes'=>(int)$r['successes']]; }
?>

<h2>My Tasks</h2>

<div class="card" style="margin-top:8px">
  <div class="grid cols-2" style="align-items:center; gap:12px">
    <div class="tabs">
      <a class="btn <?= $tab==='today'?'':'btn-outline' ?>" href="?tab=today">Today</a>
      <a class="btn <?= $tab==='week'?'':'btn-outline' ?>" href="?tab=week">This Week</a>
      <a class="btn <?= $tab==='backlog'?'':'btn-outline' ?>" href="?tab=backlog">Backlog</a>
      <a class="btn <?= $tab==='assigned'?'':'btn-outline' ?>" href="?tab=assigned">Assigned</a>
    </div>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end">
      <form method="get" style="display:flex; gap:8px; align-items:center">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <label>Type</label>
        <select name="type" onchange="this.form.submit()">
          <option value="">All</option>
          <?php foreach(['prospecting','followup','training','event','custom'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $typeFilter===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <div class="tabs">
        <a class="btn <?= $view==='list'?'':'btn-outline' ?>" href="?tab=<?= urlencode($tab) ?>&type=<?= urlencode($typeFilter) ?>&view=list">List</a>
        <a class="btn <?= $view==='calendar'?'':'btn-outline' ?>" href="?tab=<?= urlencode($tab) ?>&type=<?= urlencode($typeFilter) ?>&view=calendar">Calendar</a>
        <a class="btn <?= $view==='kanban'?'':'btn-outline' ?>" href="?tab=<?= urlencode($tab) ?>&type=<?= urlencode($typeFilter) ?>&view=kanban">Kanban</a>
      </div>
    </div>
  </div>
</div>

<?php if ($view === 'calendar'): ?>
  <div class="card" style="margin-top:12px">
    <h3>Calendar (7 days)</h3>
    <div class="grid cols-7" style="gap:8px">
      <?php for($i=0;$i<7;$i++): $d=(new DateTime("+$i day"))->format('Y-m-d'); ?>
        <div class="card" style="padding:10px">
          <strong><?= htmlspecialchars($d) ?></strong>
          <ul style="margin-top:6px">
            <?php foreach($tasks as $t): if (($t['task_date'] === $d) || ((int)$t['is_daily'])): ?>
              <li><?= htmlspecialchars($t['title']) ?></li>
            <?php endif; endforeach; ?>
          </ul>
        </div>
      <?php endfor; ?>
    </div>
  </div>
<?php elseif ($view === 'kanban'): ?>
  <?php
    $columns = ['todo' => [], 'doing' => [], 'done' => []];
    // Load states
    $statesStmt = $pdo->prepare('SELECT task_id, state FROM user_task_state WHERE user_id = ?');
    $statesStmt->execute([$user['id']]);
    $stateMap = [];
    foreach ($statesStmt->fetchAll() as $s) { $stateMap[(int)$s['task_id']] = $s['state']; }
    foreach ($tasks as $t) {
      $state = $stateMap[(int)$t['id']] ?? 'todo';
      $columns[$state][] = $t;
    }
  ?>
  <div class="grid cols-3" style="margin-top:12px">
    <?php foreach(['todo'=>'To-do','doing'=>'Doing','done'=>'Done'] as $key=>$label): ?>
    <div class="card">
      <h3><?= $label ?></h3>
      <?php foreach($columns[$key] as $t): $done = in_array($t['id'], $completed, true); ?>
        <div class="card" style="margin-top:8px">
          <strong><?= htmlspecialchars($t['title']) ?></strong>
          <p style="color:#6b7280; margin:6px 0"><?= htmlspecialchars($t['task_date'] ?? '') ?></p>
          <form method="post" action="/user/task_state.php" style="display:flex; gap:6px; flex-wrap:wrap">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
            <select name="state">
              <option value="todo" <?= $key==='todo'?'selected':'' ?>>To-do</option>
              <option value="doing" <?= $key==='doing'?'selected':'' ?>>Doing</option>
              <option value="done" <?= $key==='done'?'selected':'' ?>>Done</option>
            </select>
            <button class="btn btn-outline">Move</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
<div class="grid cols-2" style="margin-top:12px">
  <?php foreach($tasks as $t): 
    $done = in_array($t['id'], $completed, true);
    $log = $logs[$t['id']] ?? ['attempts'=>0,'successes'=>0];
    $target = (int)($t['target_count'] ?? 0);
    $succ = (int)$log['successes'];
    $progress = $target > 0 ? min(100, (int)round($succ * 100 / $target)) : 0;
  ?>
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap">
      <strong><?= htmlspecialchars($t['title']) ?></strong>
      <span class="badge" style="background:#00356a"><?= htmlspecialchars(ucfirst($t['type'] ?? 'custom')) ?></span>
      <span class="badge" style="background:<?= ($t['priority']==='high')?'#dc2626':(($t['priority']==='low')?'#6b7280':'#006400') ?>"><?= htmlspecialchars(ucfirst($t['priority'] ?? 'medium')) ?></span>
    </div>
    <?php if (!empty($t['task_date']) || !empty($t['due_time']) || (int)$t['is_daily']): ?>
      <p style="color:#6b7280; margin:6px 0">
        <?php if (!empty($t['task_date'])): ?>📅 <?= htmlspecialchars($t['task_date']) ?><?php endif; ?>
        <?php if (!empty($t['due_time'])): ?> ⏰ <?= htmlspecialchars(substr($t['due_time'],0,5)) ?><?php endif; ?>
        <?php if ((int)$t['is_daily']): ?> • Repeats daily<?php endif; ?>
      </p>
    <?php endif; ?>
    <p><?= nl2br(htmlspecialchars($t['description'])) ?></p>

    <?php if ($target > 0): ?>
      <div class="progress" style="margin-top:8px"><span style="width:<?= $progress ?>%"></span></div>
      <p style="color:#6b7280; margin:6px 0">Progress: <?= $succ ?>/<?= $target ?> successes today (Attempts: <?= (int)$log['attempts'] ?>)</p>
    <?php endif; ?>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px">
      <form method="post" action="/user/task_log.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
        <input type="hidden" name="action" value="attempt">
        <input type="hidden" name="next" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <button class="btn btn-outline">+1 Attempt</button>
      </form>
      <form method="post" action="/user/task_log.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
        <input type="hidden" name="action" value="success">
        <input type="hidden" name="next" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <button class="btn">+1 Success</button>
      </form>
      <?php if (!$done): ?>
      <form method="post" action="/user/tasks.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
        <button class="btn btn-outline">Mark Complete</button>
      </form>
      <?php else: ?>
        <span class="badge">Completed</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($t['script_a']) || !empty($t['script_b'])): ?>
      <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap">
        <?php if (!empty($t['script_a'])): ?>
          <button class="btn btn-outline" type="button" onclick="navigator.clipboard.writeText(<?= json_encode($t['script_a']) ?>).then(()=>alert('Script A copied'))">Copy Script A</button>
          <a class="btn btn-outline" target="_blank" href="https://wa.me/?text=<?= rawurlencode($t['script_a']) ?>">WhatsApp A</a>
        <?php endif; ?>
        <?php if (!empty($t['script_b'])): ?>
          <button class="btn btn-outline" type="button" onclick="navigator.clipboard.writeText(<?= json_encode($t['script_b']) ?>).then(()=>alert('Script B copied'))">Copy Script B</button>
          <a class="btn btn-outline" target="_blank" href="https://wa.me/?text=<?= rawurlencode($t['script_b']) ?>">WhatsApp B</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php
      // Show task assets
      $assets = $pdo->prepare('SELECT title, url FROM task_assets WHERE task_id = ?');
      $assets->execute([(int)$t['id']]);
      $assetRows = $assets->fetchAll();
      if ($assetRows): ?>
      <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap">
        <?php foreach($assetRows as $a): ?>
          <a class="btn btn-outline" target="_blank" href="<?= htmlspecialchars($a['url']) ?>"><?= htmlspecialchars($a['title']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($t['checklist'])): $list = json_decode($t['checklist'], true); if (is_array($list)): ?>
      <div style="margin-top:8px">
        <strong>Checklist</strong>
        <ul>
          <?php foreach($list as $item): ?>
            <li><?= htmlspecialchars((string)$item) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


