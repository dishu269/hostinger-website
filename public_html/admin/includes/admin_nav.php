<?php
// Enhanced admin navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="admin-nav-enhanced">
    <div class="nav-section">
        <h3><i class="fas fa-tachometer-alt"></i> Analytics</h3>
        <a href="/admin/dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Command Center
        </a>
        <a href="/admin/reports.php" class="nav-item <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> Reports & Analytics
        </a>
    </div>

    <div class="nav-section">
        <h3><i class="fas fa-users"></i> Team Management</h3>
        <a href="/admin/teams.php" class="nav-item <?= $current_page === 'teams.php' ? 'active' : '' ?>">
            <i class="fas fa-sitemap"></i> Teams & Hierarchy
        </a>
        <a href="/admin/users.php" class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-user-friends"></i> Team Members
        </a>
        <a href="/admin/user_profile.php" class="nav-item">
            <i class="fas fa-id-card"></i> User Profiles
        </a>
    </div>

    <div class="nav-section">
        <h3><i class="fas fa-tasks"></i> Operations</h3>
        <a href="/admin/task_management.php" class="nav-item <?= $current_page === 'task_management.php' ? 'active' : '' ?>">
            <i class="fas fa-project-diagram"></i> Task Management
        </a>
        <a href="/admin/workflows.php" class="nav-item <?= $current_page === 'workflows.php' ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> Automation
        </a>
        <a href="/admin/goals.php" class="nav-item <?= $current_page === 'goals.php' ? 'active' : '' ?>">
            <i class="fas fa-bullseye"></i> Goals & OKRs
        </a>
    </div>

    <div class="nav-section">
        <h3><i class="fas fa-dollar-sign"></i> Finance</h3>
        <a href="/admin/finance.php" class="nav-item <?= $current_page === 'finance.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Financial Overview
        </a>
        <a href="/admin/commissions.php" class="nav-item <?= $current_page === 'commissions.php' ? 'active' : '' ?>">
            <i class="fas fa-percentage"></i> Commissions
        </a>
        <a href="/admin/inventory.php" class="nav-item <?= $current_page === 'inventory.php' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i> Inventory
        </a>
    </div>

    <div class="nav-section">
        <h3><i class="fas fa-graduation-cap"></i> Training</h3>
        <a href="/admin/modules.php" class="nav-item <?= $current_page === 'modules.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Learning Modules
        </a>
        <a href="/admin/training_paths.php" class="nav-item <?= $current_page === 'training_paths.php' ? 'active' : '' ?>">
            <i class="fas fa-route"></i> Training Paths
        </a>
        <a href="/admin/certifications.php" class="nav-item <?= $current_page === 'certifications.php' ? 'active' : '' ?>">
            <i class="fas fa-certificate"></i> Certifications
        </a>
    </div>

    <div class="nav-section">
        <h3><i class="fas fa-bullhorn"></i> Communication</h3>
        <a href="/admin/messages.php" class="nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Broadcast Messages
        </a>
        <a href="/admin/chat.php" class="nav-item <?= $current_page === 'chat.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> Team Chat
        </a>
        <a href="/admin/whatsapp_templates.php" class="nav-item <?= $current_page === 'whatsapp_templates.php' ? 'active' : '' ?>">
            <i class="fab fa-whatsapp"></i> WhatsApp Templates
        </a>
        <a href="/admin/events.php" class="nav-item <?= $current_page === 'events.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> Events
        </a>
    </div>

    <div class="nav-section">
        <h3><i class="fas fa-cogs"></i> System</h3>
        <a href="/admin/integrations.php" class="nav-item <?= $current_page === 'integrations.php' ? 'active' : '' ?>">
            <i class="fas fa-plug"></i> Integrations
        </a>
        <a href="/admin/activity_logs.php" class="nav-item <?= $current_page === 'activity_logs.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Activity Logs
        </a>
        <a href="/admin/settings.php" class="nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
    </div>
</nav>

<style>
.admin-nav-enhanced {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

.nav-section {
    margin-bottom: 25px;
}

.nav-section h3 {
    font-size: 0.85rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-section h3 i {
    font-size: 0.9rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    text-decoration: none;
    color: #495057;
    border-radius: 8px;
    transition: all 0.2s;
    margin-bottom: 5px;
}

.nav-item:hover {
    background: #f8f9fa;
    color: #667eea;
    transform: translateX(5px);
}

.nav-item.active {
    background: #667eea;
    color: white;
}

.nav-item i {
    width: 20px;
    text-align: center;
}

/* Scrollbar styling */
.admin-nav-enhanced::-webkit-scrollbar {
    width: 6px;
}

.admin-nav-enhanced::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.admin-nav-enhanced::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 3px;
}

.admin-nav-enhanced::-webkit-scrollbar-thumb:hover {
    background: #5a67d8;
}
</style>