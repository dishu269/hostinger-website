<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

// Handle Q&A form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $type = $_POST['type'];
        $pdo->beginTransaction();
        try {
            if ($type === 'post') {
                $title = sanitize_string($_POST['title'] ?? '');
                $body = sanitize_string($_POST['body'] ?? '');
                if (!empty($title) && !empty($body)) {
                    $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body, created_at) VALUES (?, ?, ?, NOW())');
                    $stmt->execute([$user['id'], $title, $body]);

                    // Notify all other users
                    $users_to_notify = $pdo->prepare('SELECT id FROM users WHERE id != ?');
                    $users_to_notify->execute([$user['id']]);
                    $notif_stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, body, notif_type) VALUES (?, ?, ?, ?)');
                    $notif_title = 'New Question: ' . htmlspecialchars(mb_substr($title, 0, 100));
                    $notif_body = htmlspecialchars($user['name']) . ' asked a new question.';
                    foreach ($users_to_notify->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                        $notif_stmt->execute([$uid, $notif_title, $notif_body, 'new_post']);
                    }
                }
            } elseif ($type === 'comment') {
                $postId = (int)($_POST['post_id'] ?? 0);
                $body = sanitize_string($_POST['body'] ?? '');
                if (!empty($body) && $postId > 0) {
                    $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())');
                    $stmt->execute([$postId, $user['id'], $body]);

                    $stmt = $pdo->prepare('UPDATE users SET points = points + 10 WHERE id = ?');
                    $stmt->execute([$user['id']]);

                    $post_owner_stmt = $pdo->prepare('SELECT user_id, title FROM posts WHERE id = ?');
                    $post_owner_stmt->execute([$postId]);
                    $post_info = $post_owner_stmt->fetch();
                    if ($post_info && (int)$post_info['user_id'] !== (int)$user['id']) {
                        $notif_title = htmlspecialchars($user['name']) . ' answered your question.';
                        $notif_body = 'Your question "' . htmlspecialchars(mb_substr($post_info['title'], 0, 100)) . '..." has a new answer.';
                        $pdo->prepare('INSERT INTO notifications (user_id, title, body, notif_type) VALUES (?, ?, ?, ?)')->execute([$post_info['user_id'], $notif_title, $notif_body, 'new_comment']);
                    }
                }
            } elseif ($type === 'delete_post') {
                $id = (int)($_POST['id'] ?? 0);
                $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
            } elseif ($type === 'delete_comment') {
                $id = (int)($_POST['id'] ?? 0);
                $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // Optional: log error message $e->getMessage()
        }
    }
    header('Location: /user/training.php');
    exit;
}


// Fetch top 5 published modules for quick access
$modules = $pdo->query('SELECT id, title, category, content_url, type FROM learning_modules WHERE published = 1 ORDER BY order_index ASC, id DESC LIMIT 5')->fetchAll();

// Fetch all posts and their authors
$posts = $pdo->query('SELECT p.*, u.name as author_name, u.points as author_points FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC')->fetchAll();

// --- N+1 Query Optimization ---
// 1. Get all post IDs
$post_ids = array_map(fn($p) => $p['id'], $posts);
$comments_by_post = [];

// 2. Fetch all comments for these posts in a single query
if (!empty($post_ids)) {
    $in_placeholders = implode(',', array_fill(0, count($post_ids), '?'));
    $comments_stmt = $pdo->prepare("SELECT c.*, u.name as author_name, u.points as author_points FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id IN ($in_placeholders) ORDER BY c.created_at ASC");
    $comments_stmt->execute($post_ids);
    $all_comments = $comments_stmt->fetchAll();

    // 3. Map comments to their post ID
    foreach ($all_comments as $comment) {
        $comments_by_post[$comment['post_id']][] = $comment;
    }
}
?>

<h2>Training & Motivation Hub</h2>

<div class="grid cols-3" style="margin-top:12px">
  <div class="card">
    <h3>Ask a Question</h3>
    <p>Ask the community for help with prospecting, objections, or anything else.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type" value="post">
        <label>Question Title</label>
        <input type="text" name="title" required>
        <label>Details</label>
        <textarea name="body" rows="3" required></textarea>
        <div style="margin-top:12px"><button class="btn">Post Question</button></div>
    </form>
  </div>
  <div class="card">
    <h3>Quick Access Modules</h3>
    <ul>
      <?php foreach($modules as $m): ?>
        <li>
          <strong><?= htmlspecialchars($m['title']) ?></strong>
          <span style="color:#6b7280">(<?= htmlspecialchars($m['category']) ?>)</span>
          <?php if ($m['content_url']): ?>
            - <a class="btn btn-outline" target="_blank" href="<?= htmlspecialchars($m['content_url']) ?>">Open</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <a class="btn" href="/user/learning.php" style="margin-top:8px">All Modules</a>
  </div>
  <div class="card motivation">
    <h3>Daily Motivation</h3>
    <p>Believe in your value. Roz 1 step aage badho.</p>
    <p style="margin-top:8px; color:#fff">Practice Count: <strong id="practice-count">0</strong></p>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px">
      <button class="btn btn-outline" data-practice-increment>+1 Practice</button>
      <a class="btn" href="/user/tasks.php?view=kanban">Open Kanban</a>
    </div>
  </div>
</div>

<div class="card" style="margin-top:12px; grid-column: span 3;">
  <h3>Community Q&A</h3>
  <?php foreach($posts as $p): ?>
  <div class="card" style="margin-top: 12px;">
    <h4><?= htmlspecialchars($p['title']) ?></h4>
    <p style="color:#6b7280">
        Asked by <?= htmlspecialchars($p['author_name']) ?> (<?= (int)$p['author_points'] ?> points)
        on <?= date('M d, Y', strtotime($p['created_at'])) ?>
    </p>
    <p><?= nl2br(htmlspecialchars($p['body'])) ?></p>

    <?php if ((int)$user['id'] === (int)$p['user_id']): ?>
    <form method="post" onsubmit="return confirm('Are you sure you want to delete this post?')" style="display: inline-block;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="type" value="delete_post">
      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <button class="btn btn-outline">Delete Post</button>
    </form>
    <?php endif; ?>

    <hr style="margin: 15px 0;">
    <h5>Answers</h5>
    <?php $comments = $comments_by_post[$p['id']] ?? []; ?>
    <?php if (empty($comments)): ?>
        <p style="color:#6b7280; font-size: 14px;">No answers yet. Be the first to help!</p>
    <?php else: ?>
        <?php foreach($comments as $c): ?>
        <div style="border-left:3px solid #e5e7eb; padding-left:12px; margin:10px 0">
          <strong><?= htmlspecialchars($c['author_name']) ?> (<?= (int)$c['author_points'] ?> points)</strong>
          <span style="color:#6b7280; font-size: 12px;"> - <?= htmlspecialchars($c['created_at']) ?></span>
          <div><?= nl2br(htmlspecialchars($c['body'])) ?></div>
          <?php if ((int)$user['id'] === (int)$c['user_id']): ?>
          <form method="post" onsubmit="return confirm('Delete comment?')" style="margin-top: 5px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="type" value="delete_comment">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-outline" style="font-size: 12px; padding: 2px 6px;">Delete</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" style="margin-top:15px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="type" value="comment">
      <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
      <textarea name="body" rows="2" required placeholder="Write an answer..."></textarea>
      <button class="btn btn-outline" style="margin-top:6px">Submit Answer</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
