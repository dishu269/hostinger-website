<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();

// Real-time KPIs
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));

// Today's metrics
$today_metrics = $pdo->query("
    SELECT 
        COUNT(DISTINCT CASE WHEN l.created_at >= '$today' THEN l.id END) as leads_today,
        COUNT(DISTINCT CASE WHEN ut.completed_at >= '$today' THEN ut.id END) as tasks_today,
        COUNT(DISTINCT CASE WHEN u.last_login >= '$today' THEN u.id END) as active_users_today,
        COALESCE(SUM(CASE WHEN fr.record_date = '$today' AND fr.record_type = 'sale' THEN fr.amount END), 0) as revenue_today
    FROM users u
    LEFT JOIN leads l ON l.user_id = u.id
    LEFT JOIN user_tasks ut ON ut.user_id = u.id
    LEFT JOIN financial_records fr ON fr.user_id = u.id
")->fetch();

// Monthly metrics with comparison
$monthly_metrics = $pdo->query("
    SELECT 
        COUNT(DISTINCT CASE WHEN l.created_at >= '$month_start' THEN l.id END) as leads_mtd,
        COUNT(DISTINCT CASE WHEN l.created_at >= '$last_month_start' AND l.created_at <= '$last_month_end' THEN l.id END) as leads_last_month,
        COALESCE(SUM(CASE WHEN fr.record_date >= '$month_start' AND fr.record_type = 'sale' THEN fr.amount END), 0) as revenue_mtd,
        COALESCE(SUM(CASE WHEN fr.record_date >= '$last_month_start' AND fr.record_date <= '$last_month_end' AND fr.record_type = 'sale' THEN fr.amount END), 0) as revenue_last_month
    FROM users u
    LEFT JOIN leads l ON l.user_id = u.id
    LEFT JOIN financial_records fr ON fr.user_id = u.id
")->fetch();

// Calculate growth percentages
$leads_growth = $monthly_metrics['leads_last_month'] > 0 ? 
    round((($monthly_metrics['leads_mtd'] - $monthly_metrics['leads_last_month']) / $monthly_metrics['leads_last_month']) * 100, 1) : 0;
$revenue_growth = $monthly_metrics['revenue_last_month'] > 0 ? 
    round((($monthly_metrics['revenue_mtd'] - $monthly_metrics['revenue_last_month']) / $monthly_metrics['revenue_last_month']) * 100, 1) : 0;

// Conversion funnel
$funnel_data = $pdo->query("
    SELECT 
        COUNT(DISTINCT l.id) as total_leads,
        COUNT(DISTINCT CASE WHEN l.interest_level = 'Hot' THEN l.id END) as hot_leads,
        COUNT(DISTINCT CASE WHEN l.status = 'converted' THEN l.id END) as conversions
    FROM leads l
    WHERE l.created_at >= '$month_start'
")->fetch();

// Team performance
$team_performance = $pdo->query("
    SELECT 
        t.name as team_name,
        COUNT(DISTINCT u.id) as member_count,
        COUNT(DISTINCT l.id) as team_leads,
        COALESCE(SUM(fr.amount), 0) as team_revenue,
        AVG(CASE WHEN pm.metric_date >= '$month_start' THEN pm.leads_converted * 100.0 / NULLIF(pm.leads_contacted, 0) END) as avg_conversion_rate
    FROM teams t
    LEFT JOIN users u ON u.team_id = t.id
    LEFT JOIN leads l ON l.user_id = u.id AND l.created_at >= '$month_start'
    LEFT JOIN financial_records fr ON fr.user_id = u.id AND fr.record_date >= '$month_start' AND fr.record_type = 'sale'
    LEFT JOIN performance_metrics pm ON pm.user_id = u.id
    GROUP BY t.id
    ORDER BY team_revenue DESC
    LIMIT 5
")->fetchAll();

// Top performers
$top_performers = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.avatar_url,
        COUNT(DISTINCT l.id) as leads_count,
        COALESCE(SUM(fr.amount), 0) as revenue,
        AVG(pm.leads_converted * 100.0 / NULLIF(pm.leads_contacted, 0)) as conversion_rate,
        u.points
    FROM users u
    LEFT JOIN leads l ON l.user_id = u.id AND l.created_at >= '$month_start'
    LEFT JOIN financial_records fr ON fr.user_id = u.id AND fr.record_date >= '$month_start' AND fr.record_type = 'sale'
    LEFT JOIN performance_metrics pm ON pm.user_id = u.id AND pm.metric_date >= '$month_start'
    WHERE u.role = 'member'
    GROUP BY u.id
    ORDER BY revenue DESC
    LIMIT 10
")->fetchAll();

// Activity timeline
$recent_activities = $pdo->query("
    SELECT 
        al.created_at,
        al.action,
        al.entity_type,
        u.name as user_name,
        u.avatar_url
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 20
")->fetchAll();

// Revenue trend (last 30 days)
$revenue_trend = $pdo->query("
    SELECT 
        DATE(record_date) as date,
        SUM(amount) as daily_revenue
    FROM financial_records
    WHERE record_type = 'sale' AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(record_date)
    ORDER BY date
")->fetchAll();

// Active goals
$active_goals = $pdo->query("
    SELECT 
        g.*,
        CASE 
            WHEN g.goal_type = 'company' THEN 'Company'
            WHEN g.goal_type = 'team' THEN t.name
            WHEN g.goal_type = 'individual' THEN u.name
        END as owner_name
    FROM goals g
    LEFT JOIN teams t ON g.team_id = t.id
    LEFT JOIN users u ON g.owner_id = u.id
    WHERE g.status = 'active' AND g.end_date >= CURDATE()
    ORDER BY g.end_date
    LIMIT 5
")->fetchAll();
?>

<div class="admin-dashboard-enhanced">
    <div class="dashboard-header">
        <h1>Business Command Center</h1>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openQuickAction()">
                <i class="fas fa-plus"></i> Quick Action
            </button>
            <button class="btn btn-secondary" onclick="exportDashboard()">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Real-time KPIs -->
    <div class="kpi-section">
        <h2>Today's Performance</h2>
        <div class="kpi-grid">
            <div class="kpi-card primary">
                <div class="kpi-value"><?= number_format($today_metrics['leads_today']) ?></div>
                <div class="kpi-label">New Leads</div>
                <div class="kpi-trend">
                    <i class="fas fa-clock"></i> Today
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-value">₹<?= number_format($today_metrics['revenue_today']) ?></div>
                <div class="kpi-label">Revenue</div>
                <div class="kpi-trend">
                    <i class="fas fa-clock"></i> Today
                </div>
            </div>
            <div class="kpi-card info">
                <div class="kpi-value"><?= number_format($today_metrics['tasks_today']) ?></div>
                <div class="kpi-label">Tasks Completed</div>
                <div class="kpi-trend">
                    <i class="fas fa-check"></i> Today
                </div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-value"><?= number_format($today_metrics['active_users_today']) ?></div>
                <div class="kpi-label">Active Members</div>
                <div class="kpi-trend">
                    <i class="fas fa-users"></i> Online Today
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Performance with Trends -->
    <div class="performance-section">
        <h2>Monthly Performance</h2>
        <div class="performance-grid">
            <div class="performance-card">
                <h3>Lead Generation</h3>
                <div class="metric-value"><?= number_format($monthly_metrics['leads_mtd']) ?></div>
                <div class="metric-comparison <?= $leads_growth >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-arrow-<?= $leads_growth >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs($leads_growth) ?>% vs last month
                </div>
                <div class="mini-chart" id="leads-chart"></div>
            </div>
            <div class="performance-card">
                <h3>Revenue</h3>
                <div class="metric-value">₹<?= number_format($monthly_metrics['revenue_mtd']) ?></div>
                <div class="metric-comparison <?= $revenue_growth >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-arrow-<?= $revenue_growth >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs($revenue_growth) ?>% vs last month
                </div>
                <div class="mini-chart" id="revenue-chart"></div>
            </div>
            <div class="performance-card">
                <h3>Conversion Funnel</h3>
                <div class="funnel">
                    <div class="funnel-stage">
                        <div class="stage-label">Total Leads</div>
                        <div class="stage-value"><?= number_format($funnel_data['total_leads']) ?></div>
                    </div>
                    <div class="funnel-stage hot">
                        <div class="stage-label">Hot Leads</div>
                        <div class="stage-value"><?= number_format($funnel_data['hot_leads']) ?></div>
                        <div class="stage-percent"><?= $funnel_data['total_leads'] > 0 ? round($funnel_data['hot_leads'] * 100 / $funnel_data['total_leads'], 1) : 0 ?>%</div>
                    </div>
                    <div class="funnel-stage converted">
                        <div class="stage-label">Converted</div>
                        <div class="stage-value"><?= number_format($funnel_data['conversions']) ?></div>
                        <div class="stage-percent"><?= $funnel_data['hot_leads'] > 0 ? round($funnel_data['conversions'] * 100 / $funnel_data['hot_leads'], 1) : 0 ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Performance -->
    <div class="team-section">
        <h2>Team Performance</h2>
        <div class="team-table">
            <table>
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Members</th>
                        <th>Leads</th>
                        <th>Revenue</th>
                        <th>Avg Conversion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($team_performance as $team): ?>
                    <tr>
                        <td><?= htmlspecialchars($team['team_name']) ?></td>
                        <td><?= $team['member_count'] ?></td>
                        <td><?= $team['team_leads'] ?></td>
                        <td>₹<?= number_format($team['team_revenue']) ?></td>
                        <td><?= round($team['avg_conversion_rate'], 1) ?>%</td>
                        <td>
                            <button class="btn-small" onclick="viewTeamDetails(<?= $team['team_id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Performers Leaderboard -->
    <div class="leaderboard-section">
        <h2>Top Performers</h2>
        <div class="leaderboard">
            <?php foreach($top_performers as $index => $performer): ?>
            <div class="performer-card">
                <div class="rank"><?= $index + 1 ?></div>
                <img src="<?= $performer['avatar_url'] ?: '/assets/img/placeholder.svg' ?>" class="avatar">
                <div class="performer-info">
                    <div class="name"><?= htmlspecialchars($performer['name']) ?></div>
                    <div class="stats">
                        <span class="stat">
                            <i class="fas fa-users"></i> <?= $performer['leads_count'] ?> leads
                        </span>
                        <span class="stat">
                            <i class="fas fa-rupee-sign"></i> ₹<?= number_format($performer['revenue']) ?>
                        </span>
                        <span class="stat">
                            <i class="fas fa-percentage"></i> <?= round($performer['conversion_rate'], 1) ?>% conv
                        </span>
                        <span class="stat">
                            <i class="fas fa-star"></i> <?= number_format($performer['points']) ?> pts
                        </span>
                    </div>
                </div>
                <button class="btn-small" onclick="viewUserProfile(<?= $performer['id'] ?>)">
                    <i class="fas fa-chart-line"></i> Details
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Active Goals & OKRs -->
    <div class="goals-section">
        <h2>Active Goals & OKRs</h2>
        <div class="goals-list">
            <?php foreach($active_goals as $goal): ?>
            <div class="goal-card">
                <div class="goal-header">
                    <span class="goal-type <?= $goal['goal_type'] ?>"><?= ucfirst($goal['goal_type']) ?></span>
                    <span class="goal-owner"><?= htmlspecialchars($goal['owner_name']) ?></span>
                </div>
                <h4><?= htmlspecialchars($goal['title']) ?></h4>
                <div class="goal-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $goal['target_value'] > 0 ? round($goal['current_value'] * 100 / $goal['target_value'], 1) : 0 ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <?= number_format($goal['current_value']) ?> / <?= number_format($goal['target_value']) ?> <?= htmlspecialchars($goal['unit']) ?>
                    </div>
                </div>
                <div class="goal-deadline">
                    <i class="fas fa-calendar"></i> Due: <?= date('M d, Y', strtotime($goal['end_date'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="activity-section">
        <h2>Recent Activity</h2>
        <div class="activity-timeline">
            <?php foreach($recent_activities as $activity): ?>
            <div class="activity-item">
                <img src="<?= $activity['avatar_url'] ?: '/assets/img/placeholder.svg' ?>" class="activity-avatar">
                <div class="activity-content">
                    <div class="activity-text">
                        <strong><?= htmlspecialchars($activity['user_name']) ?></strong>
                        <?= htmlspecialchars($activity['action']) ?>
                        <span class="entity-type"><?= htmlspecialchars($activity['entity_type']) ?></span>
                    </div>
                    <div class="activity-time">
                        <i class="fas fa-clock"></i> <?= time_ago($activity['created_at']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="/admin/users.php?action=add" class="action-card">
                <i class="fas fa-user-plus"></i>
                <span>Add Team Member</span>
            </a>
            <a href="/admin/tasks.php?action=create" class="action-card">
                <i class="fas fa-tasks"></i>
                <span>Create Task</span>
            </a>
            <a href="/admin/messages.php?action=broadcast" class="action-card">
                <i class="fas fa-bullhorn"></i>
                <span>Send Broadcast</span>
            </a>
            <a href="/admin/reports.php" class="action-card">
                <i class="fas fa-chart-bar"></i>
                <span>Generate Report</span>
            </a>
            <a href="/admin/goals.php?action=create" class="action-card">
                <i class="fas fa-bullseye"></i>
                <span>Set Goal</span>
            </a>
            <a href="/admin/workflows.php" class="action-card">
                <i class="fas fa-robot"></i>
                <span>Automation</span>
            </a>
        </div>
    </div>
</div>

<style>
.admin-dashboard-enhanced {
    padding: 20px;
    background: #f5f6fa;
    min-height: 100vh;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-header h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.kpi-section, .performance-section, .team-section, .leaderboard-section, 
.goals-section, .activity-section, .quick-actions {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.kpi-card {
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    transition: transform 0.2s;
}

.kpi-card:hover {
    transform: translateY(-5px);
}

.kpi-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.kpi-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.kpi-card.info { background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%); color: white; }
.kpi-card.warning { background: linear-gradient(135deg, #F2994A 0%, #F2C94C 100%); color: white; }

.kpi-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.kpi-label {
    font-size: 1.1rem;
    opacity: 0.9;
}

.kpi-trend {
    margin-top: 10px;
    font-size: 0.9rem;
    opacity: 0.8;
}

.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.performance-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.performance-card h3 {
    margin: 0 0 15px 0;
    color: #495057;
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.metric-comparison {
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.metric-comparison.positive { color: #27ae60; }
.metric-comparison.negative { color: #e74c3c; }

.funnel {
    margin-top: 20px;
}

.funnel-stage {
    background: #e9ecef;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    position: relative;
}

.funnel-stage.hot { background: #ffeaa7; }
.funnel-stage.converted { background: #55efc4; }

.stage-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.stage-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.stage-percent {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.9rem;
    color: #6c757d;
}

.team-table {
    overflow-x: auto;
    margin-top: 20px;
}

.team-table table {
    width: 100%;
    border-collapse: collapse;
}

.team-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.team-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.leaderboard {
    margin-top: 20px;
}

.performer-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 10px;
}

.performer-card .rank {
    font-size: 1.5rem;
    font-weight: bold;
    color: #6c757d;
    width: 30px;
}

.performer-card .avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.performer-info {
    flex: 1;
}

.performer-info .name {
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.performer-info .stats {
    display: flex;
    gap: 15px;
    font-size: 0.9rem;
    color: #6c757d;
}

.goals-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.goal-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.goal-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.goal-type {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.goal-type.company { background: #e3f2fd; color: #1976d2; }
.goal-type.team { background: #f3e5f5; color: #7b1fa2; }
.goal-type.individual { background: #e8f5e9; color: #388e3c; }

.goal-progress {
    margin: 15px 0;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #667eea;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 5px;
}

.activity-timeline {
    margin-top: 20px;
}

.activity-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.activity-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.activity-content {
    flex: 1;
}

.activity-time {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 5px;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: #495057;
    transition: all 0.2s;
}

.action-card:hover {
    background: #667eea;
    color: white;
    transform: translateY(-3px);
}

.action-card i {
    font-size: 2rem;
}

.btn-small {
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    background: #667eea;
    color: white;
    cursor: pointer;
    font-size: 0.85rem;
}

.btn-small:hover {
    background: #5a67d8;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue trend chart
const revenueData = <?= json_encode($revenue_trend) ?>;
const revenueChart = new Chart(document.getElementById('revenue-chart'), {
    type: 'line',
    data: {
        labels: revenueData.map(d => d.date),
        datasets: [{
            label: 'Daily Revenue',
            data: revenueData.map(d => d.daily_revenue),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { display: false },
            y: { display: false }
        }
    }
});

// Helper functions
function time_ago(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    return Math.floor(seconds / 86400) + ' days ago';
}

function viewTeamDetails(teamId) {
    window.location.href = `/admin/teams.php?id=${teamId}`;
}

function viewUserProfile(userId) {
    window.location.href = `/admin/users.php?action=view&id=${userId}`;
}

function openQuickAction() {
    // Open quick action modal
}

function exportDashboard() {
    window.location.href = '/admin/reports.php?export=dashboard';
}

// Auto-refresh dashboard every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>