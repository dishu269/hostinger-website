<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid CSRF token.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = sanitize_string($_POST['title'] ?? '');
        $description = sanitize_string($_POST['description'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $location = sanitize_string($_POST['location'] ?? '');

        $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, location=? WHERE id=?');
        $stmt->execute([$title, $description, $eventDate, $location, $id]);
        set_flash('success', 'Event updated.');
        header('Location: /admin/events.php');
        exit;
    }
}

$event_id = (int)($_GET['id'] ?? 0);
if ($event_id === 0) {
    set_flash('error', 'Invalid event ID.');
    header('Location: /admin/events.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    set_flash('error', 'Event not found.');
    header('Location: /admin/events.php');
    exit;
}

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>Edit Event</h2>
<div class="card" style="margin-top:12px">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= (int)$event['id'] ?>">

    <label>Title</label>
    <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required>

    <label>Description</label>
    <textarea name="description" rows="3"><?= htmlspecialchars($event['description']) ?></textarea>

    <label>Event Date</label>
    <input type="datetime-local" name="event_date" value="<?= htmlspecialchars(str_replace(' ', 'T', $event['event_date'])) ?>">

    <label>Location</label>
    <input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>">

    <div style="margin-top:12px">
        <button class="btn" type="submit">Save Changes</button>
        <a href="/admin/events.php" style="margin-left: 8px;">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
