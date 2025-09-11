<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: /admin/users.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("
    SELECT u.*, 
           t.name as team_name,
           t.id as team_id,
           (SELECT COUNT(*) FROM users WHERE team_id = t.id AND id = t.team_lead_id AND t.team_lead_id = u.id) as is_team_lead
    FROM users u
    LEFT JOIN teams t ON u.team_id = t.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /admin/users.php');
    exit;
}

// Performance metrics for last 30 days
$performance = $pdo->prepare("
    SELECT 
        COALESCE(SUM(leads_contacted), 0) as total_leads_contacted,
        COALESCE(SUM(leads_converted), 0) as total_leads_converted,
        COALESCE(SUM(revenue_generated), 0) as total_revenue,
        COALESCE(SUM(tasks_completed), 0) as total_tasks,
        COALESCE(SUM(training_hours), 0) as total_training_hours,
        COALESCE(AVG(leads_converted * 100.0 / NULLIF(leads_contacted, 0)), 0) as avg_conversion_rate,
        COUNT(DISTINCT metric_date) as active_days
    FROM performance_metrics
    WHERE user_id = ? AND metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$performance->execute([$user_id]);
$perf_data = $performance->fetch();

// Daily performance trend
$daily_trend = $pdo->prepare("
    SELECT 
        metric_date,
        leads_contacted,
        leads_converted,
        revenue_generated,
        tasks_completed
    FROM performance_metrics
    WHERE user_id = ? AND metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY metric_date
");
$daily_trend->execute([$user_id]);
$trend_data = $daily_trend->fetchAll();

// Recent leads
$recent_leads = $pdo->prepare("
    SELECT * FROM leads 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recent_leads->execute([$user_id]);
$leads = $recent_leads->fetchAll();

// Completed tasks
$completed_tasks = $pdo->prepare("
    SELECT t.*, ut.completed_at
    FROM user_tasks ut
    JOIN tasks t ON ut.task_id = t.id
    WHERE ut.user_id = ?
    ORDER BY ut.completed_at DESC
    LIMIT 10
");
$completed_tasks->execute([$user_id]);
$tasks = $completed_tasks->fetchAll();

// Achievements
$achievements = $pdo->prepare("
    SELECT a.*, ua.awarded_at
    FROM user_achievements ua
    JOIN achievements a ON ua.achievement_id = a.id
    WHERE ua.user_id = ?
    ORDER BY ua.awarded_at DESC
");
$achievements->execute([$user_id]);
$user_achievements = $achievements->fetchAll();

// Active goals
$goals = $pdo->prepare("
    SELECT * FROM goals
    WHERE owner_id = ? AND status = 'active'
    ORDER BY end_date
");
$goals->execute([$user_id]);
$user_goals = $goals->fetchAll();

// Training progress
$training = $pdo->prepare("
    SELECT lm.*, mp.progress_percent, mp.completed_at
    FROM learning_modules lm
    LEFT JOIN module_progress mp ON lm.id = mp.module_id AND mp.user_id = ?
    ORDER BY lm.order_index
");
$training->execute([$user_id]);
$modules = $training->fetchAll();

// Activity log
$activity_log = $pdo->prepare("
    SELECT * FROM activity_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$activity_log->execute([$user_id]);
$activities = $activity_log->fetchAll();

// Financial summary
$financial_summary = $pdo->prepare("
    SELECT 
        record_type,
        SUM(amount) as total_amount,
        COUNT(*) as transaction_count
    FROM financial_records
    WHERE user_id = ? AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY record_type
");
$financial_summary->execute([$user_id]);
$financials = $financial_summary->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="user-profile-enhanced">
    <div class="profile-header">
        <div class="profile-info">
            <img src="<?= $user['avatar_url'] ?: '/assets/img/placeholder.svg' ?>" class="profile-avatar">
            <div>
                <h1><?= htmlspecialchars($user['name']) ?></h1>
                <p class="profile-meta">
                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : 'badge-primary' ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                    <?php if ($user['team_name']): ?>
                    <span class="team-badge">
                        <i class="fas fa-users"></i> <?= htmlspecialchars($user['team_name']) ?>
                        <?= $user['is_team_lead'] ? ' (Team Lead)' : '' ?>
                    </span>
                    <?php endif; ?>
                </p>
                <p class="contact-info">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                    <?php if ($user['phone']): ?>
                    <i class="fas fa-phone ml-3"></i> <?= htmlspecialchars($user['phone']) ?>
                    <?php endif; ?>
                    <?php if ($user['city']): ?>
                    <i class="fas fa-map-marker-alt ml-3"></i> <?= htmlspecialchars($user['city']) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="profile-actions">
            <a href="/admin/users.php?action=edit&id=<?= $user_id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <button onclick="sendMessage(<?= $user_id ?>)" class="btn btn-secondary">
                <i class="fas fa-envelope"></i> Send Message
            </button>
            <a href="/admin/users.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="performance-overview">
        <h2>Performance Overview (Last 30 Days)</h2>
        <div class="perf-cards">
            <div class="perf-card">
                <i class="fas fa-users"></i>
                <div class="perf-content">
                    <div class="perf-value"><?= number_format($perf_data['total_leads_contacted']) ?></div>
                    <div class="perf-label">Leads Contacted</div>
                </div>
            </div>
            <div class="perf-card">
                <i class="fas fa-handshake"></i>
                <div class="perf-content">
                    <div class="perf-value"><?= number_format($perf_data['total_leads_converted']) ?></div>
                    <div class="perf-label">Conversions</div>
                    <div class="perf-subtext"><?= round($perf_data['avg_conversion_rate'], 1) ?>% rate</div>
                </div>
            </div>
            <div class="perf-card">
                <i class="fas fa-rupee-sign"></i>
                <div class="perf-content">
                    <div class="perf-value">₹<?= number_format($perf_data['total_revenue']) ?></div>
                    <div class="perf-label">Revenue Generated</div>
                </div>
            </div>
            <div class="perf-card">
                <i class="fas fa-tasks"></i>
                <div class="perf-content">
                    <div class="perf-value"><?= number_format($perf_data['total_tasks']) ?></div>
                    <div class="perf-label">Tasks Completed</div>
                </div>
            </div>
            <div class="perf-card">
                <i class="fas fa-graduation-cap"></i>
                <div class="perf-content">
                    <div class="perf-value"><?= number_format($perf_data['total_training_hours'], 1) ?></div>
                    <div class="perf-label">Training Hours</div>
                </div>
            </div>
            <div class="perf-card">
                <i class="fas fa-calendar-check"></i>
                <div class="perf-content">
                    <div class="perf-value"><?= $perf_data['active_days'] ?></div>
                    <div class="perf-label">Active Days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="chart-section">
        <h2>Daily Performance Trend</h2>
        <canvas id="performanceChart" height="100"></canvas>
    </div>

    <div class="profile-grid">
        <!-- Goals & OKRs -->
        <div class="profile-section">
            <h3><i class="fas fa-bullseye"></i> Active Goals</h3>
            <?php if (empty($user_goals)): ?>
            <p class="no-data">No active goals</p>
            <?php else: ?>
            <div class="goals-list">
                <?php foreach($user_goals as $goal): ?>
                <div class="goal-item">
                    <h4><?= htmlspecialchars($goal['title']) ?></h4>
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $goal['target_value'] > 0 ? round($goal['current_value'] * 100 / $goal['target_value']) : 0 ?>%"></div>
                        </div>
                        <span class="progress-text">
                            <?= number_format($goal['current_value']) ?> / <?= number_format($goal['target_value']) ?> <?= htmlspecialchars($goal['unit']) ?>
                        </span>
                    </div>
                    <div class="goal-meta">
                        <i class="fas fa-calendar"></i> Due: <?= date('M d, Y', strtotime($goal['end_date'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Leads -->
        <div class="profile-section">
            <h3><i class="fas fa-user-friends"></i> Recent Leads</h3>
            <?php if (empty($leads)): ?>
            <p class="no-data">No leads yet</p>
            <?php else: ?>
            <div class="leads-list">
                <?php foreach($leads as $lead): ?>
                <div class="lead-item">
                    <div class="lead-name"><?= htmlspecialchars($lead['name']) ?></div>
                    <div class="lead-details">
                        <span class="interest-level <?= strtolower($lead['interest_level']) ?>">
                            <?= $lead['interest_level'] ?>
                        </span>
                        <span><?= htmlspecialchars($lead['city']) ?></span>
                        <span><?= date('M d', strtotime($lead['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Achievements -->
        <div class="profile-section">
            <h3><i class="fas fa-trophy"></i> Achievements</h3>
            <?php if (empty($user_achievements)): ?>
            <p class="no-data">No achievements yet</p>
            <?php else: ?>
            <div class="achievements-grid">
                <?php foreach($user_achievements as $ach): ?>
                <div class="achievement-badge">
                    <span class="badge-icon"><?= $ach['icon'] ?></span>
                    <span class="badge-name"><?= htmlspecialchars($ach['name']) ?></span>
                    <span class="badge-date"><?= date('M d', strtotime($ach['awarded_at'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Training Progress -->
        <div class="profile-section">
            <h3><i class="fas fa-book"></i> Training Progress</h3>
            <div class="training-modules">
                <?php foreach($modules as $module): ?>
                <div class="module-item">
                    <div class="module-info">
                        <div class="module-name"><?= htmlspecialchars($module['title']) ?></div>
                        <div class="module-category"><?= htmlspecialchars($module['category']) ?></div>
                    </div>
                    <div class="module-progress">
                        <div class="progress-bar small">
                            <div class="progress-fill" style="width: <?= $module['progress_percent'] ?: 0 ?>%"></div>
                        </div>
                        <span class="progress-percent"><?= $module['progress_percent'] ?: 0 ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="activity-timeline-section">
        <h2>Recent Activity</h2>
        <div class="timeline">
            <?php foreach($activities as $activity): ?>
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <span class="action"><?= htmlspecialchars($activity['action']) ?></span>
                        <span class="entity-type"><?= htmlspecialchars($activity['entity_type']) ?></span>
                    </div>
                    <div class="timeline-time">
                        <i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.user-profile-enhanced {
    padding: 20px;
    background: #f5f6fa;
    min-height: 100vh;
}

.profile-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.profile-info {
    display: flex;
    gap: 25px;
    align-items: center;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e9ecef;
}

.profile-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    margin: 10px 0;
}

.contact-info {
    color: #6c757d;
    font-size: 0.9rem;
}

.contact-info i {
    margin-right: 5px;
}

.profile-actions {
    display: flex;
    gap: 10px;
}

.performance-overview {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.perf-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.perf-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.perf-card i {
    font-size: 2rem;
    color: #667eea;
    opacity: 0.8;
}

.perf-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
}

.perf-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.perf-subtext {
    font-size: 0.85rem;
    color: #667eea;
    margin-top: 2px;
}

.chart-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}

.profile-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.profile-section h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.no-data {
    color: #6c757d;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.goals-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.goal-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.goal-item h4 {
    margin: 0 0 10px 0;
    font-size: 1rem;
}

.goal-progress {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar.small {
    height: 6px;
}

.progress-fill {
    height: 100%;
    background: #667eea;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.85rem;
    color: #6c757d;
    white-space: nowrap;
}

.goal-meta {
    font-size: 0.85rem;
    color: #6c757d;
}

.leads-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.lead-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.lead-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.lead-details {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: #6c757d;
}

.interest-level {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.interest-level.hot { background: #fee; color: #e55; }
.interest-level.warm { background: #ffeaa7; color: #f39c12; }
.interest-level.cold { background: #dfe6e9; color: #636e72; }

.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
}

.achievement-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.badge-icon {
    font-size: 2rem;
    margin-bottom: 5px;
}

.badge-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2c3e50;
}

.badge-date {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 3px;
}

.training-modules {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.module-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.module-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.module-category {
    font-size: 0.8rem;
    color: #6c757d;
}

.module-progress {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 150px;
}

.progress-percent {
    font-size: 0.85rem;
    color: #6c757d;
    min-width: 35px;
    text-align: right;
}

.activity-timeline-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 20px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #667eea;
    border: 2px solid white;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.timeline-header {
    font-weight: 600;
    margin-bottom: 5px;
}

.entity-type {
    color: #667eea;
    font-size: 0.9rem;
}

.timeline-time {
    font-size: 0.85rem;
    color: #6c757d;
}

.team-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-primary { background: #667eea; color: white; }
.badge-danger { background: #e74c3c; color: white; }

.btn-outline {
    background: white;
    color: #667eea;
    border: 1px solid #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance trend chart
const trendData = <?= json_encode($trend_data) ?>;
const ctx = document.getElementById('performanceChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendData.map(d => new Date(d.metric_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        datasets: [{
            label: 'Leads Contacted',
            data: trendData.map(d => d.leads_contacted),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.3
        }, {
            label: 'Conversions',
            data: trendData.map(d => d.leads_converted),
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39, 174, 96, 0.1)',
            tension: 0.3
        }, {
            label: 'Tasks Completed',
            data: trendData.map(d => d.tasks_completed),
            borderColor: '#f39c12',
            backgroundColor: 'rgba(243, 156, 18, 0.1)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function sendMessage(userId) {
    // Implement message sending
    alert('Message functionality to be implemented');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>