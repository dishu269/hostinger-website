<?php
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?= get_current_language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_BRAND ?> - <?= $pageTitle ?? 'Dashboard' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
    /* Enhanced Navigation Styles */
    .enhanced-nav {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px;
    }

    .nav-brand {
        font-size: 28px;
        font-weight: bold;
        color: white;
        text-decoration: none;
        padding: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nav-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 10px;
    }

    .nav-item {
        position: relative;
    }

    .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px 20px;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
    }

    .nav-link:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-2px);
    }

    .nav-link.active {
        background: rgba(255,255,255,0.2);
    }

    .nav-icon {
        font-size: 28px;
        margin-bottom: 5px;
    }

    .nav-label {
        font-size: 14px;
    }

    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 32px;
        cursor: pointer;
        padding: 10px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
        color: white;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: bold;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: bold;
        font-size: 16px;
    }

    .user-points {
        font-size: 14px;
        opacity: 0.9;
    }

    .notification-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #EF4444;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    /* Mobile Responsive */
    @media (max-width: 1024px) {
        .nav-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 400px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            flex-direction: column;
            padding: 80px 20px 20px;
            gap: 20px;
            transition: right 0.3s;
            overflow-y: auto;
            z-index: 999;
        }

        .nav-menu.active {
            right: 0;
        }

        .mobile-menu-toggle {
            display: block;
        }

        .nav-link {
            flex-direction: row;
            justify-content: flex-start;
            padding: 20px;
            font-size: 18px;
            border-radius: 10px;
            width: 100%;
        }

        .nav-icon {
            font-size: 32px;
            margin-bottom: 0;
            margin-right: 15px;
        }

        .nav-label {
            font-size: 18px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
    }

    /* Language Toggle in Nav */
    .lang-toggle-nav {
        display: flex;
        gap: 10px;
        margin-left: 20px;
    }

    .lang-toggle-nav a {
        padding: 8px 16px;
        background: rgba(255,255,255,0.2);
        color: white;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s;
    }

    .lang-toggle-nav a:hover,
    .lang-toggle-nav a.active {
        background: rgba(255,255,255,0.3);
    }

    /* Overlay for mobile menu */
    .menu-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 998;
    }

    .menu-overlay.active {
        display: block;
    }
    </style>
</head>
<body>
    <nav class="enhanced-nav">
        <div class="nav-container">
            <a href="/user/dashboard-enhanced.php" class="nav-brand">
                <span style="font-size: 36px;">🚀</span>
                <?= SITE_BRAND ?>
            </a>

            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                ☰
            </button>

            <ul class="nav-menu" id="navMenu">
                <?php if (is_logged_in()): ?>
                <div class="user-info mobile-only" style="display: none;">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                        <span class="user-points">⭐ <?= number_format($user['points'] ?? 0) ?> <?php _e('points'); ?></span>
                    </div>
                </div>

                <li class="nav-item">
                    <a href="/user/dashboard-enhanced.php" class="nav-link <?= $current_page == 'dashboard-enhanced.php' ? 'active' : '' ?>">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-label"><?php _e('dashboard'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/user/training-enhanced.php" class="nav-link <?= $current_page == 'training-enhanced.php' ? 'active' : '' ?>">
                        <span class="nav-icon">🎓</span>
                        <span class="nav-label"><?php _e('training'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/user/lead-management.php" class="nav-link <?= $current_page == 'lead-management.php' ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-label"><?php _e('leads'); ?></span>
                        <?php
                        // Check for pending follow-ups
                        $pendingCount = 0; // You would fetch this from database
                        if ($pendingCount > 0): ?>
                        <span class="notification-badge"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/user/resources.php" class="nav-link <?= $current_page == 'resources.php' ? 'active' : '' ?>">
                        <span class="nav-icon">📁</span>
                        <span class="nav-label"><?php _e('resources'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/user/achievements.php" class="nav-link <?= $current_page == 'achievements.php' ? 'active' : '' ?>">
                        <span class="nav-icon">🏆</span>
                        <span class="nav-label"><?php _e('achievements'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/user/help.php" class="nav-link <?= $current_page == 'help.php' ? 'active' : '' ?>">
                        <span class="nav-icon">❓</span>
                        <span class="nav-label"><?php _e('help'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/user/profile.php" class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-label"><?php _e('profile'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/logout.php" class="nav-link">
                        <span class="nav-icon">🚪</span>
                        <span class="nav-label"><?php _e('logout'); ?></span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="user-info desktop-only">
                <?php if (is_logged_in()): ?>
                <div class="lang-toggle-nav">
                    <a href="?lang=hi" class="<?= get_current_language() == 'hi' ? 'active' : '' ?>">हिं</a>
                    <a href="?lang=en" class="<?= get_current_language() == 'en' ? 'active' : '' ?>">EN</a>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                    <span class="user-points">⭐ <?= number_format($user['points'] ?? 0) ?> <?php _e('points'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="menu-overlay" id="menuOverlay" onclick="toggleMobileMenu()"></div>

    <main class="main-content" style="min-height: calc(100vh - 80px); background: #F3F4F6; padding: 20px 0;">
        <div style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">

<script>
function toggleMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const mobileUserInfo = document.querySelector('.user-info.mobile-only');
    
    navMenu.classList.toggle('active');
    menuOverlay.classList.toggle('active');
    
    if (mobileUserInfo) {
        mobileUserInfo.style.display = navMenu.classList.contains('active') ? 'flex' : 'none';
    }
}

// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const navMenu = document.getElementById('navMenu');
        const menuOverlay = document.getElementById('menuOverlay');
        
        if (navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            menuOverlay.classList.remove('active');
        }
    }
});

// Voice announcement for page changes
window.addEventListener('load', function() {
    const pageTitle = document.title;
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance('<?= get_current_language() == 'hi' ? 'आप अब हैं' : 'You are now on' ?> ' + pageTitle);
        utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
        utterance.rate = 0.9;
        utterance.volume = 0.3;
        // Uncomment to enable voice announcements
        // speechSynthesis.speak(utterance);
    }
});

// Show/hide desktop elements based on screen size
function handleResponsive() {
    const desktopOnly = document.querySelectorAll('.desktop-only');
    const isMobile = window.innerWidth <= 1024;
    
    desktopOnly.forEach(el => {
        el.style.display = isMobile ? 'none' : 'flex';
    });
}

window.addEventListener('resize', handleResponsive);
handleResponsive();
</script>