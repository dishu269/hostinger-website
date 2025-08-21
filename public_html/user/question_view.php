<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id === 0) {
    header('Location: /user/community.php');
    exit;
}

// Fetch the main question/post
$stmt = $pdo->prepare('SELECT p.*, u.name as author_name, u.points as author_points FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: /user/community.php');
    exit;
}

// Fetch answers/comments
$comments_stmt = $pdo->prepare('SELECT c.*, u.name as author_name, u.points as author_points FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id = ? ORDER BY c.created_at ASC');
$comments_stmt->execute([$post_id]);
$comments = $comments_stmt->fetchAll();

?>

<a href="/user/community.php" class="btn btn-outline" style="margin-bottom: 12px;">&larr; Back to All Questions</a>

<div class="card">
    <h2><?= htmlspecialchars($post['title']) ?></h2>
    <p style="color:#6b7280">
        Asked by <?= htmlspecialchars($post['author_name']) ?> (<?= (int)$post['author_points'] ?> points)
        on <?= date('M d, Y H:i', strtotime($post['created_at'])) ?>
    </p>
    <hr style="margin: 12px 0;">
    <p><?= nl2br(htmlspecialchars($post['body'])) ?></p>
</div>

<h3 style="margin-top: 20px;">Answers</h3>

<?php if (empty($comments)): ?>
    <div class="card">
        <p>No answers yet. Be the first to help!</p>
    </div>
<?php else: ?>
    <?php foreach($comments as $c): ?>
    <div class="card" style="margin-top:12px; border-left: 3px solid #006400;">
        <p style="color:#6b7280">
            Answered by <?= htmlspecialchars($c['author_name']) ?> (<?= (int)$c['author_points'] ?> points)
            on <?= date('M d, Y H:i', strtotime($c['created_at'])) ?>
        </p>
        <hr style="margin: 12px 0;">
        <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>
        <?php if ((int)$user['id'] === (int)$c['user_id']): ?>
        <form method="post" action="/user/community.php" onsubmit="return confirm('Delete comment?')" style="margin-top: 10px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="type" value="delete_comment">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-outline">Delete My Answer</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card" style="margin-top:20px;">
    <h3>Your Answer</h3>
    <form method="post" action="/user/community.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type" value="comment">
        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
        <textarea name="body" rows="4" required placeholder="Type your helpful answer here..."></textarea>
        <button class="btn" style="margin-top:12px">Submit Answer</button>
    </form>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
