<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/header-enhanced.php';
require_member();

$pdo = get_db();
$userId = (int)$user['id'];

// Sample resources data (in production, this would come from database)
$resources = [
    'scripts' => [
        [
            'id' => 1,
            'title' => 'Cold Calling Script',
            'title_hi' => 'कोल्ड कॉलिंग स्क्रिप्ट',
            'description' => 'Professional script for first-time calls',
            'description_hi' => 'पहली बार कॉल करने के लिए प्रोफेशनल स्क्रिप्ट',
            'file_type' => 'pdf',
            'file_size' => '245 KB',
            'downloads' => 1250,
            'rating' => 4.5,
            'tags' => ['calling', 'sales', 'beginner'],
            'icon' => '📞'
        ],
        [
            'id' => 2,
            'title' => 'WhatsApp Message Templates',
            'title_hi' => 'WhatsApp संदेश टेम्प्लेट',
            'description' => '50+ ready-to-use WhatsApp messages',
            'description_hi' => '50+ तैयार WhatsApp संदेश',
            'file_type' => 'pdf',
            'file_size' => '189 KB',
            'downloads' => 2100,
            'rating' => 4.8,
            'tags' => ['whatsapp', 'messaging', 'templates'],
            'icon' => '💬'
        ],
        [
            'id' => 3,
            'title' => 'Follow-up Email Scripts',
            'title_hi' => 'फॉलो-अप ईमेल स्क्रिप्ट',
            'description' => 'Professional email templates for follow-ups',
            'description_hi' => 'फॉलो-अप के लिए प्रोफेशनल ईमेल टेम्प्लेट',
            'file_type' => 'pdf',
            'file_size' => '156 KB',
            'downloads' => 890,
            'rating' => 4.3,
            'tags' => ['email', 'follow-up', 'professional'],
            'icon' => '📧'
        ]
    ],
    'presentations' => [
        [
            'id' => 4,
            'title' => 'Business Opportunity Presentation',
            'title_hi' => 'व्यावसायिक अवसर प्रस्तुति',
            'description' => 'Complete presentation for prospects',
            'description_hi' => 'संभावनाओं के लिए पूर्ण प्रस्तुति',
            'file_type' => 'pptx',
            'file_size' => '3.2 MB',
            'downloads' => 3450,
            'rating' => 4.9,
            'tags' => ['presentation', 'opportunity', 'business'],
            'icon' => '📊'
        ],
        [
            'id' => 5,
            'title' => 'Product Demo Slides',
            'title_hi' => 'उत्पाद डेमो स्लाइड्स',
            'description' => 'Visual product demonstration slides',
            'description_hi' => 'विज़ुअल उत्पाद प्रदर्शन स्लाइड्स',
            'file_type' => 'pptx',
            'file_size' => '2.8 MB',
            'downloads' => 2200,
            'rating' => 4.6,
            'tags' => ['demo', 'product', 'visual'],
            'icon' => '🎯'
        ]
    ],
    'marketing' => [
        [
            'id' => 6,
            'title' => 'Social Media Graphics Pack',
            'title_hi' => 'सोशल मीडिया ग्राफिक्स पैक',
            'description' => '100+ ready-to-post social media images',
            'description_hi' => '100+ तैयार सोशल मीडिया छवियां',
            'file_type' => 'zip',
            'file_size' => '45.6 MB',
            'downloads' => 5600,
            'rating' => 4.7,
            'tags' => ['social', 'graphics', 'marketing'],
            'icon' => '🎨'
        ],
        [
            'id' => 7,
            'title' => 'Business Cards Template',
            'title_hi' => 'बिज़नेस कार्ड टेम्प्लेट',
            'description' => 'Professional business card designs',
            'description_hi' => 'प्रोफेशनल बिज़नेस कार्ड डिज़ाइन',
            'file_type' => 'pdf',
            'file_size' => '890 KB',
            'downloads' => 1800,
            'rating' => 4.4,
            'tags' => ['business card', 'design', 'professional'],
            'icon' => '💳'
        ]
    ],
    'training' => [
        [
            'id' => 8,
            'title' => 'Complete Training Manual',
            'title_hi' => 'संपूर्ण प्रशिक्षण मैनुअल',
            'description' => 'Step-by-step guide for beginners',
            'description_hi' => 'शुरुआती लोगों के लिए चरण-दर-चरण गाइड',
            'file_type' => 'pdf',
            'file_size' => '5.4 MB',
            'downloads' => 8900,
            'rating' => 4.9,
            'tags' => ['training', 'manual', 'beginner'],
            'icon' => '📚'
        ],
        [
            'id' => 9,
            'title' => 'Success Stories Collection',
            'title_hi' => 'सफलता की कहानियों का संग्रह',
            'description' => 'Inspiring success stories from leaders',
            'description_hi' => 'नेताओं की प्रेरक सफलता की कहानियां',
            'file_type' => 'pdf',
            'file_size' => '2.1 MB',
            'downloads' => 3400,
            'rating' => 4.8,
            'tags' => ['success', 'stories', 'motivation'],
            'icon' => '🌟'
        ]
    ]
];

// Get selected category
$selectedCategory = $_GET['category'] ?? 'all';
?>

<style>
.resources-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.resources-header {
    text-align: center;
    margin-bottom: 40px;
}

.resources-header h1 {
    font-size: 36px;
    color: #1F2937;
    margin-bottom: 10px;
}

.resources-header p {
    font-size: 20px;
    color: #6B7280;
}

.search-bar {
    max-width: 600px;
    margin: 0 auto 40px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 18px 20px 18px 60px;
    font-size: 18px;
    border: 2px solid #E5E7EB;
    border-radius: 15px;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    color: #9CA3AF;
}

.category-tabs {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.category-tab {
    padding: 12px 30px;
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 25px;
    text-decoration: none;
    color: #6B7280;
    font-weight: bold;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-tab:hover {
    border-color: #4F46E5;
    color: #4F46E5;
    transform: translateY(-2px);
}

.category-tab.active {
    background: #4F46E5;
    color: white;
    border-color: #4F46E5;
}

.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.resource-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.resource-header {
    padding: 25px;
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    display: flex;
    align-items: center;
    gap: 20px;
}

.resource-icon {
    font-size: 48px;
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.resource-info h3 {
    font-size: 20px;
    color: #1F2937;
    margin-bottom: 5px;
}

.resource-info p {
    font-size: 14px;
    color: #6B7280;
}

.resource-body {
    padding: 25px;
}

.resource-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    font-size: 14px;
    color: #6B7280;
}

.resource-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.resource-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.tag {
    padding: 5px 12px;
    background: #F3F4F6;
    border-radius: 15px;
    font-size: 12px;
    color: #6B7280;
}

.resource-actions {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 10px;
}

.download-btn {
    padding: 15px;
    background: #4F46E5;
    color: white;
    text-align: center;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.download-btn:hover {
    background: #4338CA;
}

.preview-btn {
    padding: 15px;
    background: #F3F4F6;
    color: #6B7280;
    text-align: center;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-btn:hover {
    background: #E5E7EB;
}

.rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating .stars {
    color: #FBBF24;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state img {
    width: 200px;
    opacity: 0.5;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #6B7280;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 18px;
    color: #9CA3AF;
}

@media (max-width: 768px) {
    .resources-grid {
        grid-template-columns: 1fr;
    }
    
    .category-tabs {
        overflow-x: auto;
        justify-content: flex-start;
        padding-bottom: 10px;
    }
    
    .resource-actions {
        grid-template-columns: 1fr;
    }
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-options {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-label {
    font-weight: bold;
    color: #374151;
}
</style>

<div class="resources-container">
    <!-- Header -->
    <div class="resources-header">
        <h1><span style="font-size: 48px;">📁</span> <?php _e('resources'); ?></h1>
        <p><?= get_current_language() == 'hi' ? 'आपकी सफलता के लिए सभी आवश्यक सामग्री' : 'All essential materials for your success' ?></p>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input type="text" 
               class="search-input" 
               placeholder="<?= get_current_language() == 'hi' ? 'संसाधन खोजें...' : 'Search resources...' ?>"
               onkeyup="searchResources(this.value)">
    </div>

    <!-- Category Tabs -->
    <div class="category-tabs">
        <a href="?category=all" class="category-tab <?= $selectedCategory == 'all' ? 'active' : '' ?>">
            <span>📚</span> <?= get_current_language() == 'hi' ? 'सभी' : 'All' ?>
        </a>
        <a href="?category=scripts" class="category-tab <?= $selectedCategory == 'scripts' ? 'active' : '' ?>">
            <span>📝</span> <?php _e('pdf_scripts'); ?>
        </a>
        <a href="?category=presentations" class="category-tab <?= $selectedCategory == 'presentations' ? 'active' : '' ?>">
            <span>📊</span> <?php _e('presentations'); ?>
        </a>
        <a href="?category=marketing" class="category-tab <?= $selectedCategory == 'marketing' ? 'active' : '' ?>">
            <span>🎨</span> <?php _e('marketing_materials'); ?>
        </a>
        <a href="?category=training" class="category-tab <?= $selectedCategory == 'training' ? 'active' : '' ?>">
            <span>🎓</span> <?= get_current_language() == 'hi' ? 'प्रशिक्षण' : 'Training' ?>
        </a>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-options">
            <span class="filter-label"><?= get_current_language() == 'hi' ? 'क्रमबद्ध करें:' : 'Sort by:' ?></span>
            <select onchange="sortResources(this.value)" style="padding: 8px 15px; border-radius: 8px; border: 1px solid #E5E7EB;">
                <option value="popular"><?= get_current_language() == 'hi' ? 'सबसे लोकप्रिय' : 'Most Popular' ?></option>
                <option value="newest"><?= get_current_language() == 'hi' ? 'नवीनतम' : 'Newest' ?></option>
                <option value="rating"><?= get_current_language() == 'hi' ? 'उच्च रेटिंग' : 'Highest Rated' ?></option>
            </select>
        </div>
    </div>

    <!-- Resources Grid -->
    <div class="resources-grid" id="resourcesGrid">
        <?php 
        $displayResources = $selectedCategory == 'all' ? array_merge(...array_values($resources)) : ($resources[$selectedCategory] ?? []);
        
        foreach ($displayResources as $resource): 
        ?>
        <div class="resource-card" data-category="<?= $selectedCategory ?>" data-title="<?= strtolower($resource['title']) ?>">
            <div class="resource-header">
                <div class="resource-icon"><?= $resource['icon'] ?></div>
                <div class="resource-info">
                    <h3><?= get_current_language() == 'hi' ? $resource['title_hi'] : $resource['title'] ?></h3>
                    <p><?= get_current_language() == 'hi' ? $resource['description_hi'] : $resource['description'] ?></p>
                </div>
            </div>
            
            <div class="resource-body">
                <div class="resource-meta">
                    <span><span style="font-size: 16px;">📄</span> <?= strtoupper($resource['file_type']) ?> • <?= $resource['file_size'] ?></span>
                    <span><span style="font-size: 16px;">⬇️</span> <?= number_format($resource['downloads']) ?> <?= get_current_language() == 'hi' ? 'डाउनलोड' : 'downloads' ?></span>
                </div>
                
                <div class="resource-meta">
                    <div class="rating">
                        <span class="stars"><?= str_repeat('⭐', floor($resource['rating'])) ?></span>
                        <span><?= $resource['rating'] ?></span>
                    </div>
                </div>
                
                <div class="resource-tags">
                    <?php foreach ($resource['tags'] as $tag): ?>
                    <span class="tag">#<?= $tag ?></span>
                    <?php endforeach; ?>
                </div>
                
                <div class="resource-actions">
                    <a href="#" class="download-btn" onclick="downloadResource(<?= $resource['id'] ?>)">
                        <span>⬇️</span> <?php _e('download'); ?>
                    </a>
                    <a href="#" class="preview-btn" onclick="previewResource(<?= $resource['id'] ?>)">
                        <span>👁️</span> <?php _e('view'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($displayResources)): ?>
    <div class="empty-state">
        <h3><?= get_current_language() == 'hi' ? 'कोई संसाधन नहीं मिला' : 'No resources found' ?></h3>
        <p><?= get_current_language() == 'hi' ? 'कृपया दूसरी श्रेणी देखें' : 'Please try another category' ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
// Search functionality
function searchResources(query) {
    const cards = document.querySelectorAll('.resource-card');
    const searchTerm = query.toLowerCase();
    
    cards.forEach(card => {
        const title = card.getAttribute('data-title');
        if (title.includes(searchTerm) || searchTerm === '') {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Voice feedback
    if (query.length > 2) {
        const visibleCards = document.querySelectorAll('.resource-card:not([style*="display: none"])').length;
        const message = '<?= get_current_language() == 'hi' ? 'मिले' : 'Found' ?> ' + visibleCards + ' <?= get_current_language() == 'hi' ? 'संसाधन' : 'resources' ?>';
        // speak(message);
    }
}

// Download resource
function downloadResource(resourceId) {
    // Track download
    const message = '<?= get_current_language() == 'hi' ? 'डाउनलोड शुरू हो रहा है...' : 'Download starting...' ?>';
    alert(message);
    speak(message);
    
    // Award points for download
    setTimeout(() => {
        alert('<?= get_current_language() == 'hi' ? '5 अंक अर्जित किए!' : 'Earned 5 points!' ?>');
    }, 1000);
    
    // In production, this would trigger actual download
    window.location.href = '/user/download.php?id=' + resourceId;
}

// Preview resource
function previewResource(resourceId) {
    // In production, this would open a preview modal or new tab
    window.open('/user/preview.php?id=' + resourceId, '_blank');
}

// Sort resources
function sortResources(sortBy) {
    // In production, this would re-order the resources
    console.log('Sorting by:', sortBy);
}

// Add keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        document.querySelector('.search-input').focus();
    }
});

// Voice assistance
function speak(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
        utterance.rate = 0.9;
        speechSynthesis.speak(utterance);
    }
}

// Animate cards on load
window.addEventListener('load', function() {
    const cards = document.querySelectorAll('.resource-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer-enhanced.php'; ?>