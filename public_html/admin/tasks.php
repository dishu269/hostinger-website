<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

// Create/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
  } else {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
      $title = sanitize_text($_POST['title'] ?? '', 200);
      $description = sanitize_text($_POST['description'] ?? '', 1000);
      $taskDate = $_POST['task_date'] ?: null;
      $isDaily = isset($_POST['is_daily']) ? 1 : 0;
      $type = in_array(($_POST['type'] ?? 'custom'), ['prospecting','followup','training','event','custom'], true) ? $_POST['type'] : 'custom';
      $target = sanitize_int($_POST['target_count'] ?? 0, 0, 100000);
      $impact = sanitize_int($_POST['impact_score'] ?? 1, 1, 5);
      $effort = sanitize_int($_POST['effort_score'] ?? 1, 1, 5);
      $priority = in_array(($_POST['priority'] ?? 'medium'), ['low','medium','high'], true) ? $_POST['priority'] : 'medium';
      $dueTime = $_POST['due_time'] ?: null;
      $repeatRule = in_array(($_POST['repeat_rule'] ?? 'none'), ['none','daily','weekly'], true) ? $_POST['repeat_rule'] : 'none';
      $scriptA = sanitize_text($_POST['script_a'] ?? '', 5000);
      $scriptB = sanitize_text($_POST['script_b'] ?? '', 5000);
      $isTemplate = isset($_POST['is_template']) ? 1 : 0;
      $templateName = $isTemplate ? sanitize_text($_POST['template_name'] ?? '', 120) : null;
      $assignedTo = ($_POST['assigned_to'] ?? '') !== '' ? (int)$_POST['assigned_to'] : null;
      if (mb_strlen($title) < 3) {
        set_flash('error', 'Title must be at least 3 characters.');
      } else {
        // Optional duplicate guard for dated tasks (same date + same title)
        if ($taskDate) {
          $dup = $pdo->prepare('SELECT id FROM tasks WHERE task_date = ? AND title = ? LIMIT 1');
          $dup->execute([$taskDate, $title]);
          if ($dup->fetch()) {
            set_flash('error', 'A task with same title already exists for this date.');
          } else {
            $stmt = $pdo->prepare('INSERT INTO tasks (title, description, task_date, is_daily, type, target_count, impact_score, effort_score, priority, due_time, repeat_rule, script_a, script_b, is_template, template_name, assigned_to) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$title, $description, $taskDate, $isDaily, $type, $target, $impact, $effort, $priority, $dueTime, $repeatRule, $scriptA, $scriptB, $isTemplate, $templateName, $assignedTo]);
            set_flash('success', 'Task created.');
          }
        } else {
          $stmt = $pdo->prepare('INSERT INTO tasks (title, description, task_date, is_daily, type, target_count, impact_score, effort_score, priority, due_time, repeat_rule, script_a, script_b, is_template, template_name, assigned_to) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
          $stmt->execute([$title, $description, $taskDate, $isDaily, $type, $target, $impact, $effort, $priority, $dueTime, $repeatRule, $scriptA, $scriptB, $isTemplate, $templateName, $assignedTo]);
          set_flash('success', 'Task created.');
        }
      }
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $title = sanitize_text($_POST['title'] ?? '', 200);
      $description = sanitize_text($_POST['description'] ?? '', 1000);
      $taskDate = $_POST['task_date'] ?: null;
      $isDaily = isset($_POST['is_daily']) ? 1 : 0;
      $type = in_array(($_POST['type'] ?? 'custom'), ['prospecting','followup','training','event','custom'], true) ? $_POST['type'] : 'custom';
      $target = sanitize_int($_POST['target_count'] ?? 0, 0, 100000);
      $impact = sanitize_int($_POST['impact_score'] ?? 1, 1, 5);
      $effort = sanitize_int($_POST['effort_score'] ?? 1, 1, 5);
      $priority = in_array(($_POST['priority'] ?? 'medium'), ['low','medium','high'], true) ? $_POST['priority'] : 'medium';
      $dueTime = $_POST['due_time'] ?: null;
      $repeatRule = in_array(($_POST['repeat_rule'] ?? 'none'), ['none','daily','weekly'], true) ? $_POST['repeat_rule'] : 'none';
      $scriptA = sanitize_text($_POST['script_a'] ?? '', 5000);
      $scriptB = sanitize_text($_POST['script_b'] ?? '', 5000);
      $isTemplate = isset($_POST['is_template']) ? 1 : 0;
      $templateName = $isTemplate ? sanitize_text($_POST['template_name'] ?? '', 120) : null;
      $assignedTo = ($_POST['assigned_to'] ?? '') !== '' ? (int)$_POST['assigned_to'] : null;

      $stmt = $pdo->prepare('UPDATE tasks SET title=?, description=?, task_date=?, is_daily=?, type=?, target_count=?, impact_score=?, effort_score=?, priority=?, due_time=?, repeat_rule=?, script_a=?, script_b=?, is_template=?, template_name=?, assigned_to=? WHERE id = ?');
      $stmt->execute([$title, $description, $taskDate, $isDaily, $type, $target, $impact, $effort, $priority, $dueTime, $repeatRule, $scriptA, $scriptB, $isTemplate, $templateName, $assignedTo, $id]);
      set_flash('success', 'Task updated.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
      set_flash('success', 'Task deleted.');
    } elseif ($action === 'add_asset') {
      $taskId = (int)($_POST['task_id'] ?? 0);
      $title = sanitize_text($_POST['asset_title'] ?? '', 200);
      $url = sanitize_text($_POST['asset_url'] ?? '', 500);
      if ($taskId && $title !== '' && $url !== '') {
        $pdo->prepare('INSERT INTO task_assets (task_id, title, url) VALUES (?,?,?)')->execute([$taskId, $title, $url]);
        set_flash('success', 'Asset added.');
      }
    } elseif ($action === 'delete_asset') {
      $assetId = (int)($_POST['asset_id'] ?? 0);
      $pdo->prepare('DELETE FROM task_assets WHERE id = ?')->execute([$assetId]);
      set_flash('success', 'Asset deleted.');
    } elseif ($action === 'bulk_delete') {
      $ids = array_map('intval', $_POST['ids'] ?? []);
      if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id IN ($in)");
        $stmt->execute($ids);
        set_flash('success', 'Selected tasks deleted.');
      }
    } elseif ($action === 'clone') {
      $id = (int)($_POST['id'] ?? 0);
      $task = $pdo->prepare('SELECT title, description, task_date, is_daily FROM tasks WHERE id = ?');
      $task->execute([$id]);
      if ($row = $task->fetch()) {
        $newDate = $_POST['new_task_date'] ?: null;
        $pdo->prepare('INSERT INTO tasks (title, description, task_date, is_daily) VALUES (?,?,?,?)')
            ->execute([$row['title'], $row['description'], $newDate, (int)$row['is_daily']]);
        set_flash('success', 'Task cloned.');
      }
    }
  }
}

// Create from template
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'create_from_template' && verify_csrf_token($_POST['csrf_token'] ?? null)) {
  $tplId = (int)($_POST['template_id'] ?? 0);
  $newDate = $_POST['new_task_date'] ?: null;
  $tpl = $pdo->prepare('SELECT title, description, type, target_count, impact_score, effort_score, priority, due_time, repeat_rule, script_a, script_b FROM tasks WHERE id = ? AND is_template = 1');
  $tpl->execute([$tplId]);
  if ($row = $tpl->fetch()) {
    $pdo->prepare('INSERT INTO tasks (title, description, task_date, is_daily, type, target_count, impact_score, effort_score, priority, due_time, repeat_rule, script_a, script_b, is_template) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0)')
        ->execute([$row['title'], $row['description'], $newDate, 0, $row['type'], $row['target_count'], $row['impact_score'], $row['effort_score'], $row['priority'], $row['due_time'], $row['repeat_rule'], $row['script_a'], $row['script_b']]);
    set_flash('success', 'Task created from template.');
  } else {
    set_flash('error', 'Template not found.');
  }
}

// Filters & pagination
$q = sanitize_text($_GET['q'] ?? '', 120);
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$where = '1=1';
$params = [];
if ($q !== '') { $where .= ' AND title LIKE ?'; $params[] = "%$q%"; }
if ($from !== '') { $where .= ' AND (task_date IS NOT NULL AND task_date >= ?)'; $params[] = $from; }
if ($to !== '') { $where .= ' AND (task_date IS NOT NULL AND task_date <= ?)'; $params[] = $to; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE $where ORDER BY COALESCE(task_date, CURRENT_DATE) DESC, id DESC LIMIT $pageSize OFFSET $offset");
$stmt->execute($params);
$tasks = $stmt->fetchAll();
$assetsByTask = [];
if ($tasks) {
  $ids = implode(',', array_map('intval', array_column($tasks, 'id')));
  if ($ids) {
    foreach ($pdo->query("SELECT * FROM task_assets WHERE task_id IN ($ids)") as $a) {
      $assetsByTask[(int)$a['task_id']][] = $a;
    }
  }
}
$templates = $pdo->query('SELECT id, template_name FROM tasks WHERE is_template = 1 ORDER BY template_name ASC')->fetchAll();
$members = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Daily Tasks</h2>
<form method="get" class="card" style="margin-top:12px">
  <div class="grid cols-3">
    <div>
      <label>Search Title</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="e.g., Reach out">
    </div>
    <div>
      <label>From</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    </div>
    <div>
      <label>To</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    </div>
  </div>
  <div style="margin-top:8px"><button class="btn">Filter</button></div>
  <p style="color:#6b7280; margin-top:6px">Total: <?= $total ?> | Page <?= $page ?></p>
  </form>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Create Task</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>Description</label>
      <textarea name="description" rows="3"></textarea>
      <div class="grid cols-2">
        <div>
          <label>Type</label>
          <select name="type">
            <option value="prospecting">Prospecting</option>
            <option value="followup">Follow-up</option>
            <option value="training">Training</option>
            <option value="event">Event</option>
            <option value="custom" selected>Custom</option>
          </select>
        </div>
        <div>
          <label>Target Count</label>
          <input type="number" name="target_count" value="0" min="0">
        </div>
      </div>
      <div class="grid cols-3">
        <div>
          <label>Impact (1-5)</label>
          <input type="number" name="impact_score" value="1" min="1" max="5">
        </div>
        <div>
          <label>Effort (1-5)</label>
          <input type="number" name="effort_score" value="1" min="1" max="5">
        </div>
        <div>
          <label>Priority</label>
          <select name="priority">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>
      <label>Task Date (leave blank for none)</label>
      <input type="date" name="task_date">
      <label>Due Time</label>
      <input type="time" name="due_time">
      <label><input type="checkbox" name="is_daily"> Repeat Daily</label>
      <label>Repeat Rule</label>
      <select name="repeat_rule">
        <option value="none" selected>None</option>
        <option value="daily">Daily</option>
        <option value="weekly">Weekly</option>
      </select>
      <label>Script A</label>
      <textarea name="script_a" rows="3" placeholder="Opening script..."></textarea>
      <label>Script B</label>
      <textarea name="script_b" rows="3" placeholder="Alternative script..."></textarea>
      <div class="grid cols-2">
        <div>
          <label><input type="checkbox" name="is_template"> Save as Template</label>
          <input type="text" name="template_name" placeholder="Template name">
        </div>
        <div>
          <label>Assign To</label>
          <select name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach($members as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
  <div class="card">
    <h3>Create from Template</h3>
    <form method="post" style="margin-bottom:16px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create_from_template">
      <div class="grid cols-2">
        <div>
          <label>Template</label>
          <select name="template_id" required>
            <option value="">Select...</option>
            <?php foreach($templates as $tpl): ?>
              <option value="<?= (int)$tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name'] ?? ('Template #' . (int)$tpl['id'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Task Date</label>
          <input type="date" name="new_task_date" required>
        </div>
      </div>
      <div style="margin-top:12px"><button class="btn">Create</button></div>
    </form>
    <h3>All Tasks</h3>
    <form method="post" id="bulkForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="bulk_delete">
    </form>
    <table>
      <thead><tr><th>Title</th><th>Type</th><th>Target</th><th>Priority</th><th>Date</th><th>Daily</th><th>Assigned</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($tasks as $t): ?>
        <tr>
          <td>
            <form method="post" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <input type="text" name="title" value="<?= htmlspecialchars($t['title']) ?>">
          </td>
          <td>
            <select name="type">
              <?php foreach(['prospecting','followup','training','event','custom'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $t['type']===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="number" name="target_count" value="<?= (int)$t['target_count'] ?>" min="0" style="width:90px"></td>
          <td>
            <select name="priority">
              <?php foreach(['low','medium','high'] as $p): ?>
                <option value="<?= $p ?>" <?= $t['priority']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="date" name="task_date" value="<?= htmlspecialchars($t['task_date'] ?? '') ?>"></td>
          <td><input type="checkbox" name="is_daily" <?= (int)$t['is_daily'] ? 'checked' : '' ?>></td>
          <td>
            <select name="assigned_to">
              <option value="">Unassigned</option>
              <?php foreach($members as $m): ?>
                <option value="<?= (int)$m['id'] ?>" <?= ((string)$t['assigned_to'] === (string)$m['id'])?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
              <button class="btn btn-outline">Save</button>
            </form>
            <form method="post" onsubmit="return confirm('Delete task?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="btn btn-outline">Delete</button>
            </form>
            <form method="post" style="margin-top:6px">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="clone">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <input type="date" name="new_task_date" value="">
              <button class="btn btn-outline">Clone</button>
            </form>
            <form method="post" style="margin-top:6px" onsubmit="return confirm('Bulk delete this task?')" action="#" onclick="event.preventDefault(); const f=document.getElementById('bulkForm'); const i=document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value='<?= (int)$t['id'] ?>'; f.appendChild(i); f.submit();">
              <button class="btn btn-outline">Add to Bulk Delete</button>
            </form>
            <?php $aid = (int)$t['id']; $taskAssets = $assetsByTask[$aid] ?? []; ?>
            <div style="margin-top:8px">
              <strong>Assets</strong>
              <ul>
                <?php foreach($taskAssets as $a): ?>
                  <li>
                    <a href="<?= htmlspecialchars($a['url']) ?>" target="_blank"><?= htmlspecialchars($a['title']) ?></a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete asset?')">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="action" value="delete_asset">
                      <input type="hidden" name="asset_id" value="<?= (int)$a['id'] ?>">
                      <button class="btn btn-outline">Delete</button>
                    </form>
                  </li>
                <?php endforeach; ?>
              </ul>
              <form method="post" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="add_asset">
                <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
                <input type="text" name="asset_title" placeholder="Title">
                <input type="url" name="asset_url" placeholder="https://...">
                <button class="btn btn-outline">Add Asset</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:8px">
      <?php if ($page > 1): ?><a class="btn btn-outline" href="?page=<?= $page-1 ?>&amp;q=<?= urlencode($q) ?>&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>">Prev</a><?php endif; ?>
      <?php if ($offset + $pageSize < $total): ?><a class="btn btn-outline" href="?page=<?= $page+1 ?>&amp;q=<?= urlencode($q) ?>&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>">Next</a><?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


