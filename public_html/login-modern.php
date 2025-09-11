<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = get_user();
if ($user) {
  header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard.php'));
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
  $password = $_POST['password'] ?? '';
  
  if (!$email || !$password) {
    $error = 'Please provide valid email and password.';
  } else {
    $stmt = get_db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
      if ($user['verified']) {
        $_SESSION['user_id'] = $user['id'];
        // Update last login
        $updateStmt = get_db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $updateStmt->execute([$user['id']]);
        
        header('Location: ' . ($user['role'] === 'admin' ? '/admin/index.php' : '/user/dashboard.php'));
        exit;
      } else {
        $error = 'Please verify your email before logging in.';
      }
    } else {
      $error = 'Invalid email or password.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-xl);
            position: relative;
            overflow: hidden;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: var(--space-2xl);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--space-2xl);
        }
        
        .auth-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto var(--space-lg);
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .auth-logo i {
            font-size: 2.5rem;
            color: white;
        }
        
        .auth-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: var(--space-sm);
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .auth-subtitle {
            color: var(--text-secondary);
        }
        
        .form-group {
            margin-bottom: var(--space-lg);
        }
        
        .form-label {
            display: block;
            margin-bottom: var(--space-sm);
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: var(--space-md) var(--space-lg);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all var(--transition-base);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-light);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .form-input-icon {
            position: relative;
        }
        
        .form-input-icon i {
            position: absolute;
            left: var(--space-lg);
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        
        .form-input-icon .form-input {
            padding-left: calc(var(--space-lg) * 2.5);
        }
        
        .password-toggle {
            position: absolute;
            right: var(--space-lg);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: var(--space-sm);
        }
        
        .password-toggle:hover {
            color: var(--text-secondary);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-xl);
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .forgot-link {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-base);
        }
        
        .forgot-link:hover {
            color: var(--primary);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: var(--space-xl);
            padding-top: var(--space-xl);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .auth-footer p {
            color: var(--text-secondary);
        }
        
        .auth-footer a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition-base);
        }
        
        .auth-footer a:hover {
            color: var(--primary);
        }
        
        .alert {
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            animation: slideIn 0.3s ease;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .social-login {
            margin-top: var(--space-xl);
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin: var(--space-xl) 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider span {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .social-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-md);
        }
        
        .btn-social {
            padding: var(--space-md);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
            transition: all var(--transition-base);
            font-weight: 500;
        }
        
        .btn-social:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        /* Light mode adjustments */
        body.light-mode .auth-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        body.light-mode .form-input {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        body.light-mode .form-input:focus {
            background: rgba(0, 0, 0, 0.03);
        }
        
        /* Floating shapes animation */
        .floating-shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
            pointer-events: none;
        }
        
        .shape-1 {
            width: 200px;
            height: 200px;
            background: var(--gradient-primary);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: -100px;
            left: -100px;
            animation-duration: 25s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            background: var(--gradient-secondary);
            border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%;
            bottom: -75px;
            right: -75px;
            animation-duration: 30s;
            animation-delay: 5s;
        }
        
        .shape-3 {
            width: 100px;
            height: 100px;
            background: var(--gradient-accent);
            border-radius: 41% 59% 41% 59% / 41% 59% 41% 59%;
            top: 50%;
            right: -50px;
            animation-duration: 35s;
            animation-delay: 10s;
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

    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to continue your journey</p>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="form-input-icon">
                            <i class="fas fa-envelope"></i>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="you@example.com"
                                required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="form-input-icon">
                            <i class="fas fa-lock"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="/forgot.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Sign In <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="divider">
                    <span>or continue with</span>
                </div>

                <div class="social-buttons">
                    <a href="#" class="btn-social">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="#" class="btn-social">
                        <i class="fab fa-facebook"></i> Facebook
                    </a>
                </div>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="/register.php">Sign up for free</a></p>
                </div>
            </div>

            <!-- Floating shapes -->
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <button class="theme-toggle" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <script src="/assets/js/modern-app.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }

        // Add input animation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>