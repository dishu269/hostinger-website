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
    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
      set_flash('success', 'Task deleted.');
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
    <form method="post" id="create-task-form" action="/admin/ajax_admin_actions.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create_task">
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
    <form method="post" id="bulkActionForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="bulk_delete">
        <div style="margin-bottom: 12px;">
            <button type="submit" class="btn btn-outline" onclick="return confirm('Are you sure you want to delete all selected tasks?')">Delete Selected</button>
        </div>
        <table>
          <thead>
            <tr>
              <th><input type="checkbox" onclick="document.querySelectorAll('.task-checkbox').forEach(c => c.checked = this.checked)"></th>
              <th>Title</th>
              <th>Type</th>
              <th>Priority</th>
              <th>Date</th>
              <th>Daily</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tasks)): ?>
              <tr><td colspan="8" style="text-align: center;">No tasks found.</td></tr>
            <?php endif; ?>
            <?php foreach($tasks as $t): ?>
            <tr>
              <td><input type="checkbox" name="ids[]" value="<?= (int)$t['id'] ?>" class="task-checkbox"></td>
              <td><?= htmlspecialchars($t['title']) ?></td>
              <td><span class="badge"><?= htmlspecialchars(ucfirst($t['type'])) ?></span></td>
              <td><?= htmlspecialchars(ucfirst($t['priority'])) ?></td>
              <td><?= htmlspecialchars($t['task_date'] ?? 'N/A') ?></td>
              <td><?= (int)$t['is_daily'] ? 'Yes' : 'No' ?></td>
              <td style="display:flex; gap: 6px; align-items: center;">
                <a href="/admin/edit_task.php?id=<?= (int)$t['id'] ?>" class="btn btn-outline">Edit</a>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this task?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                  <button class="btn btn-outline">Delete</button>
                </form>
                <form method="post" onsubmit="return confirm('Clone this task for today?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="clone">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="new_task_date" value="<?= date('Y-m-d') ?>">
                    <button class="btn btn-outline">Clone for Today</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </form>
    <div style="margin-top:8px">
      <?php if ($page > 1): ?><a class="btn btn-outline" href="?page=<?= $page-1 ?>&amp;q=<?= urlencode($q) ?>&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>">Prev</a><?php endif; ?>
      <?php if ($offset + $pageSize < $total): ?><a class="btn btn-outline" href="?page=<?= $page+1 ?>&amp;q=<?= urlencode($q) ?>&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>">Next</a><?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


