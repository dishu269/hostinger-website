<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/header.php';
require_member();

$pdo = get_db();
$userId = (int)$user['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $follow_up_date = $_POST['follow_up_date'] ?? '';
    
    if ($name && $mobile) {
        $stmt = $pdo->prepare("
            INSERT INTO leads (user_id, name, mobile, email, address, notes, follow_up_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $name, $mobile, $email, $address, $notes, $follow_up_date ?: null]);
        
        // Award points for adding lead
        $pointsStmt = $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?");
        $pointsStmt->execute([$userId]);
        
        $success = true;
    } else {
        $error = true;
    }
}

// Get recent leads
$leadsStmt = $pdo->prepare("
    SELECT * FROM leads 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$leadsStmt->execute([$userId]);
$recentLeads = $leadsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.lead-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.form-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-header {
    text-align: center;
    margin-bottom: 40px;
}

.form-header h1 {
    font-size: 32px;
    color: #1F2937;
    margin-bottom: 10px;
}

.form-header p {
    font-size: 18px;
    color: #6B7280;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #374151;
    margin-bottom: 10px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 15px 20px;
    font-size: 18px;
    border: 2px solid #E5E7EB;
    border-radius: 10px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-group .help-text {
    font-size: 14px;
    color: #6B7280;
    margin-top: 5px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.submit-btn {
    background: #4F46E5;
    color: white;
    padding: 18px 40px;
    font-size: 20px;
    font-weight: bold;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
    margin-top: 20px;
}

.submit-btn:hover {
    background: #4338CA;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
}

.success-message {
    background: #D1FAE5;
    color: #065F46;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 18px;
    animation: slideIn 0.5s ease;
}

.error-message {
    background: #FEE2E2;
    color: #991B1B;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 18px;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.recent-leads {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.recent-leads h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #1F2937;
}

.lead-item {
    padding: 20px;
    border-bottom: 1px solid #E5E7EB;
    transition: background 0.3s;
}

.lead-item:hover {
    background: #F9FAFB;
}

.lead-item:last-child {
    border-bottom: none;
}

.lead-name {
    font-size: 18px;
    font-weight: bold;
    color: #1F2937;
}

.lead-details {
    font-size: 16px;
    color: #6B7280;
    margin-top: 5px;
}

.lead-actions {
    margin-top: 10px;
}

.lead-actions a {
    margin-right: 15px;
    color: #4F46E5;
    text-decoration: none;
    font-weight: bold;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-card {
        padding: 25px;
    }
}

.field-icon {
    font-size: 24px;
    margin-right: 10px;
    vertical-align: middle;
}

.required {
    color: #EF4444;
}
</style>

<div class="lead-form-container">
    <?php if (isset($success)): ?>
    <div class="success-message">
        <span style="font-size: 30px;">✅</span><br>
        <?php _e('lead_added_success'); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="error-message">
        <span style="font-size: 30px;">❌</span><br>
        <?= get_current_language() == 'hi' ? 'कृपया नाम और मोबाइल नंबर दर्ज करें!' : 'Please enter name and mobile number!' ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-header">
            <h1><span class="field-icon">👥</span> <?php _e('add_lead'); ?></h1>
            <p><?= get_current_language() == 'hi' ? 'नई लीड की जानकारी भरें' : 'Fill in new lead information' ?></p>
        </div>

        <form method="POST" action="" onsubmit="return validateForm()">
            <div class="form-group">
                <label>
                    <span class="field-icon">👤</span>
                    <?php _e('lead_name'); ?> <span class="required">*</span>
                </label>
                <input type="text" name="name" required placeholder="<?= get_current_language() == 'hi' ? 'जैसे: राहुल शर्मा' : 'e.g. Rahul Sharma' ?>">
                <div class="help-text"><?= get_current_language() == 'hi' ? 'व्यक्ति का पूरा नाम दर्ज करें' : 'Enter the full name of the person' ?></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <span class="field-icon">📱</span>
                        <?php _e('mobile_number'); ?> <span class="required">*</span>
                    </label>
                    <input type="tel" name="mobile" required pattern="[0-9]{10}" placeholder="<?= get_current_language() == 'hi' ? '10 अंकों का नंबर' : '10 digit number' ?>">
                    <div class="help-text"><?= get_current_language() == 'hi' ? 'बिना +91 के 10 अंक' : '10 digits without +91' ?></div>
                </div>

                <div class="form-group">
                    <label>
                        <span class="field-icon">📧</span>
                        <?php _e('email'); ?>
                    </label>
                    <input type="email" name="email" placeholder="example@email.com">
                    <div class="help-text"><?= get_current_language() == 'hi' ? 'वैकल्पिक' : 'Optional' ?></div>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <span class="field-icon">📍</span>
                    <?php _e('address'); ?>
                </label>
                <input type="text" name="address" placeholder="<?= get_current_language() == 'hi' ? 'शहर या क्षेत्र' : 'City or Area' ?>">
                <div class="help-text"><?= get_current_language() == 'hi' ? 'वैकल्पिक - शहर या क्षेत्र का नाम' : 'Optional - City or area name' ?></div>
            </div>

            <div class="form-group">
                <label>
                    <span class="field-icon">📝</span>
                    <?php _e('notes'); ?>
                </label>
                <textarea name="notes" rows="3" placeholder="<?= get_current_language() == 'hi' ? 'कोई विशेष जानकारी...' : 'Any special information...' ?>"></textarea>
                <div class="help-text"><?= get_current_language() == 'hi' ? 'कोई भी महत्वपूर्ण जानकारी यहाँ लिखें' : 'Write any important information here' ?></div>
            </div>

            <div class="form-group">
                <label>
                    <span class="field-icon">📅</span>
                    <?php _e('follow_up_date'); ?>
                </label>
                <input type="date" name="follow_up_date" min="<?= date('Y-m-d') ?>">
                <div class="help-text"><?= get_current_language() == 'hi' ? 'कब फॉलो-अप करना है?' : 'When to follow up?' ?></div>
            </div>

            <button type="submit" class="submit-btn">
                <span style="font-size: 24px;">💾</span> 
                <?php _e('save_lead'); ?>
            </button>
        </form>
    </div>

    <?php if (!empty($recentLeads)): ?>
    <div class="recent-leads">
        <h2><?= get_current_language() == 'hi' ? 'हाल की लीड्स' : 'Recent Leads' ?></h2>
        <?php foreach ($recentLeads as $lead): ?>
        <div class="lead-item">
            <div class="lead-name"><?= htmlspecialchars($lead['name']) ?></div>
            <div class="lead-details">
                📱 <?= htmlspecialchars($lead['mobile']) ?>
                <?php if ($lead['email']): ?> | 📧 <?= htmlspecialchars($lead['email']) ?><?php endif; ?>
                <?php if ($lead['follow_up_date']): ?> | 📅 <?= date('d/m/Y', strtotime($lead['follow_up_date'])) ?><?php endif; ?>
            </div>
            <div class="lead-actions">
                <a href="tel:<?= htmlspecialchars($lead['mobile']) ?>">📞 <?= get_current_language() == 'hi' ? 'कॉल करें' : 'Call' ?></a>
                <a href="https://wa.me/91<?= htmlspecialchars($lead['mobile']) ?>" target="_blank">💬 WhatsApp</a>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="/user/crm.php" class="submit-btn" style="display: inline-block; width: auto;">
                <?php _e('view_all_leads'); ?> →
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function validateForm() {
    const name = document.querySelector('input[name="name"]').value.trim();
    const mobile = document.querySelector('input[name="mobile"]').value.trim();
    
    if (!name || !mobile) {
        alert('<?= get_current_language() == 'hi' ? 'कृपया नाम और मोबाइल नंबर दर्ज करें!' : 'Please enter name and mobile number!' ?>');
        return false;
    }
    
    if (mobile.length !== 10 || isNaN(mobile)) {
        alert('<?= get_current_language() == 'hi' ? 'कृपया 10 अंकों का सही मोबाइल नंबर दर्ज करें!' : 'Please enter a valid 10-digit mobile number!' ?>');
        return false;
    }
    
    return true;
}

// Voice assistance for form fields
document.querySelectorAll('input, textarea').forEach(field => {
    field.addEventListener('focus', function() {
        const label = this.parentElement.querySelector('label').textContent;
        const helpText = this.parentElement.querySelector('.help-text')?.textContent || '';
        speak(label + '. ' + helpText);
    });
});

function speak(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
        utterance.rate = 0.9;
        speechSynthesis.speak(utterance);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>