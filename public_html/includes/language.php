<?php
// Language configuration and translation system
session_start();

// Set default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'hi'; // Default to Hindi
}

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['hi', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
}

// Get current language
function get_current_language() {
    return $_SESSION['language'] ?? 'hi';
}

// Translation arrays
$translations = [
    'hi' => [
        // Navigation
        'dashboard' => 'डैशबोर्ड',
        'training' => 'प्रशिक्षण',
        'leads' => 'लीड्स',
        'resources' => 'संसाधन',
        'achievements' => 'उपलब्धियां',
        'profile' => 'प्रोफाइल',
        'logout' => 'लॉग आउट',
        'help' => 'मदद',
        
        // Dashboard
        'welcome' => 'नमस्ते',
        'today_task' => 'आज का कार्य',
        'follow_ups_due' => 'आज के फॉलो-अप्स',
        'key_stats' => 'मुख्य आंकड़े',
        'quick_links' => 'त्वरित लिंक',
        'tasks_complete' => 'पूर्ण कार्य',
        'learning_progress' => 'सीखने की प्रगति',
        'streak' => 'स्ट्रीक',
        'attempts' => 'प्रयास',
        'success' => 'सफलता',
        'no_task_today' => 'आज कोई विशिष्ट कार्य नहीं है',
        'open_tasks' => 'कार्य खोलें',
        'open_crm' => 'CRM खोलें',
        'last_7_days' => 'पिछले 7 दिन की गतिविधि',
        'updates' => 'अपडेट्स',
        'keep_going' => 'बढ़ते रहें!',
        'daily_motivation' => 'रोज़ थोड़ा, जीवन में बड़ा। बस आज का कदम उठाएं!',
        
        // Training Module
        'training_modules' => 'प्रशिक्षण मॉड्यूल',
        'start_learning' => 'सीखना शुरू करें',
        'continue_learning' => 'सीखना जारी रखें',
        'completed' => 'पूर्ण',
        'in_progress' => 'प्रगति में',
        'not_started' => 'शुरू नहीं किया',
        'watch_video' => 'वीडियो देखें',
        'read_notes' => 'नोट्स पढ़ें',
        'take_quiz' => 'क्विज़ लें',
        'download_material' => 'सामग्री डाउनलोड करें',
        
        // Lead Management
        'add_lead' => 'नई लीड जोड़ें',
        'lead_name' => 'नाम',
        'mobile_number' => 'मोबाइल नंबर',
        'email' => 'ईमेल',
        'address' => 'पता',
        'notes' => 'नोट्स',
        'follow_up_date' => 'फॉलो-अप तारीख',
        'status' => 'स्थिति',
        'save_lead' => 'लीड सहेजें',
        'view_all_leads' => 'सभी लीड्स देखें',
        'search_leads' => 'लीड्स खोजें',
        'filter_by_status' => 'स्थिति से फ़िल्टर करें',
        'lead_added_success' => 'लीड सफलतापूर्वक जोड़ी गई!',
        
        // Resources
        'pdf_scripts' => 'PDF स्क्रिप्ट्स',
        'presentations' => 'प्रस्तुतियाँ',
        'marketing_materials' => 'मार्केटिंग सामग्री',
        'download' => 'डाउनलोड',
        'view' => 'देखें',
        'share' => 'शेयर करें',
        
        // Gamification
        'your_rank' => 'आपकी रैंक',
        'points' => 'अंक',
        'badges' => 'बैज',
        'leaderboard' => 'लीडरबोर्ड',
        'achievements' => 'उपलब्धियां',
        'next_rank' => 'अगली रैंक',
        'points_to_next' => 'अगली रैंक तक अंक',
        
        // Common Actions
        'save' => 'सहेजें',
        'cancel' => 'रद्द करें',
        'edit' => 'संपादित करें',
        'delete' => 'हटाएं',
        'search' => 'खोजें',
        'filter' => 'फ़िल्टर',
        'sort' => 'क्रमबद्ध करें',
        'back' => 'वापस',
        'next' => 'अगला',
        'previous' => 'पिछला',
        'submit' => 'जमा करें',
        'close' => 'बंद करें',
        
        // Status Messages
        'loading' => 'लोड हो रहा है...',
        'success_message' => 'सफलतापूर्वक पूर्ण!',
        'error_message' => 'कुछ गलत हुआ!',
        'please_wait' => 'कृपया प्रतीक्षा करें...',
        
        // Help & Support
        'need_help' => 'मदद चाहिए?',
        'watch_tutorial' => 'ट्यूटोरियल देखें',
        'read_guide' => 'गाइड पढ़ें',
        'contact_support' => 'सहायता से संपर्क करें',
        'frequently_asked' => 'अक्सर पूछे जाने वाले प्रश्न'
    ],
    'en' => [
        // Navigation
        'dashboard' => 'Dashboard',
        'training' => 'Training',
        'leads' => 'Leads',
        'resources' => 'Resources',
        'achievements' => 'Achievements',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'help' => 'Help',
        
        // Dashboard
        'welcome' => 'Welcome',
        'today_task' => "Today's Task",
        'follow_ups_due' => 'Follow-ups Due Today',
        'key_stats' => 'Key Stats',
        'quick_links' => 'Quick Links',
        'tasks_complete' => 'Tasks Complete',
        'learning_progress' => 'Learning Progress',
        'streak' => 'Streak',
        'attempts' => 'Attempts',
        'success' => 'Success',
        'no_task_today' => 'No specific task for today',
        'open_tasks' => 'Open Tasks',
        'open_crm' => 'Open CRM',
        'last_7_days' => 'Last 7 Days Activity',
        'updates' => 'Updates',
        'keep_going' => 'Keep Going!',
        'daily_motivation' => 'Small steps daily, big success in life. Just take today\'s step!',
        
        // Training Module
        'training_modules' => 'Training Modules',
        'start_learning' => 'Start Learning',
        'continue_learning' => 'Continue Learning',
        'completed' => 'Completed',
        'in_progress' => 'In Progress',
        'not_started' => 'Not Started',
        'watch_video' => 'Watch Video',
        'read_notes' => 'Read Notes',
        'take_quiz' => 'Take Quiz',
        'download_material' => 'Download Material',
        
        // Lead Management
        'add_lead' => 'Add New Lead',
        'lead_name' => 'Name',
        'mobile_number' => 'Mobile Number',
        'email' => 'Email',
        'address' => 'Address',
        'notes' => 'Notes',
        'follow_up_date' => 'Follow-up Date',
        'status' => 'Status',
        'save_lead' => 'Save Lead',
        'view_all_leads' => 'View All Leads',
        'search_leads' => 'Search Leads',
        'filter_by_status' => 'Filter by Status',
        'lead_added_success' => 'Lead added successfully!',
        
        // Resources
        'pdf_scripts' => 'PDF Scripts',
        'presentations' => 'Presentations',
        'marketing_materials' => 'Marketing Materials',
        'download' => 'Download',
        'view' => 'View',
        'share' => 'Share',
        
        // Gamification
        'your_rank' => 'Your Rank',
        'points' => 'Points',
        'badges' => 'Badges',
        'leaderboard' => 'Leaderboard',
        'achievements' => 'Achievements',
        'next_rank' => 'Next Rank',
        'points_to_next' => 'Points to Next Rank',
        
        // Common Actions
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'search' => 'Search',
        'filter' => 'Filter',
        'sort' => 'Sort',
        'back' => 'Back',
        'next' => 'Next',
        'previous' => 'Previous',
        'submit' => 'Submit',
        'close' => 'Close',
        
        // Status Messages
        'loading' => 'Loading...',
        'success_message' => 'Successfully completed!',
        'error_message' => 'Something went wrong!',
        'please_wait' => 'Please wait...',
        
        // Help & Support
        'need_help' => 'Need Help?',
        'watch_tutorial' => 'Watch Tutorial',
        'read_guide' => 'Read Guide',
        'contact_support' => 'Contact Support',
        'frequently_asked' => 'Frequently Asked Questions'
    ]
];

// Translation function
function __($key, $params = []) {
    global $translations;
    $lang = get_current_language();
    
    $text = $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
    
    // Replace parameters if any
    foreach ($params as $param => $value) {
        $text = str_replace('{' . $param . '}', $value, $text);
    }
    
    return $text;
}

// Echo translation
function _e($key, $params = []) {
    echo __($key, $params);
}