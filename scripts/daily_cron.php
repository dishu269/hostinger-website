<?php
// Daily cron script: create notifications for today's task and due follow-ups,
// and optionally email admin a summary. Secure with CRON_TOKEN.

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Simple token check to avoid public execution
$provided = $_GET['token'] ?? $_SERVER['CRON_TOKEN'] ?? '';
if (!hash_equals(CRON_TOKEN, (string)$provided)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$pdo = get_db();
$today = (new DateTime('today'))->format('Y-m-d');

// 1) Determine today's task (daily or dated)
$stmt = $pdo->prepare('SELECT title, description FROM tasks WHERE task_date = ? OR is_daily = 1 ORDER BY task_date DESC LIMIT 1');
$stmt->execute([$today]);
$todayTask = $stmt->fetch();

// 2) Create notification for all users with today's task
if ($todayTask) {
    $title = 'Today\'s Task';
    $body = $todayTask['title'];
    // Broadcast (user_id NULL)
    $pdo->prepare('INSERT INTO notifications (user_id, title, body, notif_type) VALUES (NULL, ?, ?, ?)')
        ->execute([$title, $body, 'task']);
}

// 3) Per-user due follow-ups notification
$users = $pdo->query('SELECT id, name, email FROM users')->fetchAll();
foreach ($users as $u) {
    $dueStmt = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE user_id = ? AND follow_up_date IS NOT NULL AND follow_up_date <= ?');
    $dueStmt->execute([(int)$u['id'], $today]);
    $dueCount = (int)$dueStmt->fetchColumn();
    if ($dueCount > 0) {
        $title = 'Follow-ups Due';
        $body = "You have $dueCount follow-up(s) due today.";
        $pdo->prepare('INSERT INTO notifications (user_id, title, body, notif_type) VALUES (?,?,?,?)')
            ->execute([(int)$u['id'], $title, $body, 'followup']);
    }
}

// 4) Optional email summary to admin
if (ENABLE_EMAIL_REPORTS) {
    // Basic PHP mail() fallback; on Hostinger ensure sendmail is enabled.
    $summary = $pdo->query('SELECT COUNT(*) AS users, (SELECT COUNT(*) FROM leads WHERE follow_up_date = CURDATE()) AS followups_today FROM users')->fetch();
    $subject = 'Daily Summary — Asclepius Wellness';
    $message = "Users: {$summary['users']}, Follow-ups Today: {$summary['followups_today']}";
    @mail(DEFAULT_ADMIN_EMAIL, $subject, $message, 'From: no-reply@yourdomain');
}

echo 'OK';


