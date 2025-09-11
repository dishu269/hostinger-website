<?php
// Enhanced Routes Configuration
// This file maps old routes to new enhanced versions

$enhancedRoutes = [
    '/user/dashboard.php' => '/user/dashboard-enhanced.php',
    '/user/training.php' => '/user/training-enhanced.php',
    '/user/achievements.php' => '/user/achievements-enhanced.php',
    '/user/resources.php' => '/user/resources-enhanced.php',
    '/includes/header.php' => '/includes/header-enhanced.php',
    '/includes/footer.php' => '/includes/footer-enhanced.php'
];

// Function to get enhanced route
function getEnhancedRoute($currentRoute) {
    global $enhancedRoutes;
    return $enhancedRoutes[$currentRoute] ?? $currentRoute;
}

// Auto-redirect to enhanced versions
function redirectToEnhanced() {
    $currentPath = $_SERVER['PHP_SELF'];
    $enhancedPath = getEnhancedRoute($currentPath);
    
    if ($enhancedPath !== $currentPath) {
        $queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        header("Location: {$enhancedPath}{$queryString}");
        exit;
    }
}

// Enable enhanced UI globally
define('USE_ENHANCED_UI', true);