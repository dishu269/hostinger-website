-- Enhanced Admin Panel Schema Extensions
-- For a top business team tracking system

-- Team hierarchy and organization structure
CREATE TABLE IF NOT EXISTS teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  parent_team_id INT NULL,
  team_lead_id INT NULL,
  description TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_teams_parent FOREIGN KEY (parent_team_id) REFERENCES teams(id) ON DELETE SET NULL,
  CONSTRAINT fk_teams_lead FOREIGN KEY (team_lead_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add team association to users
ALTER TABLE users ADD COLUMN team_id INT NULL AFTER role;
ALTER TABLE users ADD CONSTRAINT fk_users_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

-- Financial tracking
CREATE TABLE IF NOT EXISTS financial_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  record_type ENUM('sale','commission','expense','bonus') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT NULL,
  reference_id INT NULL,
  record_date DATE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_financial_user_date (user_id, record_date),
  CONSTRAINT fk_financial_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Goals and OKRs
CREATE TABLE IF NOT EXISTS goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  goal_type ENUM('company','team','individual') NOT NULL,
  owner_id INT NULL,
  team_id INT NULL,
  parent_goal_id INT NULL,
  target_value DECIMAL(12,2) NULL,
  current_value DECIMAL(12,2) NULL DEFAULT 0,
  unit VARCHAR(50) NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('draft','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_goals_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_goals_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
  CONSTRAINT fk_goals_parent FOREIGN KEY (parent_goal_id) REFERENCES goals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Key Results for OKRs
CREATE TABLE IF NOT EXISTS key_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  goal_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  target_value DECIMAL(12,2) NOT NULL,
  current_value DECIMAL(12,2) NOT NULL DEFAULT 0,
  unit VARCHAR(50) NULL,
  weight INT NOT NULL DEFAULT 100,
  CONSTRAINT fk_key_results_goal FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance metrics
CREATE TABLE IF NOT EXISTS performance_metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  metric_date DATE NOT NULL,
  leads_contacted INT NOT NULL DEFAULT 0,
  leads_converted INT NOT NULL DEFAULT 0,
  revenue_generated DECIMAL(12,2) NOT NULL DEFAULT 0,
  tasks_completed INT NOT NULL DEFAULT 0,
  training_hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  team_meetings_attended INT NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_user_date (user_id, metric_date),
  INDEX idx_metrics_date (metric_date),
  CONSTRAINT fk_metrics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory management
CREATE TABLE IF NOT EXISTS inventory_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  category VARCHAR(100) NULL,
  unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  current_stock INT NOT NULL DEFAULT 0,
  min_stock_level INT NOT NULL DEFAULT 0,
  max_stock_level INT NOT NULL DEFAULT 0,
  status ENUM('active','discontinued') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory transactions
CREATE TABLE IF NOT EXISTS inventory_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  transaction_type ENUM('purchase','sale','adjustment','return') NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  user_id INT NULL,
  notes TEXT NULL,
  transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inv_trans_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_trans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Advanced task dependencies
CREATE TABLE IF NOT EXISTS task_dependencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  depends_on_task_id INT NOT NULL,
  UNIQUE KEY uniq_dependency (task_id, depends_on_task_id),
  CONSTRAINT fk_dep_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_dep_depends FOREIGN KEY (depends_on_task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Workflow automation
CREATE TABLE IF NOT EXISTS workflows (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  trigger_type ENUM('time','event','condition') NOT NULL,
  trigger_config JSON NOT NULL,
  actions JSON NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Communication hub
CREATE TABLE IF NOT EXISTS chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  recipient_id INT NULL,
  team_id INT NULL,
  message TEXT NOT NULL,
  message_type ENUM('text','file','system') NOT NULL DEFAULT 'text',
  file_url VARCHAR(500) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chat_sender (sender_id),
  INDEX idx_chat_recipient (recipient_id),
  INDEX idx_chat_team (team_id),
  CONSTRAINT fk_chat_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custom reports
CREATE TABLE IF NOT EXISTS saved_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  report_type VARCHAR(100) NOT NULL,
  filters JSON NULL,
  columns JSON NULL,
  created_by INT NOT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reports_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs for audit trail
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(64) NULL,
  user_agent TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_user (user_id),
  INDEX idx_activity_entity (entity_type, entity_id),
  INDEX idx_activity_created (created_at),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Training paths and certifications
CREATE TABLE IF NOT EXISTS training_paths (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  modules JSON NOT NULL,
  required_for_role VARCHAR(50) NULL,
  certification_name VARCHAR(200) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_certifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  training_path_id INT NOT NULL,
  certified_at DATETIME NOT NULL,
  expires_at DATETIME NULL,
  certificate_url VARCHAR(500) NULL,
  CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_path FOREIGN KEY (training_path_id) REFERENCES training_paths(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Integrations
CREATE TABLE IF NOT EXISTS integrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('crm','accounting','communication','marketing','analytics') NOT NULL,
  config JSON NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_sync_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dashboard widgets configuration
CREATE TABLE IF NOT EXISTS dashboard_widgets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  widget_type VARCHAR(50) NOT NULL,
  position INT NOT NULL,
  size VARCHAR(20) NOT NULL DEFAULT 'medium',
  config JSON NULL,
  CONSTRAINT fk_widgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;