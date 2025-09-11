<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/header-enhanced.php';
require_member();

$pdo = get_db();
$userId = (int)$user['id'];

// Get user's current rank and points
$userStmt = $pdo->prepare("
    SELECT u.*, r.name as rank_name, r.name_hi as rank_name_hi, r.icon as rank_icon, 
           r.color as rank_color, r.benefits, r.benefits_hi,
           (SELECT MIN(min_points) FROM ranks WHERE min_points > u.points) as next_rank_points
    FROM users u
    LEFT JOIN ranks r ON u.points >= r.min_points AND u.points <= r.max_points
    WHERE u.id = ?
");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get all ranks
$ranksStmt = $pdo->query("SELECT * FROM ranks ORDER BY order_index");
$allRanks = $ranksStmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's badges
$badgesStmt = $pdo->prepare("
    SELECT b.*, ub.earned_at 
    FROM badges b
    INNER JOIN user_badges ub ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.earned_at DESC
");
$badgesStmt->execute([$userId]);
$userBadges = $badgesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all badges with earned status
$allBadgesStmt = $pdo->prepare("
    SELECT b.*, 
           CASE WHEN ub.user_id IS NOT NULL THEN 1 ELSE 0 END as earned,
           ub.earned_at
    FROM badges b
    LEFT JOIN user_badges ub ON b.id = ub.badge_id AND ub.user_id = ?
    ORDER BY b.category, b.id
");
$allBadgesStmt->execute([$userId]);
$allBadges = $allBadgesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get leaderboard position
$leaderboardStmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as position 
    FROM users 
    WHERE points > (SELECT points FROM users WHERE id = ?)
");
$leaderboardStmt->execute([$userId]);
$leaderboardPosition = $leaderboardStmt->fetchColumn();

// Calculate progress to next rank
$currentPoints = (int)$userData['points'];
$nextRankPoints = (int)$userData['next_rank_points'] ?: $currentPoints;
$progressToNext = $nextRankPoints > $currentPoints ? 
    round(($currentPoints % 1000) / 1000 * 100) : 100;
?>

<style>
.achievements-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.rank-showcase {
    background: linear-gradient(135deg, <?= $userData['rank_color'] ?>33 0%, <?= $userData['rank_color'] ?>11 100%);
    border: 3px solid <?= $userData['rank_color'] ?>;
    border-radius: 25px;
    padding: 40px;
    text-align: center;
    margin-bottom: 40px;
}

.current-rank {
    font-size: 80px;
    margin-bottom: 20px;
}

.rank-name {
    font-size: 36px;
    font-weight: bold;
    color: <?= $userData['rank_color'] ?>;
    margin-bottom: 10px;
}

.rank-points {
    font-size: 24px;
    color: #6B7280;
    margin-bottom: 20px;
}

.rank-progress {
    max-width: 600px;
    margin: 0 auto 30px;
}

.progress-bar-large {
    height: 35px;
    background: #E5E7EB;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
}

.progress-fill-animated {
    height: 100%;
    background: linear-gradient(90deg, <?= $userData['rank_color'] ?> 0%, <?= $userData['rank_color'] ?>CC 100%);
    transition: width 1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

.rank-benefits {
    background: white;
    padding: 20px 30px;
    border-radius: 15px;
    display: inline-block;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.stat-box {
    background: white;
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #1F2937;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 16px;
    color: #6B7280;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-header h2 {
    font-size: 32px;
    color: #1F2937;
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.badge-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.badge-card.earned {
    border: 2px solid #10B981;
}

.badge-card.not-earned {
    opacity: 0.6;
    filter: grayscale(50%);
}

.badge-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.badge-icon {
    font-size: 60px;
    margin-bottom: 15px;
}

.badge-name {
    font-size: 18px;
    font-weight: bold;
    color: #1F2937;
    margin-bottom: 10px;
}

.badge-description {
    font-size: 14px;
    color: #6B7280;
    margin-bottom: 10px;
}

.badge-earned-date {
    font-size: 12px;
    color: #10B981;
    font-weight: bold;
}

.earned-ribbon {
    position: absolute;
    top: 10px;
    right: -30px;
    background: #10B981;
    color: white;
    padding: 5px 40px;
    transform: rotate(45deg);
    font-size: 12px;
    font-weight: bold;
}

.ranks-progression {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.ranks-timeline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    padding: 40px 0;
}

.rank-item {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 2;
}

.rank-item .rank-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 10px;
}

.rank-item.current {
    transform: scale(1.2);
}

.rank-item.completed .rank-icon {
    filter: none;
}

.rank-item.locked .rank-icon {
    filter: grayscale(100%);
    opacity: 0.5;
}

.rank-item .rank-name {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 5px;
}

.rank-item .rank-points {
    font-size: 14px;
    color: #6B7280;
}

.ranks-line {
    position: absolute;
    top: 60px;
    left: 10%;
    right: 10%;
    height: 4px;
    background: #E5E7EB;
    z-index: 1;
}

.ranks-line-fill {
    height: 100%;
    background: linear-gradient(90deg, #10B981 0%, #34D399 100%);
    transition: width 1s ease;
}

@media (max-width: 768px) {
    .achievements-container {
        padding: 15px;
    }
    
    .rank-showcase {
        padding: 25px;
    }
    
    .current-rank {
        font-size: 60px;
    }
    
    .rank-name {
        font-size: 28px;
    }
    
    .badges-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .ranks-timeline {
        flex-direction: column;
        gap: 30px;
    }
    
    .ranks-line {
        display: none;
    }
}
</style>

<div class="achievements-container">
    <!-- Rank Showcase -->
    <div class="rank-showcase">
        <div class="current-rank"><?= $userData['rank_icon'] ?></div>
        <div class="rank-name">
            <?= get_current_language() == 'hi' ? $userData['rank_name_hi'] : $userData['rank_name'] ?>
        </div>
        <div class="rank-points">
            <?= number_format($userData['points']) ?> <?php _e('points'); ?>
        </div>
        
        <div class="rank-progress">
            <div class="progress-bar-large">
                <div class="progress-fill-animated" style="width: <?= $progressToNext ?>%">
                    <?= $progressToNext ?>%
                </div>
            </div>
            <p style="margin-top: 10px; color: #6B7280;">
                <?= number_format($nextRankPoints - $currentPoints) ?> <?php _e('points_to_next'); ?>
            </p>
        </div>
        
        <div class="rank-benefits">
            <h4><?= get_current_language() == 'hi' ? 'रैंक लाभ' : 'Rank Benefits' ?></h4>
            <p><?= get_current_language() == 'hi' ? $userData['benefits_hi'] : $userData['benefits'] ?></p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-icon">🏆</div>
            <div class="stat-value">#<?= $leaderboardPosition ?></div>
            <div class="stat-label"><?= get_current_language() == 'hi' ? 'लीडरबोर्ड रैंक' : 'Leaderboard Rank' ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-icon">🎖️</div>
            <div class="stat-value"><?= count($userBadges) ?></div>
            <div class="stat-label"><?php _e('badges'); ?> <?= get_current_language() == 'hi' ? 'अर्जित' : 'Earned' ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-icon">⭐</div>
            <div class="stat-value"><?= number_format($userData['points']) ?></div>
            <div class="stat-label"><?= get_current_language() == 'hi' ? 'कुल अंक' : 'Total Points' ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-icon">🔥</div>
            <div class="stat-value"><?= $userData['current_streak'] ?? 0 ?></div>
            <div class="stat-label"><?= get_current_language() == 'hi' ? 'वर्तमान स्ट्रीक' : 'Current Streak' ?></div>
        </div>
    </div>

    <!-- Ranks Progression -->
    <div class="ranks-progression">
        <h2><?= get_current_language() == 'hi' ? 'रैंक प्रगति' : 'Rank Progression' ?></h2>
        <div class="ranks-timeline">
            <div class="ranks-line">
                <div class="ranks-line-fill" style="width: <?= ($userData['order_index'] - 1) * 25 ?>%"></div>
            </div>
            <?php foreach ($allRanks as $rank): 
                $isCompleted = $currentPoints >= $rank['min_points'];
                $isCurrent = $userData['rank_name'] == $rank['name'];
            ?>
            <div class="rank-item <?= $isCurrent ? 'current' : '' ?> <?= $isCompleted ? 'completed' : 'locked' ?>">
                <span class="rank-icon"><?= $rank['icon'] ?></span>
                <div class="rank-name"><?= get_current_language() == 'hi' ? $rank['name_hi'] : $rank['name'] ?></div>
                <div class="rank-points"><?= number_format($rank['min_points']) ?>+</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Badges Section -->
    <div class="section-header">
        <h2><?php _e('badges'); ?></h2>
        <p><?= count($userBadges) ?>/<?= count($allBadges) ?> <?= get_current_language() == 'hi' ? 'अर्जित' : 'Earned' ?></p>
    </div>

    <div class="badges-grid">
        <?php foreach ($allBadges as $badge): ?>
        <div class="badge-card <?= $badge['earned'] ? 'earned' : 'not-earned' ?>">
            <?php if ($badge['earned']): ?>
            <div class="earned-ribbon">✓</div>
            <?php endif; ?>
            
            <div class="badge-icon"><?= $badge['icon'] ?></div>
            <div class="badge-name">
                <?= get_current_language() == 'hi' ? $badge['name_hi'] : $badge['name'] ?>
            </div>
            <div class="badge-description">
                <?= get_current_language() == 'hi' ? $badge['description_hi'] : $badge['description'] ?>
            </div>
            
            <?php if ($badge['earned']): ?>
            <div class="badge-earned-date">
                <?= get_current_language() == 'hi' ? 'अर्जित' : 'Earned' ?>: 
                <?= date('d/m/Y', strtotime($badge['earned_at'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Call to Action -->
    <div style="text-align: center; margin-top: 40px;">
        <a href="/user/leaderboard.php" class="big-button">
            <?php _e('leaderboard'); ?> <?= get_current_language() == 'hi' ? 'देखें' : 'View' ?> →
        </a>
    </div>
</div>

<script>
// Animate progress bars and counters
window.addEventListener('load', function() {
    // Animate progress bars
    document.querySelectorAll('.progress-fill-animated').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
    
    // Animate counters
    document.querySelectorAll('.stat-value').forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^0-9]/g, ''));
        let current = 0;
        const increment = target / 50;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (counter.textContent.includes('#')) {
                counter.textContent = '#' + Math.floor(current);
            } else {
                counter.textContent = Math.floor(current).toLocaleString();
            }
        }, 20);
    });
    
    // Voice announcement for achievements
    const lang = '<?= get_current_language() ?>';
    const announcement = lang === 'hi' 
        ? 'आपकी रैंक है <?= $userData['rank_name_hi'] ?> और आपने <?= count($userBadges) ?> बैज अर्जित किए हैं'
        : 'Your rank is <?= $userData['rank_name'] ?> and you have earned <?= count($userBadges) ?> badges';
    
    // Uncomment to enable voice announcement
    // speak(announcement);
});

// Hover effects with voice
document.querySelectorAll('.badge-card').forEach(badge => {
    badge.addEventListener('mouseenter', function() {
        if (this.classList.contains('earned')) {
            const name = this.querySelector('.badge-name').textContent;
            const date = this.querySelector('.badge-earned-date')?.textContent || '';
            // speak(name + '. ' + date);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer-enhanced.php'; ?>