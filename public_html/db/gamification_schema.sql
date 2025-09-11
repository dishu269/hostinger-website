-- Gamification System Schema

-- User points are already in users table, let's add more fields
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS total_badges INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS current_rank VARCHAR(50) DEFAULT 'Bronze',
ADD COLUMN IF NOT EXISTS rank_progress INT DEFAULT 0;

-- Badges table
CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_hi VARCHAR(100) NOT NULL,
    description TEXT,
    description_hi TEXT,
    icon VARCHAR(50) NOT NULL,
    points_required INT DEFAULT 0,
    category ENUM('achievement', 'milestone', 'special') DEFAULT 'achievement',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User badges
CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ranks table
CREATE TABLE IF NOT EXISTS ranks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    name_hi VARCHAR(50) NOT NULL,
    min_points INT NOT NULL,
    max_points INT NOT NULL,
    icon VARCHAR(50) NOT NULL,
    color VARCHAR(7) NOT NULL,
    benefits TEXT,
    benefits_hi TEXT,
    order_index INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Achievement triggers
CREATE TABLE IF NOT EXISTS achievement_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_type ENUM('leads_added', 'tasks_completed', 'modules_completed', 'streak_days', 'login_days') NOT NULL,
    trigger_value INT NOT NULL,
    badge_id INT NOT NULL,
    points_awarded INT DEFAULT 0,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default ranks
INSERT INTO ranks (name, name_hi, min_points, max_points, icon, color, benefits, benefits_hi, order_index) VALUES
('Bronze', 'कांस्य', 0, 999, '🥉', '#CD7F32', 'Basic access to all features', 'सभी सुविधाओं तक बुनियादी पहुंच', 1),
('Silver', 'रजत', 1000, 2499, '🥈', '#C0C0C0', 'Priority support, 10% bonus points', 'प्राथमिकता समर्थन, 10% बोनस अंक', 2),
('Gold', 'स्वर्ण', 2500, 4999, '🥇', '#FFD700', 'VIP support, 20% bonus points, exclusive content', 'VIP समर्थन, 20% बोनस अंक, विशेष सामग्री', 3),
('Platinum', 'प्लैटिनम', 5000, 9999, '💎', '#E5E4E2', 'Premium support, 30% bonus points, mentor access', 'प्रीमियम समर्थन, 30% बोनस अंक, मेंटर एक्सेस', 4),
('Diamond', 'हीरा', 10000, 999999, '💠', '#B9F2FF', 'Elite status, 50% bonus points, personal coach', 'एलीट स्थिति, 50% बोनस अंक, व्यक्तिगत कोच', 5);

-- Insert default badges
INSERT INTO badges (name, name_hi, description, description_hi, icon, points_required, category) VALUES
('First Steps', 'पहले कदम', 'Complete your first task', 'अपना पहला कार्य पूरा करें', '👣', 0, 'milestone'),
('Lead Hunter', 'लीड हंटर', 'Add 10 leads to your CRM', 'अपने CRM में 10 लीड जोड़ें', '🎯', 0, 'achievement'),
('Learning Star', 'लर्निंग स्टार', 'Complete 5 training modules', '5 प्रशिक्षण मॉड्यूल पूरे करें', '⭐', 0, 'achievement'),
('Streak Master', 'स्ट्रीक मास्टर', 'Maintain a 7-day streak', '7-दिन की स्ट्रीक बनाए रखें', '🔥', 0, 'achievement'),
('Super Achiever', 'सुपर अचीवर', 'Complete 50 tasks', '50 कार्य पूरे करें', '🚀', 0, 'milestone'),
('CRM Champion', 'CRM चैंपियन', 'Add 100 leads', '100 लीड जोड़ें', '🏆', 0, 'milestone'),
('Knowledge Guru', 'ज्ञान गुरु', 'Complete all training modules', 'सभी प्रशिक्षण मॉड्यूल पूरे करें', '🎓', 0, 'special'),
('Consistency King', 'निरंतरता राजा', '30-day login streak', '30-दिन लॉगिन स्ट्रीक', '👑', 0, 'special');

-- Insert achievement triggers
INSERT INTO achievement_triggers (trigger_type, trigger_value, badge_id, points_awarded) VALUES
('tasks_completed', 1, 1, 50),
('leads_added', 10, 2, 100),
('modules_completed', 5, 3, 200),
('streak_days', 7, 4, 150),
('tasks_completed', 50, 5, 500),
('leads_added', 100, 6, 1000),
('modules_completed', 20, 7, 2000),
('login_days', 30, 8, 1500);