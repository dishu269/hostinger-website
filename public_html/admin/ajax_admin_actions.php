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
} elseif ($action === 'edit_user') {
    $pdo = get_db();

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Name and Email are required.']);
        exit;
    }

    if ($id === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid User ID.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, city = ?, phone = ? WHERE id = ?');
    $success = $stmt->execute([$name, $email, $city, $phone, $id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update user.']);
    }
    exit;
} elseif ($action === 'edit_module') {
    $pdo = get_db();
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize_string($_POST['title'] ?? '');
    $category = sanitize_string($_POST['category'] ?? '');
    $description = sanitize_string($_POST['description'] ?? '');
    $contentUrl = sanitize_string($_POST['content_url'] ?? '');
    $type = sanitize_string($_POST['type'] ?? 'video');
    $published = isset($_POST['published']) ? 1 : 0;
    $orderIndex = (int)($_POST['order_index'] ?? 0);

    $stmt = $pdo->prepare('UPDATE learning_modules SET title=?, category=?, description=?, content_url=?, type=?, order_index=?, published=? WHERE id = ?');
    $success = $stmt->execute([$title, $category, $description, $contentUrl, $type, $orderIndex, $published, $id]);

    if ($success) { echo json_encode(['success' => true, 'message' => 'Module updated.']); }
    else { echo json_encode(['success' => false, 'error' => 'Failed to update module.']); }
    exit;

} elseif ($action === 'edit_resource') {
    $pdo = get_db();
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize_string($_POST['title'] ?? '');
    $description = sanitize_string($_POST['description'] ?? '');
    $fileUrl = sanitize_string($_POST['file_url'] ?? '');
    $type = sanitize_string($_POST['type'] ?? 'pdf');
    $published = isset($_POST['published']) ? 1 : 0;

    $stmt = $pdo->prepare('UPDATE resources SET title=?, description=?, file_url=?, type=?, published=? WHERE id=?');
    $success = $stmt->execute([$title, $description, $fileUrl, $type, $published, $id]);

    if ($success) { echo json_encode(['success' => true, 'message' => 'Resource updated.']); }
    else { echo json_encode(['success' => false, 'error' => 'Failed to update resource.']); }
    exit;

} elseif ($action === 'edit_event') {
    $pdo = get_db();
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize_string($_POST['title'] ?? '');
    $description = sanitize_string($_POST['description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $location = sanitize_string($_POST['location'] ?? '');

    $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, location=? WHERE id=?');
    $success = $stmt->execute([$title, $description, $eventDate, $location, $id]);

    if ($success) { echo json_encode(['success' => true, 'message' => 'Event updated.']); }
    else { echo json_encode(['success' => false, 'error' => 'Failed to update event.']); }
    exit;

} elseif ($action === 'edit_message') {
    $pdo = get_db();
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize_string($_POST['title'] ?? '');
    $body = sanitize_string($_POST['body'] ?? '');
    $type = sanitize_string($_POST['message_type'] ?? 'motivation');
    $active = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare('UPDATE messages SET title=?, body=?, message_type=?, active=? WHERE id=?');
    $success = $stmt->execute([$title, $body, $type, $active, $id]);

    if ($success) { echo json_encode(['success' => true, 'message' => 'Message updated.']); }
    else { echo json_encode(['success' => false, 'error' => 'Failed to update message.']); }
    exit;

} elseif ($action === 'edit_achievement') {
    $pdo = get_db();
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize_string($_POST['name'] ?? '');
    $description = sanitize_string($_POST['description'] ?? '');
    $icon = sanitize_string($_POST['icon'] ?? '🏆');
    $thresholdType = sanitize_string($_POST['threshold_type'] ?? 'leads');
    $thresholdValue = (int)($_POST['threshold_value'] ?? 0);

    $stmt = $pdo->prepare('UPDATE achievements SET name=?, description=?, icon=?, threshold_type=?, threshold_value=? WHERE id=?');
    $success = $stmt->execute([$name, $description, $icon, $thresholdType, $thresholdValue, $id]);

    if ($success) { echo json_encode(['success' => true, 'message' => 'Achievement updated.']); }
    else { echo json_encode(['success' => false, 'error' => 'Failed to update achievement.']); }
    exit;
}

// Default response for any other action
http_response_code(400); // Bad Request
echo json_encode(['success' => false, 'error' => 'Unknown or unsupported action.']);
exit;
