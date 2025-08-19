<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $name = sanitize_string($_POST['name'] ?? '');
    $city = sanitize_string($_POST['city'] ?? '');
    $phone = sanitize_string($_POST['phone'] ?? '');
    $pdo->prepare('UPDATE users SET name = ?, city = ?, phone = ? WHERE id = ?')->execute([$name, $city, $phone, $user['id']]);
    $_SESSION['user']['name'] = $name;
    set_flash('success', 'Profile updated.');
    header('Location: /user/profile.php');
    exit;
  }
}

$row = $pdo->prepare('SELECT name, email, city, phone FROM users WHERE id = ?');
$row->execute([$user['id']]);
$row = $row->fetch();

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>My Profile</h2>
<div class="card" style="margin-top:12px">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
    <label>Email</label>
    <input type="email" value="<?= htmlspecialchars($row['email']) ?>" disabled>
    <label>City</label>
    <input type="text" name="city" value="<?= htmlspecialchars($row['city'] ?? '') ?>">
    <label>Phone</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($row['phone'] ?? '') ?>">
    <div style="margin-top:12px"><button class="btn">Save</button></div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


