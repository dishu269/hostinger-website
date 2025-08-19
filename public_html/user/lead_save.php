<?php
// JSON endpoint for offline lead sync
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json');

// Enforce JSON content type
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
  http_response_code(415);
  echo json_encode(['ok' => false, 'error' => 'Unsupported Media Type']);
  exit;
}

// Accept JSON with fields as in form
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($token)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'CSRF']);
  exit;
}

if (!is_array($data)) { $data = []; }
$pdo = get_db();

// Basic validation
$name = sanitize_text($data['name'] ?? '', 200);
$mobile = preg_replace('/[^0-9+]/', '', (string)($data['mobile'] ?? ''));
if ($name === '' || $mobile === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Name and mobile are required']);
  exit;
}

// Duplicate guard (same user + mobile)
$dup = $pdo->prepare('SELECT id FROM leads WHERE user_id = ? AND mobile = ? LIMIT 1');
$dup->execute([(int)$_SESSION['user']['id'], $mobile]);
if ($dup->fetch()) {
  echo json_encode(['ok' => true, 'duplicate' => true]);
  exit;
}

$stmt = $pdo->prepare('INSERT INTO leads (user_id, name, mobile, city, work, age, meeting_date, interest_level, notes, follow_up_date, created_at, updated_at, status) VALUES (?,?,?,?,?,?,?,?,?,?, NOW(), NOW(), ?)');
$meetingDate = $data['meeting_date'] ?: null;
$followDate = $data['follow_up_date'] ?: null;
$interest = in_array(($data['interest_level'] ?? 'Warm'), ['Hot','Warm','Cold'], true) ? $data['interest_level'] : 'Warm';
$status = 'open';
$stmt->execute([
  (int)$_SESSION['user']['id'], $name, $mobile, sanitize_text($data['city'] ?? '', 120), sanitize_text($data['work'] ?? '', 120), sanitize_int($data['age'] ?? 0, 10, 100), $meetingDate, $interest, sanitize_text($data['notes'] ?? '', 1000), $followDate, $status
]);

award_achievements_for_user((int)$_SESSION['user']['id']);

echo json_encode(['ok' => true]);


