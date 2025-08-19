-- Asclepius Wellness App Schema (MySQL 8+)

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','member') NOT NULL DEFAULT 'member',
  city VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  email_verified_at DATETIME NULL,
  verification_token VARCHAR(64) NULL,
  verification_sent_at DATETIME NULL,
  INDEX idx_users_verify_token (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  mobile VARCHAR(30) NOT NULL,
  city VARCHAR(120) NULL,
  work VARCHAR(120) NULL,
  age INT NULL,
  meeting_date DATE NULL,
  interest_level ENUM('Hot','Warm','Cold') NOT NULL DEFAULT 'Warm',
  notes TEXT NULL,
  follow_up_date DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'open',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_leads_user (user_id),
  INDEX idx_leads_followup (follow_up_date),
  CONSTRAINT fk_leads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pr_email (email),
  INDEX idx_pr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  task_date DATE NULL,
  is_daily TINYINT(1) NOT NULL DEFAULT 0,
  type ENUM('prospecting','followup','training','event','custom') NOT NULL DEFAULT 'custom',
  target_count INT NOT NULL DEFAULT 0,
  impact_score TINYINT NOT NULL DEFAULT 1,
  effort_score TINYINT NOT NULL DEFAULT 1,
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  due_time TIME NULL,
  repeat_rule ENUM('none','daily','weekly') NOT NULL DEFAULT 'none',
  checklist JSON NULL,
  script_a TEXT NULL,
  script_b TEXT NULL,
  is_template TINYINT(1) NOT NULL DEFAULT 0,
  template_name VARCHAR(120) NULL,
  assigned_to INT NULL,
  CONSTRAINT fk_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  task_id INT NOT NULL,
  completed_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_task (user_id, task_id),
  CONSTRAINT fk_user_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_tasks_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Logging per day for attempts/successes
CREATE TABLE IF NOT EXISTS user_task_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  task_id INT NOT NULL,
  log_date DATE NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  successes INT NOT NULL DEFAULT 0,
  notes TEXT NULL,
  UNIQUE KEY uniq_user_task_day (user_id, task_id, log_date),
  CONSTRAINT fk_utl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_utl_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assets attached to a task (scripts, PDFs, links)
CREATE TABLE IF NOT EXISTS task_assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  url VARCHAR(500) NOT NULL,
  CONSTRAINT fk_task_assets_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User-specific task state (kanban)
CREATE TABLE IF NOT EXISTS user_task_state (
  user_id INT NOT NULL,
  task_id INT NOT NULL,
  state ENUM('todo','doing','done') NOT NULL DEFAULT 'todo',
  PRIMARY KEY (user_id, task_id),
  CONSTRAINT fk_uts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_uts_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS learning_modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(120) NOT NULL,
  description TEXT NULL,
  content_url VARCHAR(500) NULL,
  type ENUM('video','pdf','article') NOT NULL DEFAULT 'video',
  order_index INT NOT NULL DEFAULT 0,
  published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  module_id INT NOT NULL,
  progress_percent INT NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  UNIQUE KEY uniq_user_module (user_id, module_id),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_module FOREIGN KEY (module_id) REFERENCES learning_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  file_url VARCHAR(500) NULL,
  type VARCHAR(40) NOT NULL,
  published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '🏆',
  threshold_type ENUM('leads','tasks','modules','streak') NOT NULL,
  threshold_value INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  achievement_id INT NOT NULL,
  awarded_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_ach (user_id, achievement_id),
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ua_ach FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  event_date DATETIME NULL,
  location VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  message_type ENUM('motivation','announcement') NOT NULL DEFAULT 'motivation',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login attempts for rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL,
  INDEX idx_login_attempts_email_time (email, attempted_at),
  INDEX idx_login_attempts_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NULL,
  notif_type VARCHAR(40) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  INDEX idx_notif_user (user_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Streaks
CREATE TABLE IF NOT EXISTS user_streaks (
  user_id INT PRIMARY KEY,
  current_streak INT NOT NULL DEFAULT 0,
  longest_streak INT NOT NULL DEFAULT 0,
  last_completed_date DATE NULL,
  CONSTRAINT fk_streaks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Additional achievements samples
INSERT IGNORE INTO achievements (code, name, description, icon, threshold_type, threshold_value) VALUES
('TASKS_5','5 Tasks','Completed five tasks','✅','tasks',5),
('MODULES_3','3 Modules 100%','Completed three modules','🎓','modules',3);

-- Sample data
INSERT IGNORE INTO achievements (code, name, description, icon, threshold_type, threshold_value) VALUES
('FIRST_LEAD','First Lead','Added your first lead','🌱','leads',1),
('FIVE_LEADS','5 Leads','Added five leads','🌿','leads',5),
('TEN_LEADS','10 Leads','Added ten leads','🍀','leads',10);

INSERT IGNORE INTO learning_modules (title, category, description, content_url, type, order_index, published) VALUES
('Direct Selling Basics 101','Direct Selling Basics','Understand fundamentals of direct selling.','https://www.youtube.com/','video',1,1),
('Asclepius Company Overview','Company Info','Company mission, certifications, and values.','https://example.com/company.pdf','pdf',2,1),
('Product Knowledge: Essentials','Product Knowledge','Learn key product benefits and usage.','https://example.com/products.pdf','pdf',3,1),
('Business Plan Explained','Business Plan','Compensation plan and growth path.','https://www.youtube.com/','video',4,1),
('Sales & Networking Skills','Sales & Networking','Prospecting, follow-ups, and closing.','https://example.com/article','article',5,1);

INSERT IGNORE INTO tasks (title, description, task_date, is_daily) VALUES
('Reach out to 3 prospects','Message or call three people and share product value.', NULL, 1),
('Review one module','Open a module and reach at least 50% progress.', NULL, 1);


