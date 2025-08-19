<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php'; // This includes auth.php and db.php
require_admin();

// --- AJAX Endpoint ---

// Set the response type to JSON
header('Content-Type: application/json');

// Basic request validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create_task') {
    $pdo = get_db();

    // Sanitize all inputs from the form
    $title = sanitize_text($_POST['title'] ?? '', 200);
    $description = sanitize_text($_POST['description'] ?? '', 1000);
    $taskDate = $_POST['task_date'] ?: null;
    $isDaily = isset($_POST['is_daily']) ? 1 : 0;
    $type = in_array(($_POST['type'] ?? 'custom'), ['prospecting','followup','training','event','custom'], true) ? $_POST['type'] : 'custom';
    $target = sanitize_int($_POST['target_count'] ?? 0, 0, 100000);
    $impact = sanitize_int($_POST['impact_score'] ?? 1, 1, 5);
    $effort = sanitize_int($_POST['effort_score'] ?? 1, 1, 5);
    $priority = in_array(($_POST['priority'] ?? 'medium'), ['low','medium','high'], true) ? $_POST['priority'] : 'medium';
    $dueTime = $_POST['due_time'] ?: null;
    $repeatRule = in_array(($_POST['repeat_rule'] ?? 'none'), ['none','daily','weekly'], true) ? $_POST['repeat_rule'] : 'none';
    $scriptA = sanitize_text($_POST['script_a'] ?? '', 5000);
    $scriptB = sanitize_text($_POST['script_b'] ?? '', 5000);
    $isTemplate = isset($_POST['is_template']) ? 1 : 0;
    $templateName = $isTemplate ? sanitize_text($_POST['template_name'] ?? '', 120) : null;
    $assignedTo = ($_POST['assigned_to'] ?? '') !== '' ? (int)$_POST['assigned_to'] : null;

    if (mb_strlen($title) < 3) {
        echo json_encode(['success' => false, 'error' => 'Title must be at least 3 characters.']);
        exit;
    }

    // Optional duplicate guard for dated tasks
    if ($taskDate) {
        $dup = $pdo->prepare('SELECT id FROM tasks WHERE task_date = ? AND title = ? LIMIT 1');
        $dup->execute([$taskDate, $title]);
        if ($dup->fetch()) {
            echo json_encode(['success' => false, 'error' => 'A task with the same title already exists for this date.']);
            exit;
        }
    }

    // Insert into database
    $stmt = $pdo->prepare('INSERT INTO tasks (title, description, task_date, is_daily, type, target_count, impact_score, effort_score, priority, due_time, repeat_rule, script_a, script_b, is_template, template_name, assigned_to) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $success = $stmt->execute([$title, $description, $taskDate, $isDaily, $type, $target, $impact, $effort, $priority, $dueTime, $repeatRule, $scriptA, $scriptB, $isTemplate, $templateName, $assignedTo]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Task created successfully.']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Failed to save task to the database.']);
    }
    exit;
}

// Default response for unknown actions
http_response_code(400); // Bad Request
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;
