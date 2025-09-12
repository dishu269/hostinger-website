<?php
require_once __DIR__ . '/includes/header-enhanced.php';

if ($user) {
  header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard-enhanced.php'));
  exit;
}

$pdo = get_db();
$today = (new DateTime('today'))->format('Y-m-d');
$motivationResult = $pdo->query("SELECT title, body FROM messages WHERE active = 1 AND message_type='motivation' ORDER BY id DESC LIMIT 1");
$motivation = $motivationResult ? $motivationResult->fetch() : null;
$todayTaskStmt = $pdo->prepare('SELECT title, description FROM tasks WHERE task_date = ? OR is_daily = 1 ORDER BY task_date DESC LIMIT 1');
$todayTaskStmt->execute([$today]);
$todayTask = $todayTaskStmt->fetch();
$modulesResult = $pdo->query('SELECT COUNT(*) FROM learning_modules WHERE published = 1');
$resourcesResult = $pdo->query('SELECT COUNT(*) FROM resources WHERE published = 1');
$membersResult = $pdo->query('SELECT COUNT(*) FROM users');
$stats = [
  'modules' => $modulesResult ? (int)$modulesResult->fetchColumn() : 0,
  'resources' => $resourcesResult ? (int)$resourcesResult->fetchColumn() : 0,
  'members' => $membersResult ? (int)$membersResult->fetchColumn() : 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - World-Class Digital HQ</title>
    <link rel="stylesheet" href="/assets/css/modern-style.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <li><a href="#features" class="nav-link">Features</a></li>
                        <li><a href="#stats" class="nav-link">Stats</a></li>
                        <li><a href="#about" class="nav-link">About</a></li>
                        <li><a href="/login.php" class="nav-link">Sign In</a></li>
                        <li><a href="/register.php" class="btn btn-primary">Get Started</a></li>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title animate-on-scroll">
                    Build, Learn, and Grow<br>
                    <span class="gradient-text">with <?= SITE_BRAND ?></span>
                </h1>
                <p class="hero-subtitle animate-on-scroll">
                    Digital HQ + Personal CRM + AI Mentor + Training Hub<br>
                    Designed for unstoppable daily momentum.
                </p>
                <div class="hero-buttons animate-on-scroll">
                    <a href="/register.php" class="btn btn-primary btn-magnetic">
                        Start Your Journey <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#features" class="btn btn-outline">
                        Explore Features <i class="fas fa-chevron-down"></i>
                    </a>
                </div>
                
                <?php if ($todayTask): ?>
                <div class="glass-card mt-4 animate-on-scroll" data-aos="fade-up">
                    <div class="today-task">
                        <h3><i class="fas fa-tasks"></i> Today's Focus Task</h3>
                        <h4><?= htmlspecialchars($todayTask['title']) ?></h4>
                        <p><?= nl2br(htmlspecialchars($todayTask['description'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-header text-center animate-on-scroll">
                <h2 class="section-title">Powerful Features for Your Success</h2>
                <p class="section-subtitle">Everything you need to build momentum and achieve your goals</p>
            </div>
            
            <div class="feature-grid">
                <div class="glass-card feature-card animate-on-scroll" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="feature-title">Learning Hub</h3>
                    <p class="feature-description">
                        Master direct selling basics, company info, products, and compensation plans. 
                        Track your progress with interactive modules.
                    </p>
                    <a href="/login.php" class="feature-link">
                        Explore Modules <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="glass-card feature-card animate-on-scroll" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Personal CRM</h3>
                    <p class="feature-description">
                        Manage leads, follow-ups, and reminders with smart guidance. 
                        Works offline with voice input support.
                    </p>
                    <a href="/login.php" class="feature-link">
                        Manage Leads <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="glass-card feature-card animate-on-scroll" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">AI Mentor</h3>
                    <p class="feature-description">
                        Practice scripts, handle objections, and build consistency with 
                        AI-powered guidance and streak tracking.
                    </p>
                    <a href="/login.php" class="feature-link">
                        Start Training <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="glass-card feature-card animate-on-scroll" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Analytics Dashboard</h3>
                    <p class="feature-description">
                        Track your performance with real-time analytics, goals tracking, 
                        and actionable insights.
                    </p>
                    <a href="/login.php" class="feature-link">
                        View Analytics <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="glass-card feature-card animate-on-scroll" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="feature-title">Gamification</h3>
                    <p class="feature-description">
                        Earn badges, climb leaderboards, and maintain streaks. 
                        Turn your growth journey into an engaging game.
                    </p>
                    <a href="/login.php" class="feature-link">
                        Earn Rewards <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="glass-card feature-card animate-on-scroll" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="feature-title">Community</h3>
                    <p class="feature-description">
                        Connect with team members, share experiences, and grow together 
                        in our supportive community forum.
                    </p>
                    <a href="/login.php" class="feature-link">
                        Join Community <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in">
                    <div class="stat-number" data-target="<?= $stats['modules'] ?>">0</div>
                    <div class="stat-label">Learning Modules</div>
                </div>
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in" data-aos-delay="100">
                    <div class="stat-number" data-target="<?= $stats['resources'] ?>">0</div>
                    <div class="stat-label">Resources Available</div>
                </div>
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in" data-aos-delay="200">
                    <div class="stat-number" data-target="<?= $stats['members'] ?>">0</div>
                    <div class="stat-label">Active Members</div>
                </div>
                <div class="glass-card stat-card animate-on-scroll" data-aos="zoom-in" data-aos-delay="300">
                    <div class="stat-number" data-target="99">0</div>
                    <div class="stat-label">Success Rate %</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Motivation Section -->
    <?php if ($motivation): ?>
    <section class="motivation-section">
        <div class="container">
            <div class="glass-card motivation-card animate-on-scroll" data-aos="fade-up">
                <div class="motivation-content">
                    <h3><?= htmlspecialchars($motivation['title'] ?? 'Daily Motivation') ?></h3>
                    <p><?= htmlspecialchars($motivation['body'] ?? 'Small steps daily lead to massive results. Take action now.') ?></p>
                    <div class="motivation-author">
                        <img src="/assets/img/placeholder.svg" alt="Author">
                        <span>Team <?= SITE_BRAND ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- How It Works -->
    <section id="about" class="how-it-works">
        <div class="container">
            <div class="section-header text-center animate-on-scroll">
                <h2 class="section-title">How It Works</h2>
                <p class="section-subtitle">Get started in 4 simple steps</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card animate-on-scroll" data-aos="fade-right">
                    <div class="step-number">01</div>
                    <h3>Create Account</h3>
                    <p>Sign up in seconds and set up your profile</p>
                </div>
                <div class="step-card animate-on-scroll" data-aos="fade-right" data-aos-delay="100">
                    <div class="step-number">02</div>
                    <h3>Add Your Leads</h3>
                    <p>Import or add your contacts to the CRM</p>
                </div>
                <div class="step-card animate-on-scroll" data-aos="fade-right" data-aos-delay="200">
                    <div class="step-number">03</div>
                    <h3>Learn & Grow</h3>
                    <p>Complete modules and practice with AI</p>
                </div>
                <div class="step-card animate-on-scroll" data-aos="fade-right" data-aos-delay="300">
                    <div class="step-number">04</div>
                    <h3>Track Success</h3>
                    <p>Monitor progress and celebrate wins</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="glass-card cta-card text-center animate-on-scroll" data-aos="zoom-in">
                <h2>Ready to Transform Your Business?</h2>
                <p>Join thousands of successful members who are already growing with <?= SITE_BRAND ?></p>
                <div class="cta-buttons">
                    <a href="/register.php" class="btn btn-primary btn-large">
                        Get Started Free <i class="fas fa-rocket"></i>
                    </a>
                    <div class="cta-features">
                        <span><i class="fas fa-check"></i> No Credit Card Required</span>
                        <span><i class="fas fa-check"></i> 30-Day Free Trial</span>
                        <span><i class="fas fa-check"></i> Cancel Anytime</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h3><?= SITE_BRAND ?></h3>
                    <p>Empowering teams to achieve extraordinary results through digital transformation.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <div>
                        <h4>Product</h4>
                        <ul>
                            <li><a href="#">Features</a></li>
                            <li><a href="#">Pricing</a></li>
                            <li><a href="#">Integrations</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4>Resources</h4>
                        <ul>
                            <li><a href="#">Documentation</a></li>
                            <li><a href="#">Tutorials</a></li>
                            <li><a href="#">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4>Company</h4>
                        <ul>
                            <li><a href="#">About</a></li>
                            <li><a href="#">Contact</a></li>
                            <li><a href="#">Privacy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_BRAND ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

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

        // Animate numbers on scroll
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.target);
                    modernApp.animateCounter(entry.target, target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-number').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>