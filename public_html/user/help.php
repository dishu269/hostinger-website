<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/header-enhanced.php';
require_member();

// Video tutorials data
$tutorials = [
    [
        'id' => 1,
        'title' => 'Getting Started Guide',
        'title_hi' => 'शुरुआत कैसे करें',
        'description' => 'Learn the basics of using the platform',
        'description_hi' => 'प्लेटफॉर्म का उपयोग करने की मूल बातें सीखें',
        'duration' => '5:30',
        'video_id' => 'dQw4w9WgXcQ',
        'category' => 'basics',
        'icon' => '🚀'
    ],
    [
        'id' => 2,
        'title' => 'How to Add Leads',
        'title_hi' => 'लीड कैसे जोड़ें',
        'description' => 'Step-by-step guide to managing leads',
        'description_hi' => 'लीड प्रबंधन के लिए चरण-दर-चरण गाइड',
        'duration' => '3:45',
        'video_id' => 'dQw4w9WgXcQ',
        'category' => 'leads',
        'icon' => '👥'
    ],
    [
        'id' => 3,
        'title' => 'Training Module Guide',
        'title_hi' => 'प्रशिक्षण मॉड्यूल गाइड',
        'description' => 'How to complete training modules',
        'description_hi' => 'प्रशिक्षण मॉड्यूल कैसे पूरे करें',
        'duration' => '4:20',
        'video_id' => 'dQw4w9WgXcQ',
        'category' => 'training',
        'icon' => '🎓'
    ],
    [
        'id' => 4,
        'title' => 'Using Resources',
        'title_hi' => 'संसाधनों का उपयोग',
        'description' => 'Download and use marketing materials',
        'description_hi' => 'मार्केटिंग सामग्री डाउनलोड और उपयोग करें',
        'duration' => '2:50',
        'video_id' => 'dQw4w9WgXcQ',
        'category' => 'resources',
        'icon' => '📁'
    ]
];

// FAQs data
$faqs = [
    [
        'question' => 'How do I change the language?',
        'question_hi' => 'मैं भाषा कैसे बदलूं?',
        'answer' => 'Click on the language toggle button (हिं/EN) in the top navigation bar to switch between Hindi and English.',
        'answer_hi' => 'हिंदी और अंग्रेजी के बीच स्विच करने के लिए शीर्ष नेविगेशन बार में भाषा टॉगल बटन (हिं/EN) पर क्लिक करें।',
        'category' => 'general'
    ],
    [
        'question' => 'How do I add a new lead?',
        'question_hi' => 'मैं नई लीड कैसे जोड़ूं?',
        'answer' => 'Go to the Leads section and click on "Add New Lead". Fill in the required information (name and mobile number) and click Save.',
        'answer_hi' => 'लीड्स सेक्शन में जाएं और "नई लीड जोड़ें" पर क्लिक करें। आवश्यक जानकारी (नाम और मोबाइल नंबर) भरें और सेव पर क्लिक करें।',
        'category' => 'leads'
    ],
    [
        'question' => 'How do I earn points and badges?',
        'question_hi' => 'मैं अंक और बैज कैसे कमाऊं?',
        'answer' => 'You earn points by completing tasks, adding leads, finishing training modules, and maintaining daily streaks. Badges are automatically awarded when you reach specific milestones.',
        'answer_hi' => 'आप कार्य पूरा करके, लीड जोड़कर, प्रशिक्षण मॉड्यूल पूरा करके और दैनिक स्ट्रीक बनाए रखकर अंक कमाते हैं। जब आप विशिष्ट मील के पत्थर तक पहुंचते हैं तो बैज स्वचालित रूप से प्रदान किए जाते हैं।',
        'category' => 'gamification'
    ],
    [
        'question' => 'Where can I download marketing materials?',
        'question_hi' => 'मैं मार्केटिंग सामग्री कहां से डाउनलोड कर सकता हूं?',
        'answer' => 'Visit the Resources section where you can find PDFs, presentations, scripts, and marketing materials. Click on the download button for any resource you need.',
        'answer_hi' => 'संसाधन अनुभाग पर जाएं जहां आप PDF, प्रस्तुतियां, स्क्रिप्ट और मार्केटिंग सामग्री पा सकते हैं। आपको जिस भी संसाधन की आवश्यकता हो, उसके लिए डाउनलोड बटन पर क्लिक करें।',
        'category' => 'resources'
    ],
    [
        'question' => 'How do I track my progress?',
        'question_hi' => 'मैं अपनी प्रगति कैसे ट्रैक करूं?',
        'answer' => 'Your dashboard shows all your key metrics including tasks completed, learning progress, current streak, and points. You can also check your achievements page for detailed progress.',
        'answer_hi' => 'आपका डैशबोर्ड आपके सभी प्रमुख मेट्रिक्स दिखाता है जिसमें पूर्ण किए गए कार्य, सीखने की प्रगति, वर्तमान स्ट्रीक और अंक शामिल हैं। विस्तृत प्रगति के लिए आप अपने उपलब्धि पृष्ठ की भी जांच कर सकते हैं।',
        'category' => 'dashboard'
    ]
];

// Get selected category
$selectedCategory = $_GET['category'] ?? 'all';
?>

<style>
.help-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.help-header {
    text-align: center;
    margin-bottom: 40px;
}

.help-header h1 {
    font-size: 36px;
    color: #1F2937;
    margin-bottom: 10px;
}

.help-header p {
    font-size: 20px;
    color: #6B7280;
}

.quick-help-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 50px;
}

.quick-help-card {
    background: white;
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
    cursor: pointer;
}

.quick-help-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.quick-help-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.quick-help-title {
    font-size: 20px;
    font-weight: bold;
    color: #1F2937;
    margin-bottom: 10px;
}

.quick-help-desc {
    font-size: 16px;
    color: #6B7280;
}

.help-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.help-section {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.help-section h2 {
    font-size: 28px;
    color: #1F2937;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.video-tutorials {
    display: grid;
    gap: 20px;
}

.video-card {
    border: 2px solid #E5E7EB;
    border-radius: 15px;
    padding: 20px;
    transition: all 0.3s;
    cursor: pointer;
}

.video-card:hover {
    border-color: #4F46E5;
    background: #F9FAFB;
}

.video-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.video-icon {
    font-size: 36px;
    width: 60px;
    height: 60px;
    background: #EEF2FF;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.video-info h3 {
    font-size: 18px;
    color: #1F2937;
    margin-bottom: 5px;
}

.video-info p {
    font-size: 14px;
    color: #6B7280;
}

.video-duration {
    background: #F3F4F6;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    color: #6B7280;
    font-weight: bold;
}

.play-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #4F46E5;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 15px;
    transition: all 0.3s;
}

.play-button:hover {
    background: #4338CA;
}

.faq-list {
    display: grid;
    gap: 15px;
}

.faq-item {
    border: 2px solid #E5E7EB;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
}

.faq-item.active {
    border-color: #4F46E5;
}

.faq-question {
    padding: 20px;
    background: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.faq-question:hover {
    background: #F9FAFB;
}

.faq-question h4 {
    font-size: 16px;
    color: #1F2937;
    margin: 0;
}

.faq-toggle {
    font-size: 24px;
    transition: transform 0.3s;
}

.faq-item.active .faq-toggle {
    transform: rotate(45deg);
}

.faq-answer {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s;
    background: #F9FAFB;
}

.faq-item.active .faq-answer {
    padding: 20px;
    max-height: 300px;
}

.contact-support {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    margin-top: 40px;
}

.contact-support h2 {
    font-size: 32px;
    margin-bottom: 20px;
}

.contact-methods {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.contact-method {
    padding: 15px 30px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-method:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.video-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.video-modal-content {
    background: white;
    padding: 20px;
    border-radius: 20px;
    width: 90%;
    max-width: 800px;
    position: relative;
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 30px;
    cursor: pointer;
    color: #6B7280;
}

.video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 15px;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

@media (max-width: 768px) {
    .help-sections {
        grid-template-columns: 1fr;
    }
    
    .contact-methods {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<div class="help-container">
    <!-- Header -->
    <div class="help-header">
        <h1><span style="font-size: 48px;">❓</span> <?php _e('need_help'); ?></h1>
        <p><?= get_current_language() == 'hi' ? 'हम यहाँ आपकी सहायता के लिए हैं' : 'We are here to help you' ?></p>
    </div>

    <!-- Quick Help Cards -->
    <div class="quick-help-cards">
        <div class="quick-help-card" onclick="scrollToSection('videos')">
            <div class="quick-help-icon">🎥</div>
            <div class="quick-help-title"><?php _e('watch_tutorial'); ?></div>
            <div class="quick-help-desc"><?= get_current_language() == 'hi' ? 'वीडियो गाइड देखें' : 'Watch video guides' ?></div>
        </div>
        
        <div class="quick-help-card" onclick="scrollToSection('faqs')">
            <div class="quick-help-icon">❓</div>
            <div class="quick-help-title"><?php _e('frequently_asked'); ?></div>
            <div class="quick-help-desc"><?= get_current_language() == 'hi' ? 'सामान्य प्रश्न' : 'Common questions' ?></div>
        </div>
        
        <div class="quick-help-card" onclick="scrollToSection('contact')">
            <div class="quick-help-icon">💬</div>
            <div class="quick-help-title"><?php _e('contact_support'); ?></div>
            <div class="quick-help-desc"><?= get_current_language() == 'hi' ? 'सीधे संपर्क करें' : 'Direct contact' ?></div>
        </div>
        
        <div class="quick-help-card" onclick="location.href='/user/resources-enhanced.php'">
            <div class="quick-help-icon">📚</div>
            <div class="quick-help-title"><?php _e('read_guide'); ?></div>
            <div class="quick-help-desc"><?= get_current_language() == 'hi' ? 'गाइड पढ़ें' : 'Read guides' ?></div>
        </div>
    </div>

    <!-- Help Sections -->
    <div class="help-sections">
        <!-- Video Tutorials -->
        <div class="help-section" id="videos">
            <h2><span>🎥</span> <?= get_current_language() == 'hi' ? 'वीडियो ट्यूटोरियल' : 'Video Tutorials' ?></h2>
            <div class="video-tutorials">
                <?php foreach ($tutorials as $tutorial): ?>
                <div class="video-card" onclick="playVideo('<?= $tutorial['video_id'] ?>', '<?= get_current_language() == 'hi' ? $tutorial['title_hi'] : $tutorial['title'] ?>')">
                    <div class="video-header">
                        <div class="video-icon"><?= $tutorial['icon'] ?></div>
                        <div class="video-info">
                            <h3><?= get_current_language() == 'hi' ? $tutorial['title_hi'] : $tutorial['title'] ?></h3>
                            <p><?= get_current_language() == 'hi' ? $tutorial['description_hi'] : $tutorial['description'] ?></p>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="video-duration">⏱️ <?= $tutorial['duration'] ?></span>
                        <a href="#" class="play-button" onclick="event.stopPropagation(); playVideo('<?= $tutorial['video_id'] ?>', '<?= get_current_language() == 'hi' ? $tutorial['title_hi'] : $tutorial['title'] ?>')">
                            ▶️ <?= get_current_language() == 'hi' ? 'देखें' : 'Watch' ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- FAQs -->
        <div class="help-section" id="faqs">
            <h2><span>❓</span> <?php _e('frequently_asked'); ?></h2>
            <div class="faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                <div class="faq-item" id="faq-<?= $index ?>">
                    <div class="faq-question" onclick="toggleFAQ(<?= $index ?>)">
                        <h4><?= get_current_language() == 'hi' ? $faq['question_hi'] : $faq['question'] ?></h4>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p><?= get_current_language() == 'hi' ? $faq['answer_hi'] : $faq['answer'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="contact-support" id="contact">
        <h2><?= get_current_language() == 'hi' ? 'अभी भी मदद चाहिए?' : 'Still Need Help?' ?></h2>
        <p><?= get_current_language() == 'hi' ? 'हमारी टीम आपकी सहायता के लिए तैयार है' : 'Our team is ready to assist you' ?></p>
        
        <div class="contact-methods">
            <a href="tel:+911234567890" class="contact-method">
                <span style="font-size: 24px;">📞</span>
                <?= get_current_language() == 'hi' ? 'कॉल करें' : 'Call Us' ?>
                <span>+91 123-456-7890</span>
            </a>
            
            <a href="https://wa.me/911234567890?text=<?= urlencode(get_current_language() == 'hi' ? 'मुझे मदद चाहिए' : 'I need help') ?>" target="_blank" class="contact-method">
                <span style="font-size: 24px;">💬</span>
                WhatsApp
            </a>
            
            <a href="mailto:support@example.com" class="contact-method">
                <span style="font-size: 24px;">📧</span>
                Email
            </a>
        </div>
        
        <p style="margin-top: 30px; opacity: 0.9;">
            <?= get_current_language() == 'hi' ? 'समय: सुबह 9 बजे - रात 9 बजे (सभी दिन)' : 'Timing: 9 AM - 9 PM (All days)' ?>
        </p>
    </div>
</div>

<!-- Video Modal -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-content">
        <span class="close-modal" onclick="closeVideoModal()">✕</span>
        <h3 id="videoTitle" style="margin-bottom: 20px;"></h3>
        <div class="video-container">
            <iframe id="videoFrame" src="" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
// Toggle FAQ
function toggleFAQ(index) {
    const faqItem = document.getElementById('faq-' + index);
    const wasActive = faqItem.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't active
    if (!wasActive) {
        faqItem.classList.add('active');
        
        // Voice feedback
        const question = faqItem.querySelector('h4').textContent;
        const answer = faqItem.querySelector('.faq-answer p').textContent;
        speak(question + '. ' + answer);
    }
}

// Play video
function playVideo(videoId, title) {
    const modal = document.getElementById('videoModal');
    const videoFrame = document.getElementById('videoFrame');
    const videoTitle = document.getElementById('videoTitle');
    
    videoTitle.textContent = title;
    videoFrame.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    modal.style.display = 'flex';
    
    speak('<?= get_current_language() == 'hi' ? 'वीडियो चल रहा है' : 'Playing video' ?> ' + title);
}

// Close video modal
function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const videoFrame = document.getElementById('videoFrame');
    
    modal.style.display = 'none';
    videoFrame.src = '';
}

// Scroll to section
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Voice assistance
function speak(text) {
    if ('speechSynthesis' in window) {
        speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
        utterance.rate = 0.9;
        speechSynthesis.speak(utterance);
    }
}

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeVideoModal();
    }
});

// Close modal on outside click
document.getElementById('videoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVideoModal();
    }
});

// Add keyboard navigation for FAQs
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const activeFaq = document.querySelector('.faq-item.active');
        if (activeFaq) {
            e.preventDefault();
            const allFaqs = Array.from(document.querySelectorAll('.faq-item'));
            const currentIndex = allFaqs.indexOf(activeFaq);
            let nextIndex;
            
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % allFaqs.length;
            } else {
                nextIndex = currentIndex === 0 ? allFaqs.length - 1 : currentIndex - 1;
            }
            
            toggleFAQ(nextIndex);
            allFaqs[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer-enhanced.php'; ?>