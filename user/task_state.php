<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
  http_response_code(400);
  echo 'Bad Request';
  exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
$state = $_POST['state'] ?? 'todo';
if (!in_array($state, ['todo','doing','done'], true)) { $state = 'todo'; }

// Ensure task exists and is visible to the user
$row = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND (assigned_to IS NULL OR assigned_to = ?)');
$row->execute([$taskId, $user['id']]);
if (!$row->fetch()) {
  http_response_code(404);
  echo 'Task not found';
  exit;
}

$pdo->prepare('INSERT INTO user_task_state (user_id, task_id, state) VALUES (?,?,?) ON DUPLICATE KEY UPDATE state = VALUES(state)')
    ->execute([$user['id'], $taskId, $state]);

$next = $_SERVER['HTTP_REFERER'] ?? '/user/tasks.php?view=kanban';
header('Location: ' . $next);
exit;


