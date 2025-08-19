<?php
require_once __DIR__ . '/includes/header.php';

$ok = false;
$token = $_GET['token'] ?? '';
if ($token !== '') {
  $ok = verify_email_with_token($token);
}

if ($ok) {
  set_flash('success', 'Email verified successfully. You can now sign in.');
} else {
  set_flash('error', 'Invalid or expired verification link.');
}

header('Location: /login.php');
exit;


