<?php
// Weekly digest: team KPIs and due assigned tasks summary to admin
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Secure with CRON_TOKEN
$provided = $_GET['token'] ?? $_SERVER['CRON_TOKEN'] ?? '';
if (!hash_equals(CRON_TOKEN, (string)$provided)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$pdo = get_db();

// KPIs last 7 days
$kpi = $pdo->query('SELECT COUNT(*) AS users FROM users')->fetch();
$leads7 = $pdo->query('SELECT COUNT(*) AS c FROM leads WHERE created_at >= (NOW() - INTERVAL 7 DAY)')->fetchColumn();
$attempts7 = $pdo->query('SELECT COALESCE(SUM(attempts),0) FROM user_task_logs WHERE log_date >= (CURRENT_DATE - INTERVAL 6 DAY)')->fetchColumn();
$success7 = $pdo->query('SELECT COALESCE(SUM(successes),0) FROM user_task_logs WHERE log_date >= (CURRENT_DATE - INTERVAL 6 DAY)')->fetchColumn();

// Due assigned tasks today
$today = (new DateTime('today'))->format('Y-m-d');
$due = $pdo->query("SELECT t.title, u.name, t.task_date FROM tasks t JOIN users u ON u.id = t.assigned_to WHERE (t.task_date = CURRENT_DATE OR t.is_daily = 1) ORDER BY u.name ASC, t.title ASC")->fetchAll();

$lines = [];
$lines[] = 'Weekly Digest — Dishant Parihar Team';
$lines[] = 'Users: ' . (int)$kpi['users'];
$lines[] = 'Leads (7d): ' . (int)$leads7;
$lines[] = 'Attempts (7d): ' . (int)$attempts7;
$lines[] = 'Successes (7d): ' . (int)$success7;
$lines[] = '';
$lines[] = 'Due Assigned Tasks Today:';
if ($due) {
    foreach ($due as $d) {
        $lines[] = '- ' . $d['name'] . ': ' . $d['title'] . ' (' . $d['task_date'] . ')';
    }
} else {
    $lines[] = 'None';
}

$body = implode("\n", $lines);

if (ENABLE_EMAIL_REPORTS) {
    @mail(DEFAULT_ADMIN_EMAIL, 'Weekly Digest — Dishant Parihar Team', $body, 'From: no-reply@app.dishantparihar.com');
}

echo nl2br(htmlspecialchars($body));


