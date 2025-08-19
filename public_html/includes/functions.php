<?php
// Reusable application-specific functions
declare(strict_types=1);

/**
 * Fetches the main task for the day (either dated or daily).
 * @param PDO $pdo
 * @return array|false
 */
function get_today_task(PDO $pdo)
{
    $today = (new DateTime('today'))->format('Y-m-d');
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE task_date = ? OR is_daily = 1 ORDER BY task_date DESC LIMIT 1');
    $stmt->execute([$today]);
    return $stmt->fetch();
}

/**
 * Fetches leads with follow-ups due today or earlier.
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function get_due_leads(PDO $pdo, int $userId): array
{
    $today = (new DateTime('today'))->format('Y-m-d');
    $stmt = $pdo->prepare('SELECT id, name, mobile, interest_level, follow_up_date FROM leads WHERE user_id = ? AND follow_up_date IS NOT NULL AND follow_up_date <= ? ORDER BY follow_up_date ASC LIMIT 5');
    $stmt->execute([$userId, $today]);
    return $stmt->fetchAll();
}

/**
 * Fetches a summary of user and system KPIs.
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function get_dashboard_kpis(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
      SELECT
        (SELECT COUNT(*) FROM user_tasks WHERE user_id = :user_id) as completed_tasks,
        (SELECT COUNT(*) FROM learning_modules WHERE published = 1) as total_modules,
        (SELECT COUNT(*) FROM module_progress WHERE user_id = :user_id AND progress_percent = 100) as completed_modules,
        (SELECT COUNT(*) FROM leads WHERE user_id = :user_id) as lead_count
    ");
    $stmt->execute(['user_id' => $userId]);
    $kpis = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'completed_tasks' => (int)($kpis['completed_tasks'] ?? 0),
        'total_modules' => (int)($kpis['total_modules'] ?? 0),
        'completed_modules' => (int)($kpis['completed_modules'] ?? 0),
        'lead_count' => (int)($kpis['lead_count'] ?? 0),
    ];
}

/**
 * Fetches the user's current and longest streak.
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function get_user_streak(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0];
}

/**
 * Fetches the latest notifications for a user (or global ones).
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function get_notifications(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT title, body, created_at FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 3');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Fetches the user's task funnel stats for the last 7 days.
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function get_weekly_funnel(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(attempts),0) AS attempts, COALESCE(SUM(successes),0) AS successes FROM user_task_logs WHERE user_id = ? AND log_date >= (CURRENT_DATE - INTERVAL 6 DAY)');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: ['attempts' => 0, 'successes' => 0];
}

/**
 * Fetches the latest motivational message.
 * @param PDO $pdo
 * @return array|false
 */
function get_motivation_message(PDO $pdo)
{
    return $pdo->query("SELECT title, body FROM messages WHERE active = 1 AND message_type='motivation' ORDER BY id DESC LIMIT 1")->fetch();
}
