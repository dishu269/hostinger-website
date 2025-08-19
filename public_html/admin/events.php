<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
  } else {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
      $title = sanitize_string($_POST['title'] ?? '');
      $description = sanitize_string($_POST['description'] ?? '');
      $eventDate = $_POST['event_date'] ?? '';
      $location = sanitize_string($_POST['location'] ?? '');
      $stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, location) VALUES (?,?,?,?)');
      $stmt->execute([$title, $description, $eventDate, $location]);
      set_flash('success', 'Event created.');
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $title = sanitize_string($_POST['title'] ?? '');
      $description = sanitize_string($_POST['description'] ?? '');
      $eventDate = $_POST['event_date'] ?? '';
      $location = sanitize_string($_POST['location'] ?? '');
      $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, location=? WHERE id=?');
      $stmt->execute([$title, $description, $eventDate, $location, $id]);
      set_flash('success', 'Event updated.');
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
      set_flash('success', 'Event deleted.');
    }
  }
}

$events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC, id DESC')->fetchAll();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Events</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Create Event</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>Description</label>
      <textarea name="description" rows="3"></textarea>
      <label>Event Date</label>
      <input type="datetime-local" name="event_date">
      <label>Location</label>
      <input type="text" name="location">
      <div style="margin-top:12px"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
  <div class="card">
    <h3>All Events</h3>
    <table>
      <thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($events as $e): ?>
        <tr>
          <td>
            <form method="post" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
              <input type="text" name="title" value="<?= htmlspecialchars($e['title']) ?>">
          </td>
          <td><input type="datetime-local" name="event_date" value="<?= htmlspecialchars(str_replace(' ', 'T', $e['event_date'])) ?>"></td>
          <td><input type="text" name="location" value="<?= htmlspecialchars($e['location']) ?>"></td>
          <td>
              <button class="btn btn-outline">Save</button>
            </form>
            <form method="post" onsubmit="return confirm('Delete event?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
              <button class="btn btn-outline">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


