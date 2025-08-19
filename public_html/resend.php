<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? null)) {
  $email = sanitize_email($_POST['email'] ?? ($_POST['resend_email'] ?? ''));
  if ($email !== '') {
    // Throttle resends: 5 minutes
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, email_verified_at, verification_sent_at FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && empty($u['email_verified_at'])) {
      $recent = $u['verification_sent_at'] && (time() - strtotime($u['verification_sent_at']) < 300);
      if ($recent) {
        set_flash('error', 'Verification was sent recently. Please check inbox/spam or try later.');
      } else {
        resend_verification_email($email);
        set_flash('success', 'Verification link sent. Please check your inbox/spam.');
      }
    } else {
      set_flash('success', 'If the email is unverified, a verification link has been sent.');
    }
  } else {
    set_flash('error', 'Please enter a valid email.');
  }
}

header('Location: /login.php');
exit;


