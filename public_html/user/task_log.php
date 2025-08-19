<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_member();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
  http_response_code(400);
  echo 'Bad Request';
  exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
$action = $_POST['action'] ?? '';
$today = (new DateTime('today'))->format('Y-m-d');

// Ensure task exists and assigned (or unassigned)
$row = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND (assigned_to IS NULL OR assigned_to = ?)');
$row->execute([$taskId, $user['id']]);
if (!$row->fetch()) {
  http_response_code(404);
  echo 'Task not found';
  exit;
}

// Upsert daily log
$pdo->prepare('INSERT INTO user_task_logs (user_id, task_id, log_date, attempts, successes) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE attempts = VALUES(attempts), successes = VALUES(successes)')
    ->execute([$user['id'], $taskId, $today, 0, 0]);

if ($action === 'attempt') {
  $pdo->prepare('UPDATE user_task_logs SET attempts = attempts + 1 WHERE user_id = ? AND task_id = ? AND log_date = ?')
      ->execute([$user['id'], $taskId, $today]);
} elseif ($action === 'success') {
  $pdo->prepare('UPDATE user_task_logs SET successes = successes + 1 WHERE user_id = ? AND task_id = ? AND log_date = ?')
      ->execute([$user['id'], $taskId, $today]);
} else {
  http_response_code(400);
  echo 'Invalid action';
  exit;
}

award_achievements_for_user((int)$user['id']);

$next = $_POST['next'] ?? '/user/tasks.php';
header('Location: ' . $next);
exit;


