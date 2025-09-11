<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

require_auth();
$user = get_user();
if ($user['role'] !== 'member') {
    header('Location: /admin/index.php');
    exit;
}

$pdo = get_db();

// Get user stats
$stats = [
    'leads' => $pdo->prepare('SELECT COUNT(*) FROM leads WHERE user_id = ?'),
    'tasks_completed' => $pdo->prepare('SELECT COUNT(*) FROM task_logs WHERE user_id = ? AND status = "completed"'),
    'modules_completed' => $pdo->prepare('SELECT COUNT(*) FROM user_module_progress WHERE user_id = ? AND completed = 1'),
    'current_streak' => $pdo->prepare('SELECT current_streak FROM users WHERE id = ?')
];

$userStats = [];
foreach ($stats as $key => $stmt) {
    $stmt->execute([$user['id']]);
    $userStats[$key] = $stmt->fetchColumn();
}

// Get recent activities
$recentActivities = $pdo->prepare('
    SELECT "lead" as type, name as title, created_at, NULL as status 
    FROM leads 
    WHERE user_id = ? 
    UNION ALL
    SELECT "task" as type, t.title, tl.completed_at as created_at, tl.status 
    FROM task_logs tl 
    JOIN tasks t ON tl.task_id = t.id 
    WHERE tl.user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
');
$recentActivities->execute([$user['id'], $user['id']]);
$activities = $recentActivities->fetchAll();

// Get today's tasks
$today = date('Y-m-d');
$todayTasks = $pdo->prepare('
    SELECT t.*, 
           COALESCE(tl.status, "pending") as user_status,
           tl.id as log_id
    FROM tasks t
    LEFT JOIN task_logs tl ON t.id = tl.task_id AND tl.user_id = ? AND DATE(tl.created_at) = ?
    WHERE t.task_date = ? OR t.is_daily = 1
    ORDER BY t.task_date DESC
');
$todayTasks->execute([$user['id'], $today, $today]);
$tasks = $todayTasks->fetchAll();

// Get upcoming follow-ups
$followUps = $pdo->prepare('
    SELECT * FROM leads 
    WHERE user_id = ? AND next_follow_up >= CURDATE() 
    ORDER BY next_follow_up ASC 
    LIMIT 5
');
$followUps->execute([$user['id']]);
$upcomingFollowUps = $followUps->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/modern-style.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-page {
            min-height: 100vh;
            padding-top: 100px;
            background: var(--bg-primary);
        }
        
        .dashboard-header {
            margin-bottom: var(--space-2xl);
        }
        
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-2xl);
        }
        
        .welcome-message h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: var(--space-sm);
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-message p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        
        .quick-actions {
            display: flex;
            gap: var(--space-md);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-xl);
            margin-bottom: var(--space-2xl);
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: var(--space-sm);
        }
        
        .stat-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-xl);
        }
        
        .activity-card,
        .task-card {
            padding: var(--space-xl);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-lg);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: start;
            gap: var(--space-md);
            padding: var(--space-md) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }
        
        .activity-time {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .task-list {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .task-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-md);
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-md);
            transition: all var(--transition-base);
            cursor: pointer;
        }
        
        .task-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }
        
        .task-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid var(--primary);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-base);
            cursor: pointer;
        }
        
        .task-checkbox.checked {
            background: var(--primary);
        }
        
        .task-checkbox.checked i {
            color: white;
            font-size: 0.875rem;
        }
        
        .task-content {
            flex: 1;
        }
        
        .task-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }
        
        .task-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .streak-banner {
            background: var(--gradient-primary);
            border-radius: var(--radius-xl);
            padding: var(--space-2xl);
            margin-bottom: var(--space-2xl);
            position: relative;
            overflow: hidden;
        }
        
        .streak-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }
        
        .streak-number {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: var(--space-sm);
        }
        
        .streak-text {
            font-size: 1.25rem;
            opacity: 0.9;
        }
        
        .streak-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        /* Chart placeholder */
        .chart-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                flex-direction: column;
                align-items: start;
                gap: var(--space-lg);
            }
            
            .quick-actions {
                width: 100%;
            }
            
            .quick-actions .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
    </div>

    <!-- Modern Header -->
    <header class="modern-header">
        <div class="container">
            <div class="header-container">
                <a href="/" class="logo"><?= SITE_BRAND ?></a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="/user/dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="/user/learning.php" class="nav-link">Learning</a></li>
                        <li><a href="/user/crm.php" class="nav-link">CRM</a></li>
                        <li><a href="/user/tasks.php" class="nav-link">Tasks</a></li>
                        <li><a href="/user/community.php" class="nav-link">Community</a></li>
                        <li class="nav-profile">
                            <a href="/user/profile.php" class="nav-link nav-profile-link">
                                <img src="<?= get_avatar_url($user['id']) ?>" alt="Profile">
                                <span><?= htmlspecialchars($user['name']) ?></span>
                            </a>
                        </li>
                        <li><a href="/logout.php" class="btn btn-outline">Logout</a></li>
                    </ul>
                    <button class="menu-toggle" aria-label="Toggle menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard-page">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-section animate-on-scroll">
                <div class="welcome-message">
                    <h1>Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>!</h1>
                    <p>Let's make today count. Your success journey continues.</p>
                </div>
                <div class="quick-actions">
                    <a href="/user/crm.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Lead
                    </a>
                    <a href="/user/learning.php" class="btn btn-outline">
                        <i class="fas fa-graduation-cap"></i> Continue Learning
                    </a>
                </div>
            </div>

            <!-- Streak Banner -->
            <?php if ($userStats['current_streak'] > 0): ?>
            <div class="streak-banner animate-on-scroll" data-aos="fade-up">
                <div class="streak-content">
                    <div class="streak-number"><?= $userStats['current_streak'] ?></div>
                    <div class="streak-text">Day Streak! Keep the momentum going 🔥</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?= $userStats['leads'] ?></h3>
                            <p>Total Leads</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in" data-aos-delay="100">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?= $userStats['tasks_completed'] ?></h3>
                            <p>Tasks Completed</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in" data-aos-delay="200">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?= $userStats['modules_completed'] ?></h3>
                            <p>Modules Completed</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in" data-aos-delay="300">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?= $userStats['current_streak'] ?></h3>
                            <p>Current Streak</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Today's Tasks -->
                <div class="glass-card task-card animate-on-scroll" data-aos="fade-up">
                    <div class="card-header">
                        <h2 class="card-title">Today's Tasks</h2>
                        <a href="/user/tasks.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <div class="task-list">
                        <?php foreach ($tasks as $task): ?>
                        <div class="task-item" data-task-id="<?= $task['id'] ?>">
                            <div class="task-checkbox <?= $task['user_status'] === 'completed' ? 'checked' : '' ?>">
                                <?php if ($task['user_status'] === 'completed'): ?>
                                <i class="fas fa-check"></i>
                                <?php endif; ?>
                            </div>
                            <div class="task-content">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-description"><?= htmlspecialchars($task['description']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($tasks)): ?>
                        <p class="text-muted">No tasks for today. Check back tomorrow!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card activity-card animate-on-scroll" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-header">
                        <h2 class="card-title">Recent Activity</h2>
                    </div>
                    <ul class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?= $activity['type'] === 'lead' ? 'user-plus' : 'check' ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?= $activity['type'] === 'lead' ? 'Added new lead: ' : 'Completed task: ' ?>
                                    <?= htmlspecialchars($activity['title']) ?>
                                </div>
                                <div class="activity-time">
                                    <?= time_ago($activity['created_at']) ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($activities)): ?>
                        <p class="text-muted">No recent activity to show.</p>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Progress Chart -->
                <div class="glass-card animate-on-scroll" data-aos="fade-up" data-aos-delay="200">
                    <div class="card-header">
                        <h2 class="card-title">Weekly Progress</h2>
                    </div>
                    <div class="chart-container">
                        <i class="fas fa-chart-line" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p style="margin-top: 1rem;">Chart visualization coming soon</p>
                    </div>
                </div>

                <!-- Upcoming Follow-ups -->
                <div class="glass-card animate-on-scroll" data-aos="fade-up" data-aos-delay="300">
                    <div class="card-header">
                        <h2 class="card-title">Upcoming Follow-ups</h2>
                        <a href="/user/crm.php" class="btn btn-outline btn-sm">View CRM</a>
                    </div>
                    <ul class="activity-list">
                        <?php foreach ($upcomingFollowUps as $lead): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?= htmlspecialchars($lead['name']) ?></div>
                                <div class="activity-time">
                                    Follow up: <?= date('M d, Y', strtotime($lead['next_follow_up'])) ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($upcomingFollowUps)): ?>
                        <p class="text-muted">No upcoming follow-ups scheduled.</p>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <button class="theme-toggle" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="/assets/js/modern-app.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Task completion handler
        document.querySelectorAll('.task-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', function() {
                const taskItem = this.closest('.task-item');
                const taskId = taskItem.dataset.taskId;
                const isCompleted = this.classList.contains('checked');
                
                // Toggle state
                if (isCompleted) {
                    this.classList.remove('checked');
                    this.innerHTML = '';
                } else {
                    this.classList.add('checked');
                    this.innerHTML = '<i class="fas fa-check"></i>';
                }
                
                // Here you would make an AJAX call to update the task status
                // For now, just animate the task
                taskItem.style.opacity = '0.5';
                setTimeout(() => {
                    taskItem.style.opacity = '1';
                }, 300);
            });
        });
    </script>
</body>
</html>