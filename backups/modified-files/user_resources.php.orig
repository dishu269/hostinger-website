<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

$resources = $pdo->query('SELECT * FROM resources WHERE published = 1 ORDER BY id DESC')->fetchAll();
?>

<h2>Resources</h2>
<div style="margin-top:12px">
  <?php if (empty($resources)): ?>
    <div class="card" style="text-align: center; padding: 32px;">
      <h3>No Resources Available Yet</h3>
      <p>Check back soon for training materials, PDFs, and more.</p>
      <a href="/user/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
  <?php else: ?>
    <div class="grid cols-3">
      <?php foreach($resources as $r): ?>
      <a class="card" href="<?= htmlspecialchars($r['file_url']) ?>" target="_blank">
        <strong><?= htmlspecialchars($r['title']) ?></strong>
        <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
        <span class="badge"><?= htmlspecialchars(strtoupper($r['type'])) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
