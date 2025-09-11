<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
?>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="admin-menu-page">
    <div class="menu-header">
        <h1>Admin Control Panel</h1>
        <p class="subtitle">Comprehensive Business Management System</p>
    </div>

    <div class="menu-grid">
        <!-- Analytics & Reporting -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-chart-line"></i>
                <h2>Analytics & Reporting</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/dashboard.php" class="menu-item featured">
                    <i class="fas fa-tachometer-alt"></i>
                    <div class="item-content">
                        <h3>Business Command Center</h3>
                        <p>Real-time KPIs, conversion funnels, predictive analytics, and team performance metrics</p>
                    </div>
                    <span class="badge new">Enhanced</span>
                </a>
                <a href="/admin/reports.php" class="menu-item">
                    <i class="fas fa-file-chart-line"></i>
                    <div class="item-content">
                        <h3>Advanced Reports</h3>
                        <p>Custom report builder, saved reports, and data export capabilities</p>
                    </div>
                </a>
                <a href="/admin/analytics.php" class="menu-item">
                    <i class="fas fa-chart-pie"></i>
                    <div class="item-content">
                        <h3>Business Intelligence</h3>
                        <p>Deep insights, trends analysis, and forecasting</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Team Management -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-users"></i>
                <h2>Team Management</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/teams.php" class="menu-item featured">
                    <i class="fas fa-sitemap"></i>
                    <div class="item-content">
                        <h3>Teams & Hierarchy</h3>
                        <p>Organizational structure, team performance tracking, and hierarchy management</p>
                    </div>
                    <span class="badge new">New</span>
                </a>
                <a href="/admin/users.php" class="menu-item">
                    <i class="fas fa-user-friends"></i>
                    <div class="item-content">
                        <h3>Team Members</h3>
                        <p>User management, roles, permissions, and individual performance tracking</p>
                    </div>
                </a>
                <a href="/admin/performance.php" class="menu-item">
                    <i class="fas fa-award"></i>
                    <div class="item-content">
                        <h3>Performance Reviews</h3>
                        <p>Individual scorecards, team metrics, and performance analytics</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Task & Project Management -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-tasks"></i>
                <h2>Task & Project Management</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/task_management.php" class="menu-item featured">
                    <i class="fas fa-project-diagram"></i>
                    <div class="item-content">
                        <h3>Advanced Task Management</h3>
                        <p>Kanban boards, Gantt charts, dependencies, and automated workflows</p>
                    </div>
                    <span class="badge new">Enhanced</span>
                </a>
                <a href="/admin/workflows.php" class="menu-item">
                    <i class="fas fa-robot"></i>
                    <div class="item-content">
                        <h3>Workflow Automation</h3>
                        <p>Automated processes, triggers, and business rule engine</p>
                    </div>
                </a>
                <a href="/admin/goals.php" class="menu-item">
                    <i class="fas fa-bullseye"></i>
                    <div class="item-content">
                        <h3>Goals & OKRs</h3>
                        <p>Cascading objectives, key results tracking, and goal alignment</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Financial Management -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-dollar-sign"></i>
                <h2>Financial Management</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/finance.php" class="menu-item featured">
                    <i class="fas fa-chart-bar"></i>
                    <div class="item-content">
                        <h3>Financial Dashboard</h3>
                        <p>Revenue tracking, expense management, profit analysis, and forecasting</p>
                    </div>
                    <span class="badge new">New</span>
                </a>
                <a href="/admin/commissions.php" class="menu-item">
                    <i class="fas fa-percentage"></i>
                    <div class="item-content">
                        <h3>Commission Management</h3>
                        <p>Commission structure, calculations, and payout tracking</p>
                    </div>
                </a>
                <a href="/admin/inventory.php" class="menu-item">
                    <i class="fas fa-boxes"></i>
                    <div class="item-content">
                        <h3>Inventory Control</h3>
                        <p>Stock management, order tracking, and inventory analytics</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Training & Development -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-graduation-cap"></i>
                <h2>Training & Development</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/modules.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <div class="item-content">
                        <h3>Learning Modules</h3>
                        <p>Course management, content library, and progress tracking</p>
                    </div>
                </a>
                <a href="/admin/training_paths.php" class="menu-item">
                    <i class="fas fa-route"></i>
                    <div class="item-content">
                        <h3>Training Paths</h3>
                        <p>Structured learning paths, prerequisites, and certifications</p>
                    </div>
                </a>
                <a href="/admin/achievements.php" class="menu-item">
                    <i class="fas fa-trophy"></i>
                    <div class="item-content">
                        <h3>Achievements & Badges</h3>
                        <p>Gamification, recognition system, and milestone tracking</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Communication Hub -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-comments"></i>
                <h2>Communication Hub</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/messages.php" class="menu-item">
                    <i class="fas fa-bullhorn"></i>
                    <div class="item-content">
                        <h3>Broadcast Center</h3>
                        <p>Mass messaging, announcements, and targeted communications</p>
                    </div>
                </a>
                <a href="/admin/chat.php" class="menu-item">
                    <i class="fas fa-comment-dots"></i>
                    <div class="item-content">
                        <h3>Team Chat</h3>
                        <p>Real-time messaging, group chats, and file sharing</p>
                    </div>
                </a>
                <a href="/admin/events.php" class="menu-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="item-content">
                        <h3>Event Management</h3>
                        <p>Training sessions, meetings, and event scheduling</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- CRM & Sales Tools -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-handshake"></i>
                <h2>CRM & Sales Tools</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/leads.php" class="menu-item">
                    <i class="fas fa-user-plus"></i>
                    <div class="item-content">
                        <h3>Lead Management</h3>
                        <p>Lead tracking, pipeline management, and conversion analytics</p>
                    </div>
                </a>
                <a href="/admin/whatsapp_templates.php" class="menu-item">
                    <i class="fab fa-whatsapp"></i>
                    <div class="item-content">
                        <h3>WhatsApp Templates</h3>
                        <p>Pre-built templates, persona-based messaging, and quick responses</p>
                    </div>
                </a>
                <a href="/admin/resources.php" class="menu-item">
                    <i class="fas fa-folder-open"></i>
                    <div class="item-content">
                        <h3>Sales Resources</h3>
                        <p>Brochures, scripts, presentations, and marketing materials</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- System & Integrations -->
        <div class="menu-section">
            <div class="section-header">
                <i class="fas fa-cogs"></i>
                <h2>System & Integrations</h2>
            </div>
            <div class="menu-items">
                <a href="/admin/integrations.php" class="menu-item">
                    <i class="fas fa-plug"></i>
                    <div class="item-content">
                        <h3>Integrations</h3>
                        <p>Third-party connections, APIs, and data synchronization</p>
                    </div>
                </a>
                <a href="/admin/activity_logs.php" class="menu-item">
                    <i class="fas fa-history"></i>
                    <div class="item-content">
                        <h3>Activity Logs</h3>
                        <p>Audit trails, user activity tracking, and system logs</p>
                    </div>
                </a>
                <a href="/admin/settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <div class="item-content">
                        <h3>System Settings</h3>
                        <p>Configuration, preferences, and system maintenance</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="quick-stats">
        <h2>System Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-value">Active Users</div>
                <div class="stat-label">Team members currently online</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-server"></i>
                <div class="stat-value">System Health</div>
                <div class="stat-label">All systems operational</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-sync"></i>
                <div class="stat-value">Last Sync</div>
                <div class="stat-label">Data synchronized 5 mins ago</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-shield-alt"></i>
                <div class="stat-value">Security Status</div>
                <div class="stat-label">No threats detected</div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-menu-page {
    padding: 30px;
    background: #f5f6fa;
    min-height: 100vh;
}

.menu-header {
    text-align: center;
    margin-bottom: 40px;
}

.menu-header h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin: 0;
}

.subtitle {
    color: #6c757d;
    font-size: 1.1rem;
    margin-top: 10px;
}

.menu-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
}

.menu-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.section-header i {
    font-size: 1.5rem;
    color: #667eea;
}

.section-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.3rem;
}

.menu-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}

.menu-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.menu-item.featured {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.menu-item.featured:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.menu-item i {
    font-size: 2rem;
    opacity: 0.8;
}

.menu-item.featured i {
    color: white;
}

.item-content h3 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.item-content p {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.8;
}

.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge.new {
    background: #27ae60;
    color: white;
}

.quick-stats {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.quick-stats h2 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-card i {
    font-size: 2rem;
    color: #667eea;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.85rem;
    color: #6c757d;
}

/* Responsive Design */
@media (max-width: 768px) {
    .menu-items {
        grid-template-columns: 1fr;
    }
    
    .menu-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>