<?php
// Global configuration for Asclepius Wellness Team App

declare(strict_types=1);

// Site constants
const SITE_NAME = 'Dishant Parihar — Digital HQ';
const SITE_BRAND = 'Dishant Parihar Team';

// --- IMPORTANT SECURITY NOTICE ---
// Do not hardcode database credentials. Use environment variables.
// In your hosting panel (e.g., Hostinger hPanel), set an environment variable
// named DB_PASS with your actual database password.
const DB_HOST = getenv('DB_HOST') ?: 'localhost';
const DB_NAME = getenv('DB_NAME') ?: 'u782093275_app';
const DB_USER = getenv('DB_USER') ?: 'u782093275_app';
const DB_PASS = getenv('DB_PASS') ?: ''; // Fallback to empty string if not set
const DB_CHARSET = 'utf8mb4';

// Session settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!isset($_SESSION)) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// App paths
define('BASE_URL', rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'), '/'));

// If your web server document root is the repository root, set to ''
// If you serve from the `public/` directory, set to '/public'
if (!defined('PUBLIC_BASE')) {
    define('PUBLIC_BASE', '/public');
}

// CSRF token name
const CSRF_TOKEN_KEY = 'csrf_token';

// Security: Content Security Policy domain allowances (matched in .htaccess too)
const CSP_FONT_SRC = "'self' https://fonts.gstatic.com";
const CSP_STYLE_SRC = "'self' 'unsafe-inline' https://fonts.googleapis.com";
const CSP_SCRIPT_SRC = "'self'";
const CSP_IMG_SRC = "'self' data:";
const CSP_CONNECT_SRC = "'self'";

// Feature flags
const ENABLE_SERVICE_WORKER = true;
const ENABLE_VOICE_INPUT = true;

// Cron security and email notifications
// --- IMPORTANT SECURITY NOTICE ---
// Set a strong, secret CRON_TOKEN as an environment variable in your hosting panel.
const CRON_TOKEN = getenv('CRON_TOKEN') ?: 'your_default_fallback_token';
const ENABLE_EMAIL_REPORTS = false; // Set true to email daily admin reports

// App URL and email sender
if (!defined('APP_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    define('APP_URL', $scheme . '://' . $host);
}
const SENDER_EMAIL = 'no-reply@app.dishantparihar.com';

// Admin email (first admin user)
const DEFAULT_ADMIN_EMAIL = 'dishantparihar00@gmail.com';

?>


