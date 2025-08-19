<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

$resources = $pdo->query('SELECT * FROM resources WHERE published = 1 ORDER BY id DESC')->fetchAll();
?>

<h2>Resources</h2>
<div class="grid cols-3" style="margin-top:12px">
  <?php foreach($resources as $r): ?>
  <a class="card" href="<?= htmlspecialchars($r['file_url']) ?>" target="_blank">
    <strong><?= htmlspecialchars($r['title']) ?></strong>
    <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
    <span class="badge"><?= htmlspecialchars(strtoupper($r['type'])) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
