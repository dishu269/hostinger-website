<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

// Create post/comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $type = $_POST['type'] ?? '';
    $pdo->beginTransaction();
    try {
        if ($type === 'post') {
            $title = sanitize_string($_POST['title'] ?? '');
            $body = sanitize_string($_POST['body'] ?? '');
            if (!empty($title) && !empty($body)) {
                $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$user['id'], $title, $body]);

                // Notify all other users about the new question
                $users_to_notify = $pdo->prepare('SELECT id FROM users WHERE id != ?');
                $users_to_notify->execute([$user['id']]);
                $notif_stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, body, notif_type) VALUES (?, ?, ?, ?)');
                $notif_title = 'New Question: ' . htmlspecialchars(mb_substr($title, 0, 100));
                $notif_body = htmlspecialchars($user['name']) . ' asked a new question in the community forum.';
                foreach ($users_to_notify->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    $notif_stmt->execute([$uid, $notif_title, $notif_body, 'new_post']);
                }
            }
        } elseif ($type === 'comment') {
            $postId = (int)($_POST['post_id'] ?? 0);
            $body = sanitize_string($_POST['body'] ?? '');
            if (!empty($body) && $postId > 0) {
                // Insert the comment
                $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$postId, $user['id'], $body]);

                // Award points for answering
                $stmt = $pdo->prepare('UPDATE users SET points = points + 10 WHERE id = ?');
                $stmt->execute([$user['id']]);

                // Notify original poster
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
        // In a real app, log this error: error_log($e->getMessage());
    }
    header('Location: /user/community.php');
    exit;
}

$posts = $pdo->query('SELECT p.id, p.title, p.created_at, u.name as author_name, (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as answer_count FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC')->fetchAll();

?>

<h2>Community Q&A</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Ask a Question</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="type" value="post">
      <label>Question Title</label>
      <input type="text" name="title" required placeholder="e.g., How do I handle price objections?">
      <label>Details</label>
      <textarea name="body" rows="3" required placeholder="Provide some context for your question..."></textarea>
      <div style="margin-top:12px"><button class="btn">Ask Question</button></div>
    </form>
  </div>
  <div class="card">
    <h3>Leaderboard</h3>
    <p>Users with the most points from helpful answers will be shown here.</p>
    <!-- Leaderboard logic can be added here later -->
  </div>
</div>

<h3>All Questions</h3>
<div class="card" style="margin-top:12px">
  <?php if (empty($posts)): ?>
    <p>No questions have been asked yet. Be the first!</p>
  <?php else: ?>
    <ul class="item-list">
      <?php foreach($posts as $p): ?>
      <li>
        <a href="/user/question_view.php?id=<?= (int)$p['id'] ?>">
          <strong><?= htmlspecialchars($p['title']) ?></strong>
        </a>
        <br>
        <small>
          Asked by <?= htmlspecialchars($p['author_name']) ?> on <?= date('M d, Y', strtotime($p['created_at'])) ?>
          &bull;
          <span style="color: #0056b3;"><?= (int)$p['answer_count'] ?> Answers</span>
        </small>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


