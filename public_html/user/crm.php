<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

// Handle create/update/delete lead (standard form submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
  } else {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
      $name = sanitize_text($_POST['name'] ?? '', 200);
      $mobile = preg_replace('/[^0-9+]/', '', (string)($_POST['mobile'] ?? ''));
      if ($name === '' || $mobile === '') {
        set_flash('error', 'Name and mobile are required.');
      } else {
        // Duplicate guard
        $dup = $pdo->prepare('SELECT id FROM leads WHERE user_id = ? AND mobile = ? LIMIT 1');
        $dup->execute([$user['id'], $mobile]);
        if ($dup->fetch()) {
          set_flash('success', 'Lead already exists.');
        } else {
          $stmt = $pdo->prepare('INSERT INTO leads (user_id, name, mobile, city, work, age, meeting_date, interest_level, notes, follow_up_date, created_at, updated_at, status) VALUES (?,?,?,?,?,?,?,?,?,?, NOW(), NOW(), ?)');
          $meetingDate = $_POST['meeting_date'] ?: null;
          $followDate = $_POST['follow_up_date'] ?: null;
          $interest = in_array(($_POST['interest_level'] ?? 'Warm'), ['Hot','Warm','Cold'], true) ? $_POST['interest_level'] : 'Warm';
          $persona = sanitize_text($_POST['persona'] ?? '');
          $status = 'open';
          $stmt->execute([
            $user['id'], $name, $mobile, sanitize_text($_POST['city'] ?? '', 120), sanitize_text($_POST['work'] ?? '', 120), sanitize_int($_POST['age'] ?? 0, 10, 100), $meetingDate, $interest, sanitize_text($_POST['notes'] ?? '', 1000), $followDate, $status
          ]);
          award_achievements_for_user((int)$user['id']);
          set_flash('success', 'Lead saved. Tip: ' . guidance_tip_for_interest($interest, $persona));
        }
      }
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $stmt = $pdo->prepare('UPDATE leads SET name = ?, mobile = ?, city = ?, work = ?, age = ?, meeting_date = ?, interest_level = ?, notes = ?, follow_up_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
      $stmt->execute([
        sanitize_text($_POST['name'] ?? '', 200), preg_replace('/[^0-9+]/', '', (string)($_POST['mobile'] ?? '')), sanitize_text($_POST['city'] ?? '', 120), sanitize_text($_POST['work'] ?? '', 120), sanitize_int($_POST['age'] ?? 0, 10, 100), ($_POST['meeting_date'] ?: null), (in_array(($_POST['interest_level'] ?? 'Warm'), ['Hot','Warm','Cold'], true) ? $_POST['interest_level'] : 'Warm'), sanitize_text($_POST['notes'] ?? '', 1000), ($_POST['follow_up_date'] ?: null), $id, $user['id']
      ]);
      set_flash('success', 'Lead updated.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM leads WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
      set_flash('success', 'Lead deleted.');
    }
  }
}

// Filters & pagination
$q = sanitize_text($_GET['q'] ?? '', 120);
$interestFilter = $_GET['interest'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$where = 'user_id = ?';
$params = [$user['id']];
if ($q !== '') { $where .= ' AND (name LIKE ? OR mobile LIKE ? OR city LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if (in_array($interestFilter, ['Hot','Warm','Cold'], true)) { $where .= ' AND interest_level = ?'; $params[] = $interestFilter; }

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="leads.csv"');
  $stmt = $pdo->prepare("SELECT name, mobile, city, work, age, meeting_date, interest_level, notes, follow_up_date FROM leads WHERE $where ORDER BY COALESCE(follow_up_date, created_at) ASC");
  $stmt->execute($params);
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Name','Mobile','City','Work','Age','Meeting Date','Interest','Notes','Follow-up']);
  while ($row = $stmt->fetch()) { fputcsv($out, $row); }
  fclose($out);
  exit;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM leads WHERE $where ORDER BY COALESCE(follow_up_date, created_at) ASC LIMIT $pageSize OFFSET $offset");
$stmt->execute($params);
$leads = $stmt->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Personal CRM</h2>
<form method="get" class="card" style="margin-top:12px">
  <div class="grid cols-3">
    <div>
      <label>Search</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Name, mobile, city">
    </div>
    <div>
      <label>Interest</label>
      <select name="interest">
        <option value="">Any</option>
        <option <?= $interestFilter==='Hot'?'selected':'' ?>>Hot</option>
        <option <?= $interestFilter==='Warm'?'selected':'' ?>>Warm</option>
        <option <?= $interestFilter==='Cold'?'selected':'' ?>>Cold</option>
      </select>
    </div>
    <div style="align-self:end">
      <button class="btn">Filter</button>
      <a class="btn btn-outline" href="?export=csv&amp;q=<?= urlencode($q) ?>&amp;interest=<?= urlencode($interestFilter) ?>">Export CSV</a>
    </div>
  </div>
  <p style="color:#6b7280; margin-top:6px">Total: <?= $total ?> | Page <?= $page ?></p>
</form>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Add Contact</h3>
    <form method="post" id="lead-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Name</label>
      <input type="text" name="name" required data-voice>
      <label>Mobile</label>
      <input type="tel" name="mobile" required>
      <label>City</label>
      <input type="text" name="city" data-voice>
      <label>Work</label>
      <input type="text" name="work" data-voice>
      <label>Age</label>
      <input type="number" name="age" min="10" max="100">
      <label>Meeting Date</label>
      <input type="date" name="meeting_date">
      <label>Interest Level</label>
      <select name="interest_level">
        <option>Hot</option>
        <option>Warm</option>
        <option>Cold</option>
      </select>
      <label>Persona</label>
      <select name="persona">
        <option value="health">Health-conscious</option>
        <option value="income">Income seeker</option>
      </select>
      <label>Notes</label>
      <textarea name="notes" rows="3" data-voice></textarea>
      <label>Follow-up Date</label>
      <input type="date" name="follow_up_date">
      <div style="margin-top:12px"><button class="btn" type="submit">Save Lead</button></div>
    </form>
  </div>
  <div class="card">
    <h3>My Leads</h3>
    <table>
      <thead><tr><th>Name</th><th>Mobile</th><th>Interest</th><th>Follow-up</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($leads as $l): $due = $l['follow_up_date'] && $l['follow_up_date'] <= date('Y-m-d'); ?>
        <tr style="<?= $due ? 'background:#fff7ed' : '' ?>">
          <td>
            <form method="post" style="display:grid; grid-template-columns:1fr; gap:6px">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <input type="text" name="name" value="<?= htmlspecialchars($l['name']) ?>">
          </td>
          <td>
              <input type="text" name="mobile" value="<?= htmlspecialchars($l['mobile']) ?>">
          </td>
          <td>
              <select name="interest_level">
                <option <?= $l['interest_level']==='Hot'?'selected':'' ?>>Hot</option>
                <option <?= $l['interest_level']==='Warm'?'selected':'' ?>>Warm</option>
                <option <?= $l['interest_level']==='Cold'?'selected':'' ?>>Cold</option>
              </select>
          </td>
          <td><input type="date" name="follow_up_date" value="<?= htmlspecialchars($l['follow_up_date'] ?? '') ?>"></td>
          <td>
              <button class="btn btn-outline">Save</button>
            </form>
            <form method="post" onsubmit="return confirm('Delete lead?')" style="margin-top:6px">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <button class="btn btn-outline">Delete</button>
            </form>
            <?php
              $persona = 'general'; // you can extend to store persona per lead
              $tpl = $pdo->prepare('SELECT body FROM whatsapp_templates WHERE persona = ? AND interest = ?');
              $tpl->execute([$persona, $l['interest_level']]);
              $body = $tpl->fetchColumn();
              if (!$body) { $body = 'Hi {{name}}, hello from {{brand}}'; }
              $msg = str_replace(['{{name}}','{{brand}}'], [$l['name'], SITE_BRAND], $body);
              $wa = 'https://wa.me/' . rawurlencode($l['mobile']) . '?text=' . rawurlencode($msg);
            ?>
            <a class="btn btn-outline" target="_blank" href="<?= $wa ?>">WhatsApp</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:8px">
      <?php if ($page > 1): ?><a class="btn btn-outline" href="?page=<?= $page-1 ?>&amp;q=<?= urlencode($q) ?>&amp;interest=<?= urlencode($interestFilter) ?>">Prev</a><?php endif; ?>
      <?php if ($offset + $pageSize < $total): ?><a class="btn btn-outline" href="?page=<?= $page+1 ?>&amp;q=<?= urlencode($q) ?>&amp;interest=<?= urlencode($interestFilter) ?>">Next</a><?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


