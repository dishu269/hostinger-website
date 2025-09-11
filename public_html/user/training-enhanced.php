<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/header.php';
require_member();

$pdo = get_db();
$userId = (int)$user['id'];

// Get training modules
$modulesStmt = $pdo->prepare("
    SELECT 
        tm.*,
        COALESCE(up.completion_percentage, 0) as user_progress,
        COALESCE(up.completed, 0) as is_completed,
        COALESCE(up.last_accessed, NULL) as last_accessed
    FROM training_modules tm
    LEFT JOIN user_progress up ON tm.id = up.module_id AND up.user_id = ?
    WHERE tm.is_active = 1
    ORDER BY tm.order_index, tm.id
");
$modulesStmt->execute([$userId]);
$modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected module details
$selectedModuleId = $_GET['module'] ?? null;
$selectedModule = null;
$moduleContent = [];

if ($selectedModuleId) {
    foreach ($modules as $module) {
        if ($module['id'] == $selectedModuleId) {
            $selectedModule = $module;
            break;
        }
    }
    
    // Get module content
    if ($selectedModule) {
        $contentStmt = $pdo->prepare("
            SELECT * FROM training_content 
            WHERE module_id = ? 
            ORDER BY order_index
        ");
        $contentStmt->execute([$selectedModuleId]);
        $moduleContent = $contentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update last accessed
        $updateStmt = $pdo->prepare("
            INSERT INTO user_progress (user_id, module_id, last_accessed) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_accessed = NOW()
        ");
        $updateStmt->execute([$userId, $selectedModuleId]);
    }
}
?>

<style>
.training-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.training-header {
    text-align: center;
    margin-bottom: 40px;
}

.training-header h1 {
    font-size: 36px;
    color: #1F2937;
    margin-bottom: 10px;
}

.training-header p {
    font-size: 20px;
    color: #6B7280;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.module-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.module-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.module-card.completed {
    border: 3px solid #10B981;
}

.module-header {
    padding: 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.module-header h3 {
    font-size: 24px;
    margin: 0;
}

.module-body {
    padding: 25px;
}

.module-progress {
    margin: 20px 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #E5E7EB;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #10B981;
    transition: width 0.5s ease;
}

.module-stats {
    display: flex;
    justify-content: space-between;
    font-size: 16px;
    color: #6B7280;
}

.start-button {
    display: block;
    width: 100%;
    padding: 15px;
    background: #4F46E5;
    color: white;
    text-align: center;
    border-radius: 10px;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
    transition: all 0.3s;
}

.start-button:hover {
    background: #4338CA;
}

.start-button.completed {
    background: #10B981;
}

/* Module Content View */
.module-content-view {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.content-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #E5E7EB;
}

.content-header h2 {
    font-size: 32px;
    color: #1F2937;
    margin-bottom: 10px;
}

.content-navigation {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.content-tab {
    padding: 12px 24px;
    background: #F3F4F6;
    border-radius: 10px;
    text-decoration: none;
    color: #6B7280;
    font-weight: bold;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.content-tab:hover,
.content-tab.active {
    background: #4F46E5;
    color: white;
}

.content-section {
    display: none;
}

.content-section.active {
    display: block;
}

.video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 15px;
    margin-bottom: 30px;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.notes-content {
    font-size: 18px;
    line-height: 1.8;
    color: #374151;
}

.notes-content h3 {
    font-size: 24px;
    color: #1F2937;
    margin: 25px 0 15px;
}

.notes-content ul {
    margin: 15px 0;
    padding-left: 30px;
}

.notes-content li {
    margin-bottom: 10px;
}

.download-section {
    display: grid;
    gap: 20px;
}

.download-item {
    padding: 20px;
    background: #F9FAFB;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.download-item .info {
    flex: 1;
}

.download-item .info h4 {
    font-size: 20px;
    margin-bottom: 5px;
}

.download-button {
    padding: 10px 20px;
    background: #10B981;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.download-button:hover {
    background: #059669;
}

.quiz-section {
    max-width: 600px;
    margin: 0 auto;
}

.quiz-question {
    background: #F9FAFB;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 20px;
}

.quiz-question h4 {
    font-size: 22px;
    margin-bottom: 20px;
    color: #1F2937;
}

.quiz-options {
    display: grid;
    gap: 15px;
}

.quiz-option {
    padding: 15px 20px;
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 18px;
}

.quiz-option:hover {
    border-color: #4F46E5;
    background: #EEF2FF;
}

.quiz-option.selected {
    border-color: #4F46E5;
    background: #EEF2FF;
}

.quiz-option.correct {
    border-color: #10B981;
    background: #D1FAE5;
}

.quiz-option.incorrect {
    border-color: #EF4444;
    background: #FEE2E2;
}

.complete-button {
    display: block;
    width: 100%;
    max-width: 400px;
    margin: 30px auto;
    padding: 18px;
    background: #10B981;
    color: white;
    text-align: center;
    border-radius: 10px;
    font-size: 20px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.complete-button:hover {
    background: #059669;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    background: #F3F4F6;
    color: #6B7280;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-bottom: 20px;
}

.back-button:hover {
    background: #E5E7EB;
}

@media (max-width: 768px) {
    .training-container {
        padding: 15px;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
    
    .module-content-view {
        padding: 20px;
    }
}
</style>

<div class="training-container">
    <?php if (!$selectedModule): ?>
    <!-- Modules List View -->
    <div class="training-header">
        <h1><span style="font-size: 48px;">🎓</span> <?php _e('training_modules'); ?></h1>
        <p><?= get_current_language() == 'hi' ? 'अपनी गति से सीखें, हर कदम पर बढ़ें' : 'Learn at your pace, grow with every step' ?></p>
    </div>

    <div class="modules-grid">
        <?php foreach ($modules as $module): ?>
        <div class="module-card <?= $module['is_completed'] ? 'completed' : '' ?>" onclick="location.href='?module=<?= $module['id'] ?>'">
            <div class="module-header">
                <h3><?= htmlspecialchars($module['title']) ?></h3>
            </div>
            <div class="module-body">
                <p><?= htmlspecialchars($module['description']) ?></p>
                
                <div class="module-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $module['user_progress'] ?>%"></div>
                    </div>
                </div>
                
                <div class="module-stats">
                    <span><?= $module['user_progress'] ?>% <?php _e('completed'); ?></span>
                    <span><?= $module['duration'] ?> min</span>
                </div>
                
                <a href="?module=<?= $module['id'] ?>" class="start-button <?= $module['is_completed'] ? 'completed' : '' ?>">
                    <?php if ($module['is_completed']): ?>
                        ✅ <?php _e('completed'); ?>
                    <?php elseif ($module['user_progress'] > 0): ?>
                        <?php _e('continue_learning'); ?> →
                    <?php else: ?>
                        <?php _e('start_learning'); ?> →
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <!-- Module Content View -->
    <a href="/user/training-enhanced.php" class="back-button">
        ← <?php _e('back'); ?> <?= get_current_language() == 'hi' ? 'मॉड्यूल्स पर' : 'to Modules' ?>
    </a>

    <div class="module-content-view">
        <div class="content-header">
            <h2><?= htmlspecialchars($selectedModule['title']) ?></h2>
            <p><?= htmlspecialchars($selectedModule['description']) ?></p>
        </div>

        <div class="content-navigation">
            <a href="#" class="content-tab active" data-section="video">
                <span>🎥</span> <?php _e('watch_video'); ?>
            </a>
            <a href="#" class="content-tab" data-section="notes">
                <span>📝</span> <?php _e('read_notes'); ?>
            </a>
            <a href="#" class="content-tab" data-section="download">
                <span>📥</span> <?php _e('download_material'); ?>
            </a>
            <a href="#" class="content-tab" data-section="quiz">
                <span>❓</span> <?php _e('take_quiz'); ?>
            </a>
        </div>

        <!-- Video Section -->
        <div class="content-section active" id="video">
            <div class="video-container">
                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allowfullscreen></iframe>
            </div>
            <p style="text-align: center; color: #6B7280;">
                <?= get_current_language() == 'hi' ? 'वीडियो देखने के बाद नोट्स पढ़ना न भूलें' : 'Don\'t forget to read the notes after watching the video' ?>
            </p>
        </div>

        <!-- Notes Section -->
        <div class="content-section" id="notes">
            <div class="notes-content">
                <h3><?= get_current_language() == 'hi' ? 'मुख्य बिंदु' : 'Key Points' ?></h3>
                <ul>
                    <li><?= get_current_language() == 'hi' ? 'सबसे पहले, अपने लक्ष्य को समझें' : 'First, understand your goal' ?></li>
                    <li><?= get_current_language() == 'hi' ? 'हर दिन छोटे कदम उठाएं' : 'Take small steps every day' ?></li>
                    <li><?= get_current_language() == 'hi' ? 'निरंतरता ही सफलता की कुंजी है' : 'Consistency is the key to success' ?></li>
                    <li><?= get_current_language() == 'hi' ? 'अपनी प्रगति को ट्रैक करें' : 'Track your progress' ?></li>
                </ul>

                <h3><?= get_current_language() == 'hi' ? 'महत्वपूर्ण टिप्स' : 'Important Tips' ?></h3>
                <ul>
                    <li><?= get_current_language() == 'hi' ? 'रोज़ाना कम से कम 3 नए लोगों से संपर्क करें' : 'Contact at least 3 new people daily' ?></li>
                    <li><?= get_current_language() == 'hi' ? 'फॉलो-अप कभी न भूलें' : 'Never forget to follow up' ?></li>
                    <li><?= get_current_language() == 'hi' ? 'अपने CRM को अपडेट रखें' : 'Keep your CRM updated' ?></li>
                </ul>
            </div>
        </div>

        <!-- Download Section -->
        <div class="content-section" id="download">
            <div class="download-section">
                <div class="download-item">
                    <div class="info">
                        <h4>📄 <?= get_current_language() == 'hi' ? 'ट्रेनिंग गाइड PDF' : 'Training Guide PDF' ?></h4>
                        <p><?= get_current_language() == 'hi' ? 'पूरी जानकारी एक PDF में' : 'Complete information in one PDF' ?></p>
                    </div>
                    <a href="#" class="download-button"><?php _e('download'); ?> ↓</a>
                </div>
                
                <div class="download-item">
                    <div class="info">
                        <h4>📊 <?= get_current_language() == 'hi' ? 'प्रेजेंटेशन स्लाइड्स' : 'Presentation Slides' ?></h4>
                        <p><?= get_current_language() == 'hi' ? 'मीटिंग के लिए तैयार स्लाइड्स' : 'Meeting-ready slides' ?></p>
                    </div>
                    <a href="#" class="download-button"><?php _e('download'); ?> ↓</a>
                </div>
                
                <div class="download-item">
                    <div class="info">
                        <h4>📝 <?= get_current_language() == 'hi' ? 'स्क्रिप्ट टेम्प्लेट' : 'Script Template' ?></h4>
                        <p><?= get_current_language() == 'hi' ? 'बातचीत के लिए तैयार स्क्रिप्ट' : 'Ready-to-use conversation scripts' ?></p>
                    </div>
                    <a href="#" class="download-button"><?php _e('download'); ?> ↓</a>
                </div>
            </div>
        </div>

        <!-- Quiz Section -->
        <div class="content-section" id="quiz">
            <div class="quiz-section">
                <div class="quiz-question">
                    <h4><?= get_current_language() == 'hi' ? 'प्रश्न 1: सबसे पहले क्या करना चाहिए?' : 'Question 1: What should you do first?' ?></h4>
                    <div class="quiz-options">
                        <div class="quiz-option" onclick="selectOption(this)">
                            <?= get_current_language() == 'hi' ? 'लक्ष्य निर्धारित करें' : 'Set your goals' ?>
                        </div>
                        <div class="quiz-option" onclick="selectOption(this)">
                            <?= get_current_language() == 'hi' ? 'तुरंत काम शुरू करें' : 'Start working immediately' ?>
                        </div>
                        <div class="quiz-option" onclick="selectOption(this)">
                            <?= get_current_language() == 'hi' ? 'दूसरों से पूछें' : 'Ask others' ?>
                        </div>
                    </div>
                </div>

                <button class="complete-button" onclick="completeModule()">
                    ✅ <?= get_current_language() == 'hi' ? 'मॉड्यूल पूर्ण करें' : 'Complete Module' ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Content tab switching
document.querySelectorAll('.content-tab').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and sections
        document.querySelectorAll('.content-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding section
        this.classList.add('active');
        const sectionId = this.getAttribute('data-section');
        document.getElementById(sectionId).classList.add('active');
        
        // Speak the section name
        speak(this.textContent);
    });
});

// Quiz functionality
function selectOption(option) {
    // Remove selected class from all options
    option.parentElement.querySelectorAll('.quiz-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    option.classList.add('selected');
}

function completeModule() {
    if (confirm('<?= get_current_language() == 'hi' ? 'क्या आप इस मॉड्यूल को पूर्ण के रूप में चिह्नित करना चाहते हैं?' : 'Do you want to mark this module as complete?' ?>')) {
        // Here you would make an AJAX call to mark the module as complete
        alert('<?= get_current_language() == 'hi' ? 'बधाई हो! मॉड्यूल पूर्ण हो गया!' : 'Congratulations! Module completed!' ?>');
        
        // Award points
        speak('<?= get_current_language() == 'hi' ? 'बधाई हो! आपने 50 अंक अर्जित किए!' : 'Congratulations! You earned 50 points!' ?>');
        
        // Redirect back to modules list
        setTimeout(() => {
            location.href = '/user/training-enhanced.php';
        }, 2000);
    }
}

// Voice assistance
function speak(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
        utterance.rate = 0.9;
        speechSynthesis.speak(utterance);
    }
}

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