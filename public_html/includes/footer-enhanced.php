        </div>
    </main>

    <footer class="enhanced-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4><?php _e('need_help'); ?></h4>
                <p><?= get_current_language() == 'hi' ? 'हमारी टीम आपकी मदद के लिए तैयार है' : 'Our team is ready to help you' ?></p>
                <div class="help-buttons">
                    <a href="tel:+911234567890" class="help-btn">
                        <span>📞</span> 
                        <?= get_current_language() == 'hi' ? 'कॉल करें' : 'Call Us' ?>
                    </a>
                    <a href="https://wa.me/911234567890" target="_blank" class="help-btn">
                        <span>💬</span> 
                        WhatsApp
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4><?= get_current_language() == 'hi' ? 'त्वरित लिंक' : 'Quick Links' ?></h4>
                <ul class="footer-links">
                    <li><a href="/user/training-enhanced.php"><?php _e('training'); ?></a></li>
                    <li><a href="/user/resources.php"><?php _e('resources'); ?></a></li>
                    <li><a href="/user/help.php"><?php _e('help'); ?></a></li>
                    <li><a href="/user/community.php">Community</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4><?= get_current_language() == 'hi' ? 'हमसे जुड़ें' : 'Connect With Us' ?></h4>
                <div class="social-links">
                    <a href="#" aria-label="Facebook">📘</a>
                    <a href="#" aria-label="YouTube">📺</a>
                    <a href="#" aria-label="Instagram">📷</a>
                    <a href="#" aria-label="Telegram">✈️</a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= SITE_BRAND ?>. <?= get_current_language() == 'hi' ? 'सर्वाधिकार सुरक्षित' : 'All rights reserved' ?>.</p>
        </div>
    </footer>

    <style>
    .enhanced-footer {
        background: #1F2937;
        color: white;
        padding: 40px 0 20px;
        margin-top: 60px;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
    }

    .footer-section h4 {
        font-size: 20px;
        margin-bottom: 15px;
        color: #F3F4F6;
    }

    .footer-section p {
        color: #9CA3AF;
        margin-bottom: 20px;
    }

    .help-buttons {
        display: flex;
        gap: 15px;
    }

    .help-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #374151;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: bold;
    }

    .help-btn:hover {
        background: #4B5563;
        transform: translateY(-2px);
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 10px;
    }

    .footer-links a {
        color: #9CA3AF;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-links a:hover {
        color: #F3F4F6;
    }

    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: #374151;
        border-radius: 50%;
        font-size: 20px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .social-links a:hover {
        background: #4B5563;
        transform: scale(1.1);
    }

    .footer-bottom {
        text-align: center;
        padding: 20px;
        border-top: 1px solid #374151;
        margin-top: 40px;
        color: #9CA3AF;
    }

    /* Accessibility Button */
    .accessibility-btn {
        position: fixed;
        bottom: 30px;
        left: 30px;
        width: 60px;
        height: 60px;
        background: #4F46E5;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(79, 70, 229, 0.3);
        transition: all 0.3s;
        z-index: 997;
    }

    .accessibility-btn:hover {
        transform: scale(1.1);
    }

    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .help-buttons {
            justify-content: center;
        }

        .social-links {
            justify-content: center;
        }
    }
    </style>

    <!-- Accessibility Button -->
    <button class="accessibility-btn" onclick="toggleAccessibility()" aria-label="Accessibility Options">
        ♿
    </button>

    <!-- Accessibility Modal -->
    <div id="accessibilityModal" class="accessibility-modal" style="display: none;">
        <div class="modal-content">
            <h3><?= get_current_language() == 'hi' ? 'सुगमता विकल्प' : 'Accessibility Options' ?></h3>
            
            <div class="accessibility-options">
                <button onclick="changeFontSize('increase')">
                    <span>A+</span>
                    <?= get_current_language() == 'hi' ? 'फ़ॉन्ट बढ़ाएं' : 'Increase Font' ?>
                </button>
                
                <button onclick="changeFontSize('decrease')">
                    <span>A-</span>
                    <?= get_current_language() == 'hi' ? 'फ़ॉन्ट घटाएं' : 'Decrease Font' ?>
                </button>
                
                <button onclick="toggleHighContrast()">
                    <span>🎨</span>
                    <?= get_current_language() == 'hi' ? 'हाई कंट्रास्ट' : 'High Contrast' ?>
                </button>
                
                <button onclick="toggleVoiceAssist()">
                    <span>🔊</span>
                    <?= get_current_language() == 'hi' ? 'आवाज सहायता' : 'Voice Assist' ?>
                </button>
            </div>
            
            <button class="close-modal" onclick="toggleAccessibility()">✕</button>
        </div>
    </div>

    <style>
    .accessibility-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 20px;
        max-width: 400px;
        width: 90%;
        position: relative;
    }

    .modal-content h3 {
        margin-bottom: 20px;
        text-align: center;
    }

    .accessibility-options {
        display: grid;
        gap: 15px;
    }

    .accessibility-options button {
        padding: 15px;
        border: 2px solid #E5E7EB;
        background: white;
        border-radius: 10px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .accessibility-options button:hover {
        background: #F3F4F6;
        border-color: #4F46E5;
    }

    .accessibility-options button span {
        font-size: 24px;
        width: 40px;
    }

    .close-modal {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6B7280;
    }

    /* High Contrast Mode */
    body.high-contrast {
        background: black !important;
        color: white !important;
    }

    body.high-contrast * {
        background: black !important;
        color: white !important;
        border-color: white !important;
    }

    body.high-contrast a {
        color: yellow !important;
    }

    body.high-contrast button,
    body.high-contrast .btn {
        background: white !important;
        color: black !important;
    }
    </style>

    <script>
    // Font size adjustment
    let currentFontSize = 100;

    function changeFontSize(action) {
        if (action === 'increase' && currentFontSize < 150) {
            currentFontSize += 10;
        } else if (action === 'decrease' && currentFontSize > 80) {
            currentFontSize -= 10;
        }
        
        document.documentElement.style.fontSize = currentFontSize + '%';
        localStorage.setItem('fontSize', currentFontSize);
    }

    // High contrast toggle
    function toggleHighContrast() {
        document.body.classList.toggle('high-contrast');
        const isHighContrast = document.body.classList.contains('high-contrast');
        localStorage.setItem('highContrast', isHighContrast);
    }

    // Voice assist toggle
    let voiceAssistEnabled = false;

    function toggleVoiceAssist() {
        voiceAssistEnabled = !voiceAssistEnabled;
        localStorage.setItem('voiceAssist', voiceAssistEnabled);
        
        if (voiceAssistEnabled) {
            speak('<?= get_current_language() == 'hi' ? 'आवाज सहायता सक्रिय' : 'Voice assist enabled' ?>');
            enableVoiceAssist();
        } else {
            speak('<?= get_current_language() == 'hi' ? 'आवाज सहायता निष्क्रिय' : 'Voice assist disabled' ?>');
        }
    }

    function enableVoiceAssist() {
        // Add voice assistance to interactive elements
        document.querySelectorAll('a, button, input, textarea').forEach(element => {
            element.addEventListener('focus', function() {
                if (voiceAssistEnabled) {
                    const text = this.textContent || this.getAttribute('aria-label') || this.getAttribute('placeholder') || 'Interactive element';
                    speak(text);
                }
            });
        });
    }

    // Accessibility modal toggle
    function toggleAccessibility() {
        const modal = document.getElementById('accessibilityModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }

    // Speak function
    function speak(text) {
        if ('speechSynthesis' in window) {
            speechSynthesis.cancel(); // Cancel any ongoing speech
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = '<?= get_current_language() == 'hi' ? 'hi-IN' : 'en-US' ?>';
            utterance.rate = 0.9;
            speechSynthesis.speak(utterance);
        }
    }

    // Load saved preferences
    window.addEventListener('load', function() {
        // Font size
        const savedFontSize = localStorage.getItem('fontSize');
        if (savedFontSize) {
            currentFontSize = parseInt(savedFontSize);
            document.documentElement.style.fontSize = currentFontSize + '%';
        }
        
        // High contrast
        const savedHighContrast = localStorage.getItem('highContrast') === 'true';
        if (savedHighContrast) {
            document.body.classList.add('high-contrast');
        }
        
        // Voice assist
        voiceAssistEnabled = localStorage.getItem('voiceAssist') === 'true';
        if (voiceAssistEnabled) {
            enableVoiceAssist();
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + A for accessibility options
        if (e.altKey && e.key === 'a') {
            toggleAccessibility();
        }
        
        // Alt + Plus for increase font
        if (e.altKey && e.key === '+') {
            changeFontSize('increase');
        }
        
        // Alt + Minus for decrease font
        if (e.altKey && e.key === '-') {
            changeFontSize('decrease');
        }
    });
    </script>

    <!-- Scripts -->
    <script src="/assets/js/feather.min.js"></script>
    <script src="/assets/js/chart.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Initialize Feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    </script>
</body>
</html>