// Enhanced Accessibility and Voice Guidance System

class AccessibilityManager {
    constructor() {
        this.voiceEnabled = localStorage.getItem('voiceAssist') === 'true';
        this.speechRate = parseFloat(localStorage.getItem('speechRate') || '0.9');
        this.currentLanguage = this.getCurrentLanguage();
        this.init();
    }

    init() {
        // Initialize voice synthesis
        if ('speechSynthesis' in window) {
            this.setupVoiceGuidance();
        }

        // Setup keyboard navigation
        this.setupKeyboardNavigation();

        // Setup tooltips
        this.setupTooltips();

        // Setup screen reader announcements
        this.setupAriaLive();

        // Setup focus management
        this.setupFocusManagement();
    }

    getCurrentLanguage() {
        return document.documentElement.lang || 'en';
    }

    // Voice Guidance Functions
    speak(text, priority = 'polite') {
        if (!this.voiceEnabled || !('speechSynthesis' in window)) return;

        // Cancel current speech if priority is high
        if (priority === 'assertive') {
            window.speechSynthesis.cancel();
        }

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = this.currentLanguage === 'hi' ? 'hi-IN' : 'en-US';
        utterance.rate = this.speechRate;
        utterance.pitch = 1;
        utterance.volume = 0.8;

        window.speechSynthesis.speak(utterance);
    }

    setupVoiceGuidance() {
        // Page load announcement
        window.addEventListener('load', () => {
            const pageTitle = document.title;
            const welcomeMessage = this.currentLanguage === 'hi' 
                ? `आप ${pageTitle} पेज पर हैं` 
                : `You are on ${pageTitle} page`;
            
            setTimeout(() => {
                if (this.voiceEnabled) {
                    this.speak(welcomeMessage);
                }
            }, 500);
        });

        // Form field focus
        document.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('focus', (e) => {
                if (!this.voiceEnabled) return;

                const label = this.getFieldLabel(field);
                const helpText = field.getAttribute('aria-describedby') 
                    ? document.getElementById(field.getAttribute('aria-describedby'))?.textContent 
                    : '';
                const required = field.hasAttribute('required') 
                    ? (this.currentLanguage === 'hi' ? 'आवश्यक' : 'required') 
                    : '';
                
                const announcement = `${label} ${required}. ${helpText}`;
                this.speak(announcement);
            });
        });

        // Button and link hover/focus
        document.querySelectorAll('button, a, .clickable').forEach(element => {
            element.addEventListener('focus', () => {
                if (!this.voiceEnabled) return;
                
                const text = element.getAttribute('aria-label') 
                    || element.textContent.trim() 
                    || element.title;
                    
                if (text) {
                    this.speak(text);
                }
            });
        });

        // Success/Error message announcements
        this.observeMessages();
    }

    getFieldLabel(field) {
        // Try to find associated label
        const labelFor = field.id ? document.querySelector(`label[for="${field.id}"]`) : null;
        if (labelFor) return labelFor.textContent.trim();

        // Try to find parent label
        const parentLabel = field.closest('label');
        if (parentLabel) return parentLabel.textContent.trim();

        // Use placeholder or name as fallback
        return field.placeholder || field.name || 'Input field';
    }

    observeMessages() {
        // Observe DOM for success/error messages
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList.contains('success-message') || 
                            node.classList.contains('error-message') ||
                            node.classList.contains('alert')) {
                            this.speak(node.textContent.trim(), 'assertive');
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Keyboard Navigation
    setupKeyboardNavigation() {
        // Skip to main content
        this.createSkipLink();

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Alt + H for help
            if (e.altKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = '/user/help.php';
            }

            // Alt + D for dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = '/user/dashboard-enhanced.php';
            }

            // Alt + L for language toggle
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                this.toggleLanguage();
            }

            // Alt + V for voice toggle
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                this.toggleVoice();
            }

            // Escape to close modals
            if (e.key === 'Escape') {
                this.closeActiveModal();
            }
        });

        // Tab trap for modals
        this.setupModalTabTrap();
    }

    createSkipLink() {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = this.currentLanguage === 'hi' 
            ? 'मुख्य सामग्री पर जाएं' 
            : 'Skip to main content';
        
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 0;
            background: #4F46E5;
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 0 0 4px 0;
            z-index: 10000;
        `;

        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '0';
        });

        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });

        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    setupModalTabTrap() {
        // Find all modals
        document.querySelectorAll('.modal, [role="dialog"]').forEach(modal => {
            const focusableElements = modal.querySelectorAll(
                'a[href], button, textarea, input, select, [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length === 0) return;

            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];

            modal.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            });
        });
    }

    // Tooltips
    setupTooltips() {
        // Add tooltips to all elements with title attribute
        document.querySelectorAll('[title]').forEach(element => {
            const tooltipText = element.getAttribute('title');
            element.removeAttribute('title'); // Remove default tooltip
            
            element.setAttribute('data-tooltip', tooltipText);
            element.classList.add('has-tooltip');

            // Voice announcement on hover
            element.addEventListener('mouseenter', () => {
                if (this.voiceEnabled) {
                    this.speak(tooltipText);
                }
            });
        });

        // Enhanced tooltips for form fields
        document.querySelectorAll('input, textarea, select').forEach(field => {
            const helpText = field.parentElement.querySelector('.help-text');
            if (helpText) {
                field.setAttribute('aria-describedby', `help-${field.name}`);
                helpText.id = `help-${field.name}`;
            }
        });
    }

    // ARIA Live Regions
    setupAriaLive() {
        // Create live region for announcements
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        liveRegion.style.cssText = `
            position: absolute;
            left: -10000px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        `;
        document.body.appendChild(liveRegion);

        // Method to announce messages
        window.announceMessage = (message, priority = 'polite') => {
            liveRegion.setAttribute('aria-live', priority);
            liveRegion.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        };
    }

    // Focus Management
    setupFocusManagement() {
        // Save and restore focus for modals
        let previousFocus = null;

        document.addEventListener('focusin', (e) => {
            if (e.target.closest('.modal, [role="dialog"]') && !previousFocus) {
                previousFocus = document.activeElement;
            }
        });

        // Restore focus when modal closes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'style' &&
                    mutation.target.classList.contains('modal')) {
                    
                    const modal = mutation.target;
                    if (modal.style.display === 'none' && previousFocus) {
                        previousFocus.focus();
                        previousFocus = null;
                    }
                }
            });
        });

        document.querySelectorAll('.modal').forEach(modal => {
            observer.observe(modal, { attributes: true });
        });
    }

    // Utility Methods
    toggleLanguage() {
        const currentLang = this.currentLanguage;
        const newLang = currentLang === 'hi' ? 'en' : 'hi';
        window.location.href = `?lang=${newLang}`;
    }

    toggleVoice() {
        this.voiceEnabled = !this.voiceEnabled;
        localStorage.setItem('voiceAssist', this.voiceEnabled);
        
        const message = this.voiceEnabled 
            ? (this.currentLanguage === 'hi' ? 'आवाज सहायता सक्रिय' : 'Voice assist enabled')
            : (this.currentLanguage === 'hi' ? 'आवाज सहायता निष्क्रिय' : 'Voice assist disabled');
        
        this.speak(message, 'assertive');
        window.announceMessage(message, 'assertive');
    }

    closeActiveModal() {
        const activeModal = document.querySelector('.modal[style*="display: block"], .modal[style*="display: flex"]');
        if (activeModal) {
            activeModal.style.display = 'none';
            const closeBtn = activeModal.querySelector('.close-modal, [data-dismiss="modal"]');
            if (closeBtn) closeBtn.click();
        }
    }

    // Public API
    setVoiceRate(rate) {
        this.speechRate = Math.max(0.5, Math.min(2, rate));
        localStorage.setItem('speechRate', this.speechRate);
    }

    announcePageChange(pageName) {
        const message = this.currentLanguage === 'hi' 
            ? `${pageName} पेज लोड हो गया है` 
            : `${pageName} page has loaded`;
        
        this.speak(message, 'polite');
        window.announceMessage(message);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.accessibilityManager = new AccessibilityManager();
});

// Export for use in other scripts
window.AccessibilityManager = AccessibilityManager;