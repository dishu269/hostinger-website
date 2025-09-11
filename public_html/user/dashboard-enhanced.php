<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/header.php';
require_member();
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$userId = (int)$user['id'];

// Fetch all dashboard data
$todayTask = get_today_task($pdo);
$motivation = get_motivation_message($pdo);
$due = get_due_leads($pdo, $userId);
$kpis = get_dashboard_kpis($pdo, $userId);
$streakRow = get_user_streak($pdo, $userId);
$notifications = get_notifications($pdo, $userId);
$weekly_chart_data = get_weekly_funnel_chart_data($pdo, $userId);

// Calculate funnel stats
$f = [
    'attempts' => array_sum($weekly_chart_data['datasets'][0]['data']),
    'successes' => array_sum($weekly_chart_data['datasets'][1]['data']),
];

// Extract KPI values
$completedTasks = $kpis['completed_tasks'];
$totalModules = $kpis['total_modules'];
$completedModules = $kpis['completed_modules'];

// Get user rank and points (for gamification)
$rankQuery = $pdo->prepare("
    SELECT 
        u.points,
        (SELECT COUNT(*) + 1 FROM users WHERE points > u.points) as rank,
        CASE
            WHEN u.points >= 10000 THEN 'Diamond'
            WHEN u.points >= 5000 THEN 'Platinum'
            WHEN u.points >= 2500 THEN 'Gold'
            WHEN u.points >= 1000 THEN 'Silver'
            ELSE 'Bronze'
        END as rank_name
    FROM users u
    WHERE u.id = ?
");
$rankQuery->execute([$userId]);
$userRank = $rankQuery->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Enhanced Styles for Better Accessibility */
.enhanced-dashboard {
    font-size: 18px;
    line-height: 1.6;
}

.language-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    background: #fff;
    padding: 10px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.language-toggle a {
    padding: 8px 16px;
    margin: 0 5px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.language-toggle a.active {
    background: #4F46E5;
    color: white;
}

.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.welcome-section h1 {
    font-size: 36px;
    margin-bottom: 10px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.stat-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card .icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: #f0f0f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.stat-card .value {
    font-size: 36px;
    font-weight: bold;
    color: #4F46E5;
    margin: 10px 0;
}

.stat-card .label {
    font-size: 18px;
    color: #666;
}

.action-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin: 30px 0;
}

.action-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.action-card:hover {
    transform: scale(1.02);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.action-card-header {
    padding: 25px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.action-card-header h3 {
    margin: 0;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.action-card-body {
    padding: 25px;
}

.big-button {
    display: inline-block;
    padding: 15px 30px;
    background: #4F46E5;
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
    transition: all 0.3s;
    text-align: center;
    margin: 10px 0;
}

.big-button:hover {
    background: #4338CA;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
}

.big-button.secondary {
    background: #E5E7EB;
    color: #374151;
}

.big-button.secondary:hover {
    background: #D1D5DB;
}

.help-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 70px;
    height: 70px;
    background: #10B981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    cursor: pointer;
    box-shadow: 0 5px 20px rgba(16, 185, 129, 0.3);
    transition: all 0.3s;
    z-index: 999;
}

.help-button:hover {
    transform: scale(1.1);
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    background: #FEF3C7;
    color: #92400E;
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: bold;
    gap: 10px;
}

.progress-bar {
    width: 100%;
    height: 25px;
    background: #E5E7EB;
    border-radius: 15px;
    overflow: hidden;
    margin: 15px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10B981 0%, #34D399 100%);
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

@media (max-width: 768px) {
    .enhanced-dashboard {
        font-size: 16px;
    }
    
    .welcome-section h1 {
        font-size: 28px;
    }
    
    .stat-card .value {
        font-size: 28px;
    }
    
    .action-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="enhanced-dashboard">
    <!-- Language Toggle -->
    <div class="language-toggle">
        <a href="?lang=hi" class="<?= get_current_language() == 'hi' ? 'active' : '' ?>">हिंदी</a>
        <a href="?lang=en" class="<?= get_current_language() == 'en' ? 'active' : '' ?>">English</a>
    </div>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1><?php _e('welcome'); ?> <?= htmlspecialchars($user['name']) ?> 👋</h1>
        <p><?php _e('daily_motivation'); ?></p>
        <div class="rank-badge">
            <span>🏆</span>
            <span><?php _e('your_rank'); ?>: <?= $userRank['rank_name'] ?> (#<?= $userRank['rank'] ?>)</span>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card" onclick="location.href='/user/tasks.php'">
            <div class="icon">✅</div>
            <div class="value"><?= $completedTasks ?></div>
            <div class="label"><?php _e('tasks_complete'); ?></div>
        </div>
        
        <div class="stat-card" onclick="location.href='/user/learning.php'">
            <div class="icon">📚</div>
            <div class="value"><?= $completedModules ?>/<?= $totalModules ?></div>
            <div class="label"><?php _e('learning_progress'); ?></div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $totalModules > 0 ? ($completedModules / $totalModules * 100) : 0 ?>%">
                    <?= $totalModules > 0 ? round($completedModules / $totalModules * 100) : 0 ?>%
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="icon">🔥</div>
            <div class="value"><?= (int)$streakRow['current_streak'] ?></div>
            <div class="label"><?php _e('streak'); ?> (<?php _e('attempts'); ?>)</div>
        </div>
        
        <div class="stat-card">
            <div class="icon">⭐</div>
            <div class="value"><?= number_format($userRank['points']) ?></div>
            <div class="label"><?php _e('points'); ?></div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="action-cards">
        <!-- Today's Task -->
        <div class="action-card">
            <div class="action-card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><span>📋</span> <?php _e('today_task'); ?></h3>
            </div>
            <div class="action-card-body">
                <?php if ($todayTask): ?>
                    <h4><?= htmlspecialchars($todayTask['title']) ?></h4>
                    <p><?= nl2br(htmlspecialchars($todayTask['description'])) ?></p>
                <?php else: ?>
                    <p><?php _e('no_task_today'); ?></p>
                <?php endif; ?>
                <a href="/user/tasks.php" class="big-button"><?php _e('open_tasks'); ?></a>
            </div>
        </div>

        <!-- Training Module -->
        <div class="action-card">
            <div class="action-card-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <h3><span>🎓</span> <?php _e('training_modules'); ?></h3>
            </div>
            <div class="action-card-body">
                <p><?php _e('learning_progress'); ?>: <strong><?= $completedModules ?>/<?= $totalModules ?></strong></p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $totalModules > 0 ? ($completedModules / $totalModules * 100) : 0 ?>%">
                        <?= $totalModules > 0 ? round($completedModules / $totalModules * 100) : 0 ?>%
                    </div>
                </div>
                <a href="/user/training.php" class="big-button"><?php _e('continue_learning'); ?></a>
            </div>
        </div>

        <!-- Lead Management -->
        <div class="action-card">
            <div class="action-card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><span>👥</span> <?php _e('leads'); ?></h3>
            </div>
            <div class="action-card-body">
                <?php if ($due && count($due) > 0): ?>
                    <p><strong><?= count($due) ?></strong> <?php _e('follow_ups_due'); ?></p>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach (array_slice($due, 0, 3) as $d): ?>
                        <li style="padding: 5px 0;">
                            <strong><?= htmlspecialchars($d['name']) ?></strong> - <?= htmlspecialchars($d['mobile']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No follow-ups due today</p>
                <?php endif; ?>
                <a href="/user/lead-management.php" class="big-button"><?php _e('add_lead'); ?></a>
                <a href="/user/crm.php" class="big-button secondary"><?php _e('view_all_leads'); ?></a>
            </div>
        </div>

        <!-- Resources -->
        <div class="action-card">
            <div class="action-card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><span>📁</span> <?php _e('resources'); ?></h3>
            </div>
            <div class="action-card-body">
                <p><?php _e('pdf_scripts'); ?>, <?php _e('presentations'); ?> & <?php _e('marketing_materials'); ?></p>
                <a href="/user/resources.php" class="big-button"><?php _e('view'); ?> <?php _e('resources'); ?></a>
            </div>
        </div>

        <!-- Achievements -->
        <div class="action-card">
            <div class="action-card-header" style="background: linear-gradient(135deg, #FDBB2D 0%, #22C1C3 100%);">
                <h3><span>🏆</span> <?php _e('achievements'); ?></h3>
            </div>
            <div class="action-card-body">
                <p><?php _e('your_rank'); ?>: <strong><?= $userRank['rank_name'] ?></strong></p>
                <p><?php _e('points'); ?>: <strong><?= number_format($userRank['points']) ?></strong></p>
                <a href="/user/achievements.php" class="big-button"><?php _e('view'); ?> <?php _e('achievements'); ?></a>
                <a href="/user/leaderboard.php" class="big-button secondary"><?php _e('leaderboard'); ?></a>
            </div>
        </div>

        <!-- Quick Help -->
        <div class="action-card">
            <div class="action-card-header" style="background: linear-gradient(135deg, #13547a 0%, #80d0c7 100%);">
                <h3><span>❓</span> <?php _e('need_help'); ?></h3>
            </div>
            <div class="action-card-body">
                <p><?php _e('watch_tutorial'); ?> या <?php _e('read_guide'); ?></p>
                <a href="/user/help.php" class="big-button"><?php _e('help'); ?> Center</a>
                <a href="/user/community.php" class="big-button secondary">Community</a>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <div class="help-button" onclick="showHelp()">
        <span>?</span>
    </div>
</div>

<script>
// Add voice guidance for better accessibility
function speak(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
        utterance.rate = 0.9;
        speechSynthesis.speak(utterance);
    }
}

// Help function
function showHelp() {
    const helpText = '<?= get_current_language() == 'hi' ? 'मदद के लिए, किसी भी बटन पर क्लिक करें या हेल्प सेंटर पर जाएं।' : 'For help, click any button or visit the Help Center.' ?>';
    alert(helpText);
    speak(helpText);
}

// Add tooltips with voice support
document.querySelectorAll('.stat-card, .action-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        const label = this.querySelector('.label')?.textContent || this.querySelector('h3')?.textContent;
        if (label) {
            this.title = label;
        }
    });
});

// Animate progress bars on load
window.addEventListener('load', function() {
    document.querySelectorAll('.progress-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>