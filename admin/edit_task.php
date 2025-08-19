<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

// Handle all form submissions for this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($action === 'update') {
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
        }
    }
    // Redirect back to the same edit page to see changes
    header('Location: /admin/edit_task.php?id=' . ($id ?? 0));
    exit;
}

$task_id = (int)($_GET['id'] ?? 0);
if ($task_id === 0) {
    set_flash('error', 'Invalid task ID.');
    header('Location: /admin/tasks.php');
    exit;
}

// Fetch the task to edit
$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    set_flash('error', 'Task not found.');
    header('Location: /admin/tasks.php');
    exit;
}

// Fetch assets for this task
$assets_stmt = $pdo->prepare('SELECT * FROM task_assets WHERE task_id = ? ORDER BY id ASC');
$assets_stmt->execute([$task_id]);
$assets = $assets_stmt->fetchAll();

// Fetch all members for the 'assign to' dropdown
$members = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();

// Display flashes for update messages
foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit Task: <?= htmlspecialchars($task['title']) ?></h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Task Details</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">

      <label>Title</label>
      <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>

      <label>Description</label>
      <textarea name="description" rows="3"><?= htmlspecialchars($task['description']) ?></textarea>

      <div class="grid cols-2">
        <div>
          <label>Type</label>
          <select name="type">
            <?php foreach(['prospecting','followup','training','event','custom'] as $opt): ?>
              <option value="<?= $opt ?>" <?= $task['type']===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Target Count</label>
          <input type="number" name="target_count" value="<?= (int)$task['target_count'] ?>" min="0">
        </div>
      </div>

      <div class="grid cols-3">
        <div>
          <label>Impact (1-5)</label>
          <input type="number" name="impact_score" value="<?= (int)$task['impact_score'] ?>" min="1" max="5">
        </div>
        <div>
          <label>Effort (1-5)</label>
          <input type="number" name="effort_score" value="<?= (int)$task['effort_score'] ?>" min="1" max="5">
        </div>
        <div>
          <label>Priority</label>
          <select name="priority">
            <?php foreach(['low','medium','high'] as $p): ?>
              <option value="<?= $p ?>" <?= $task['priority']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label>Task Date (leave blank for none)</label>
      <input type="date" name="task_date" value="<?= htmlspecialchars($task['task_date'] ?? '') ?>">

      <label>Due Time</label>
      <input type="time" name="due_time" value="<?= htmlspecialchars($task['due_time'] ?? '') ?>">

      <label><input type="checkbox" name="is_daily" <?= (int)$task['is_daily'] ? 'checked' : '' ?>> Repeat Daily</label>

      <label>Repeat Rule</label>
      <select name="repeat_rule">
        <?php foreach(['none','daily','weekly'] as $r): ?>
          <option value="<?= $r ?>" <?= $task['repeat_rule']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Script A</label>
      <textarea name="script_a" rows="3" placeholder="Opening script..."><?= htmlspecialchars($task['script_a']) ?></textarea>

      <label>Script B</label>
      <textarea name="script_b" rows="3" placeholder="Alternative script..."><?= htmlspecialchars($task['script_b']) ?></textarea>

      <div class="grid cols-2">
        <div>
          <label><input type="checkbox" name="is_template" <?= (int)$task['is_template'] ? 'checked' : '' ?>> Save as Template</label>
          <input type="text" name="template_name" placeholder="Template name" value="<?= htmlspecialchars($task['template_name']) ?>">
        </div>
        <div>
          <label>Assign To</label>
          <select name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach($members as $m): ?>
              <option value="<?= (int)$m['id'] ?>" <?= ((string)$task['assigned_to'] === (string)$m['id'])?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="margin-top:12px">
        <button class="btn" type="submit">Save Changes</button>
        <a href="/admin/tasks.php" style="margin-left: 8px;">Cancel</a>
      </div>
    </form>
  </div>
  <div class="card">
    <h3>Task Assets</h3>
    <?php if (empty($assets)): ?>
      <p>No assets attached to this task.</p>
    <?php else: ?>
      <ul>
        <?php foreach($assets as $a): ?>
          <li>
            <a href="<?= htmlspecialchars($a['url']) ?>" target="_blank"><?= htmlspecialchars($a['title']) ?></a>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete asset?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete_asset">
              <input type="hidden" name="asset_id" value="<?= (int)$a['id'] ?>">
              <button class="btn btn-outline" style="padding: 2px 6px;">X</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <hr>
    <h4>Add New Asset</h4>
    <form method="post" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="add_asset">
      <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
      <input type="text" name="asset_title" placeholder="Title" required>
      <input type="url" name="asset_url" placeholder="https://..." required>
      <button class="btn btn-outline">Add Asset</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
