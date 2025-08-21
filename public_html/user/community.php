<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

// Create post/comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
    if (($_POST['type'] ?? '') === 'post') {
      $title = sanitize_string($_POST['title'] ?? '');
      $body = sanitize_string($_POST['body'] ?? '');
      $pdo->prepare('INSERT INTO posts (user_id, title, body, created_at) VALUES (?,?,?, NOW())')->execute([$user['id'], $title, $body]);
    } elseif (($_POST['type'] ?? '') === 'comment') {
      $postId = (int)($_POST['post_id'] ?? 0);
      $body = sanitize_string($_POST['body'] ?? '');
      $pdo->prepare('INSERT INTO comments (post_id, user_id, body, created_at) VALUES (?,?,?, NOW())')->execute([$postId, $user['id'], $body]);
    } elseif (($_POST['type'] ?? '') === 'delete_post') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
    } elseif (($_POST['type'] ?? '') === 'delete_comment') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
    }
    header('Location: /user/community.php');
    exit;
  }
}

$posts = $pdo->query('SELECT p.*, u.name FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC')->fetchAll();

?>

<h2>Community & Achievements</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Start a Discussion</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="type" value="post">
      <label>Title</label>
      <input type="text" name="title" required>
      <label>Message</label>
      <textarea name="body" rows="3" required></textarea>
      <div style="margin-top:12px"><button class="btn">Post</button></div>
    </form>
  </div>
  <div class="card">
    <h3>Recognition Wall</h3>
    <p>Top performers and recent achievements will appear here.</p>
  </div>
</div>

<div class="grid cols-1" style="margin-top:12px">
  <?php foreach($posts as $p): ?>
  <div class="card">
    <strong><?= htmlspecialchars($p['title']) ?></strong>
    <p style="color:#6b7280">By <?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['created_at']) ?></p>
    <p><?= nl2br(htmlspecialchars($p['body'])) ?></p>
    <?php if ((int)$user['id'] === (int)$p['user_id']): ?>
    <form method="post" onsubmit="return confirm('Delete post?')">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="type" value="delete_post">
      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <button class="btn btn-outline">Delete Post</button>
    </form>
    <?php endif; ?>
    <?php $comments = $pdo->prepare('SELECT c.*, u.name FROM comments c JOIN users u ON u.id = c.user_id WHERE post_id = ? ORDER BY c.id ASC'); $comments->execute([$p['id']]); ?>
    <div style="margin-top:8px">
      <?php foreach($comments->fetchAll() as $c): ?>
        <div style="border-left:3px solid #e5e7eb; padding-left:8px; margin:6px 0">
          <strong><?= htmlspecialchars($c['name']) ?></strong> <span style="color:#6b7280">(<?= htmlspecialchars($c['created_at']) ?>)</span>
          <div><?= nl2br(htmlspecialchars($c['body'])) ?></div>
          <?php if ((int)$user['id'] === (int)$c['user_id']): ?>
          <form method="post" onsubmit="return confirm('Delete comment?')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="type" value="delete_comment">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-outline">Delete</button>
          </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <form method="post" style="margin-top:8px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="type" value="comment">
      <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
      <label>Add Comment</label>
      <input type="text" name="body" required>
      <button class="btn btn-outline" style="margin-top:6px">Reply</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


