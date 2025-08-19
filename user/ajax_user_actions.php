<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_login();

// --- User-facing AJAX Endpoint ---

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create_user_task') {
    $pdo = get_db();

    $title = sanitize_text($_POST['title'] ?? '', 200);
    $description = sanitize_text($_POST['description'] ?? '', 1000);
    $taskDate = $_POST['task_date'] ?: null;

    if (mb_strlen($title) < 3) {
        echo json_encode(['success' => false, 'error' => 'Title must be at least 3 characters.']);
        exit;
    }

    // Insert into database, assigned to the current user
    $stmt = $pdo->prepare(
        'INSERT INTO tasks (title, description, task_date, assigned_to, type, priority)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $success = $stmt->execute([
        $title,
        $description,
        $taskDate,
        $user['id'], // Assign to the current user
        'custom',   // Default type
        'medium'    // Default priority
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Personal task created successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save task.']);
    }
    exit;
}

// Default response for unknown actions
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;
