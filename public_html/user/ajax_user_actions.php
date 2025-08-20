<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_member();

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

if ($action === 'get_ai_tip') {
    $userInput = sanitize_text($_POST['prompt'] ?? '', 2000);
    if (mb_strlen($userInput) < 5) {
        echo json_encode(['success' => false, 'error' => 'Input is too short.']);
        exit;
    }

    if (OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY_HERE') {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'AI service is not configured.']);
        exit;
    }

    $system_prompt = "You are a helpful assistant for a network marketing professional. The user will provide a statement from a prospect. Your task is to give a concise, actionable tip (in Hinglish) on how to respond. Focus on building relationships, addressing concerns, and guiding the conversation forward. Keep the tip to 1-2 short sentences. Example: Prospect says 'Price is too high'. You say: 'Value pe focus karein. Quality aur long-term benefits ke baare mein batayein.'";

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $userInput]
        ],
        'temperature' => 0.7,
        'max_tokens' => 60,
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . OPENAI_API_KEY . "\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);

    $response_data = json_decode($result, true);
    $http_code = (int)explode(' ', $http_response_header[0])[1];

    if ($http_code >= 400 || isset($response_data['error'])) {
        http_response_code($http_code > 0 ? $http_code : 500);
        $error_message = $response_data['error']['message'] ?? 'Unknown error from AI service.';
        echo json_encode(['success' => false, 'error' => $error_message]);
        exit;
    }

    $tip = $response_data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a tip.';
    echo json_encode(['success' => true, 'tip' => trim($tip)]);
    exit;
}


// Default response for unknown actions
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;
