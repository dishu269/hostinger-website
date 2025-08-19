<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function sanitize_text(string $value, int $maxLen = 5000): string {
    $value = trim($value);
    $value = strip_tags($value);
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

function sanitize_email(string $email): string {
    $email = trim(mb_strtolower($email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

// Backward compatibility wrapper
function sanitize_string(string $value): string {
    return sanitize_text($value);
}

function sanitize_int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int {
    if (!is_numeric($value)) return 0;
    $n = (int)$value;
    return max($min, min($max, $n));
}

function generate_csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_KEY]) && is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function current_user(): ?array {
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    if (!empty($_SESSION['user_id'])) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, name, email, role, avatar_url FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user'] = $user;
            return $user;
        }
    }
    return null;
}

function is_admin(): bool {
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function require_login(): void {
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function register_user(string $name, string $email, string $password, string $role = 'member'): bool {
    $pdo = get_db();
    $email = sanitize_email($email);
    if ($email === '' || mb_strlen($name) < 2 || mb_strlen($password) < 6) {
        set_flash('error', 'Please provide valid name, email, and a stronger password (6+ chars).');
        return false;
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        set_flash('error', 'Email already registered.');
        return false;
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $isAdmin = ($role === 'admin');
    if ($isAdmin) {
        // Auto-verify admins and skip sending verification email
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at, email_verified_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$name, $email, $passwordHash, $role]);
    } else {
        $verificationToken = bin2hex(random_bytes(20));
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at, verification_token, verification_sent_at) VALUES (?, ?, ?, ?, NOW(), ?, NOW())');
        $stmt->execute([$name, $email, $passwordHash, $role, $verificationToken]);
        // Send verification email for non-admins
        send_app_email($email, 'Verify your email — ' . SITE_BRAND, "Hi $name,\n\nPlease verify your email by clicking the link below:\n" . APP_URL . "/verify.php?token=$verificationToken\n\nThanks,\n" . SITE_BRAND);
    }
    return true;
}

function login_user(string $email, string $password): bool {
    $pdo = get_db();
    $email = sanitize_email($email);
    if ($email === '') {
        set_flash('error', 'Invalid credentials.');
        return false;
    }

    if (!can_attempt_login($email, $_SERVER['REMOTE_ADDR'] ?? '')) {
        set_flash('error', 'Too many attempts. Try again in a few minutes.');
        return false;
    }
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, email_verified_at, avatar_url FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        if (empty($user['email_verified_at']) && $user['role'] !== 'admin') {
            // Block login until verified
            set_flash('error', 'Please verify your email to sign in. Check your inbox or resend verification.');
            record_login_attempt($email, $_SERVER['REMOTE_ADDR'] ?? '', false);
            return false;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar_url' => $user['avatar_url'],
        ];
        $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
        record_login_attempt($email, $_SERVER['REMOTE_ADDR'] ?? '', true);
        return true;
    }
    record_login_attempt($email, $_SERVER['REMOTE_ADDR'] ?? '', false);
    set_flash('error', 'Invalid credentials.');
    return false;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function guidance_tip_for_interest(string $interestLevel, string $persona): string {
    $interestLevel = strtolower($interestLevel);
    $persona = strtolower($persona);
    if ($persona === 'health' || str_contains($persona, 'health')) {
        return 'Focus on wellness benefits, product efficacy, and real testimonials.';
    }
    if ($persona === 'income' || str_contains($persona, 'income') || str_contains($persona, 'business')) {
        return 'Emphasize income plan, success stories, and mentorship support.';
    }
    switch ($interestLevel) {
        case 'hot':
            return 'Schedule a meeting within 24 hours and share the business plan deck.';
        case 'warm':
            return 'Send a short video and follow up in 2-3 days with a call.';
        default:
            return 'Stay friendly, add value with simple tips, and check in next week.';
    }
}

/**
 * Award achievements for a user based on thresholds.
 */
function award_achievements_for_user(int $userId): void {
    $pdo = get_db();
    // Leads count
    $s = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE user_id = ?');
    $s->execute([$userId]);
    $leadCount = (int)$s->fetchColumn();

    // Tasks completed
    $s = $pdo->prepare('SELECT COUNT(*) FROM user_tasks WHERE user_id = ?');
    $s->execute([$userId]);
    $taskCount = (int)$s->fetchColumn();

    // Modules 100%
    $s = $pdo->prepare('SELECT COUNT(*) FROM module_progress WHERE user_id = ? AND progress_percent = 100');
    $s->execute([$userId]);
    $moduleCount = (int)$s->fetchColumn();

    // Achievements by type
    foreach ([['leads', $leadCount], ['tasks', $taskCount], ['modules', $moduleCount]] as [$type, $value]) {
        $achStmt = $pdo->prepare('SELECT id FROM achievements WHERE threshold_type = ? AND threshold_value <= ?');
        $achStmt->execute([$type, $value]);
        foreach ($achStmt->fetchAll() as $row) {
            $pdo->prepare('INSERT IGNORE INTO user_achievements (user_id, achievement_id, awarded_at) VALUES (?,?, NOW())')
                ->execute([$userId, (int)$row['id']]);
        }
    }
}

function can_attempt_login(string $email, string $ip): bool {
    $pdo = get_db();
    // 5 attempts in 15 minutes window
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip_address = ?) AND attempted_at > (NOW() - INTERVAL 15 MINUTE) AND success = 0');
    $stmt->execute([$email, $ip]);
    $count = (int)$stmt->fetchColumn();
    return $count < 5;
}

function record_login_attempt(string $email, string $ip, bool $success): void {
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success, attempted_at) VALUES (?,?,?, NOW())');
    $stmt->execute([$email, $ip, $success ? 1 : 0]);
}

function update_streak_on_task_completion(int $userId): void {
    $pdo = get_db();
    $today = (new DateTime('today'))->format('Y-m-d');
    $row = $pdo->prepare('SELECT user_id, current_streak, longest_streak, last_completed_date FROM user_streaks WHERE user_id = ?');
    $row->execute([$userId]);
    $streak = $row->fetch();
    if (!$streak) {
        $pdo->prepare('INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_completed_date) VALUES (?,?,?,?)')
            ->execute([$userId, 1, 1, $today]);
        return;
    }
    $last = $streak['last_completed_date'];
    $yesterday = (new DateTime('yesterday'))->format('Y-m-d');
    if ($last === $today) return; // already counted
    $current = (int)$streak['current_streak'];
    if ($last === $yesterday) {
        $current += 1;
    } else {
        $current = 1;
    }
    $longest = max($current, (int)$streak['longest_streak']);
    $pdo->prepare('UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_completed_date = ? WHERE user_id = ?')
        ->execute([$current, $longest, $today, $userId]);
}

/**
 * Minimal mail helper using PHP mail(). Configure SENDER_EMAIL in config.
 */
function send_app_email(string $to, string $subject, string $body): void {
    $headers  = 'From: ' . SENDER_EMAIL . "\r\n";
    $headers .= 'Reply-To: ' . SENDER_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $ok = @mail($to, $subject, $body, $headers, '-f' . SENDER_EMAIL);
    if (!$ok) {
        error_log('send_app_email failed: to=' . $to . ' subject=' . $subject);
    }
}

function send_password_reset_email(string $email): void {
    $pdo = get_db();
    $token = bin2hex(random_bytes(20));
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)')->execute([$email, $token, $expires]);
    $link = APP_URL . '/reset.php?token=' . $token;
    send_app_email($email, 'Reset your password — ' . SITE_BRAND, "Reset link (valid 1 hour):\n$link\n\nIf you did not request this, ignore.");
}

function verify_email_with_token(string $token): bool {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE verification_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) return false;
    $pdo->prepare('UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?')->execute([(int)$user['id']]);
    return true;
}

function resend_verification_email(string $email): void {
    $pdo = get_db();
    $email = sanitize_email($email);
    if ($email === '') { return; }
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? AND email_verified_at IS NULL');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u) { return; }
    $token = bin2hex(random_bytes(20));
    $pdo->prepare('UPDATE users SET verification_token = ?, verification_sent_at = NOW() WHERE id = ?')
        ->execute([$token, (int)$u['id']]);
    $link = APP_URL . '/verify.php?token=' . $token;
    send_app_email($email, 'Verify your email — ' . SITE_BRAND, "Hi {$u['name']},\n\nPlease verify your email by clicking the link below:\n$link\n\nThanks,\n" . SITE_BRAND);
}

?>


