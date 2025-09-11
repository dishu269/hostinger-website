// Enhanced Main JS for Asclepius Wellness App
(function () {
  'use strict';

  // Form validation helper
  function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
      const value = input.value.trim();
      const errorEl = input.parentElement.querySelector('.form-error');
      
      // Reset validation state
      input.classList.remove('error', 'success');
      if (errorEl) errorEl.style.display = 'none';
      
      // Check if empty
      if (!value) {
        input.classList.add('error');
        if (errorEl) {
          errorEl.textContent = 'This field is required';
          errorEl.style.display = 'block';
        }
        isValid = false;
        return;
      }
      
      // Email validation
      if (input.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          input.classList.add('error');
          if (errorEl) {
            errorEl.textContent = 'Please enter a valid email address';
            errorEl.style.display = 'block';
          }
          isValid = false;
          return;
        }
      }
      
      // Password validation
      if (input.type === 'password' && input.name === 'password') {
        if (value.length < 8) {
          input.classList.add('error');
          if (errorEl) {
            errorEl.textContent = 'Password must be at least 8 characters';
            errorEl.style.display = 'block';
          }
          isValid = false;
          return;
        }
      }
      
      // Phone validation
      if (input.type === 'tel' || input.name === 'mobile') {
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(value) || value.replace(/\D/g, '').length < 10) {
          input.classList.add('error');
          if (errorEl) {
            errorEl.textContent = 'Please enter a valid phone number';
            errorEl.style.display = 'block';
          }
          isValid = false;
          return;
        }
      }
      
      // If all validations pass
      input.classList.add('success');
    });
    
    return isValid;
  }

  // Enhanced alert system
  function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.cssText = 'opacity: 0; transition: opacity 0.3s ease;';
    alert.innerHTML = `
      <span>${message}</span>
      <button class="alert-close" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer; margin-left: auto;">&times;</button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Trigger animation
    requestAnimationFrame(() => {
      alert.style.opacity = '1';
    });
    
    // Auto dismiss after 5 seconds
    const timeout = setTimeout(() => {
      dismissAlert(alert);
    }, 5000);
    
    // Manual dismiss
    alert.querySelector('.alert-close').addEventListener('click', () => {
      clearTimeout(timeout);
      dismissAlert(alert);
    });
  }
  
  function dismissAlert(alert) {
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 300);
  }
  
  function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 1000; max-width: 400px;';
    document.body.appendChild(container);
    return container;
  }
  
  // Make showAlert globally available
  window.showAlert = showAlert;

  // --- Page Loader ---
  window.addEventListener('load', () => {
    const loader = document.getElementById('loader');
    if (loader) {
      // Wait a moment for assets to render, then fade out
      setTimeout(() => loader.classList.add('hidden'), 200);
    }

    // Feather Icons
    if (typeof feather !== 'undefined') {
      feather.replace();
    }
  });

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // --- Theme Toggler ---
  const themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    // Set initial icon
    themeToggle.textContent = localStorage.getItem('theme') === 'dark' ? '☀️' : '🌙';

    themeToggle.addEventListener('click', () => {
      const isDark = document.body.classList.toggle('dark-mode');
      if (isDark) {
        localStorage.setItem('theme', 'dark');
        themeToggle.textContent = '☀️';
      } else {
        localStorage.setItem('theme', 'light');
        themeToggle.textContent = '🌙';
      }
    });
  }

  // --- Mobile Menu ---
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');

  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', () => {
      const isActive = mobileMenuButton.classList.toggle('is-active');
      mobileMenu.classList.toggle('is-active');
      mobileMenuButton.setAttribute('aria-expanded', isActive);
      // Prevent scrolling when menu is open
      document.body.style.overflow = isActive ? 'hidden' : '';
    });
  }

  // Progressive enhancement: register service worker
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
  }

  // Intersection fade-ins
  const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      if (entry.isIntersecting) {
        entry.target.classList.add('fade-in');
      }
    }
  }, { threshold: 0.1 });

  document.querySelectorAll('.card, .kpi, .motivation').forEach((el, index) => {
    // Add a staggered delay, but only for elements that aren't already visible
    const rect = el.getBoundingClientRect();
    if (rect.top > window.innerHeight) {
      el.style.animationDelay = `${index * 50}ms`;
    }
    observer.observe(el);
  });

  // Voice input helper for fields with [data-voice]
  function attachVoice(el) {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) return;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = '🎤';
    btn.style.marginLeft = '8px';
    btn.className = 'btn btn-outline';
    el.insertAdjacentElement('afterend', btn);
    btn.addEventListener('click', () => {
      const Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
      const rec = new Rec();
      rec.lang = 'en-IN';
      rec.onresult = (e) => {
        const txt = e.results[0][0].transcript;
        el.value = (el.value ? el.value + ' ' : '') + txt;
      };
      rec.start();
    });
  }
  document.querySelectorAll('[data-voice]').forEach(attachVoice);

  // Offline CRM queue
  const queueKey = 'offline_leads';
  async function trySyncQueue() {
    const raw = localStorage.getItem(queueKey);
    if (!raw) return;
    const queue = JSON.parse(raw);
    if (!Array.isArray(queue) || queue.length === 0) return;
    
    showAlert('Syncing offline leads...', 'info');
    let syncedCount = 0;
    
    for (let i = 0; i < queue.length; i++) {
      try {
        const res = await fetch('/user/lead_save.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify(queue[i]),
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('sync failed');
        syncedCount++;
        // Remove synced item from queue
        queue.splice(i, 1);
        i--;
      } catch (_) {
        // Keep remaining items in queue
        break;
      }
    }
    
    if (syncedCount > 0) {
      showAlert(`Synced ${syncedCount} offline lead(s) successfully!`, 'success');
    }
    
    // Update queue
    if (queue.length > 0) {
      localStorage.setItem(queueKey, JSON.stringify(queue));
    } else {
      localStorage.removeItem(queueKey);
    }
  }
  
  window.addEventListener('online', trySyncQueue);
  trySyncQueue();

  // Lead form AJAX and offline fallback
  const leadForm = document.querySelector('#lead-form');
  if (leadForm) {
    // Add form validation elements
    leadForm.querySelectorAll('input, select, textarea').forEach(input => {
      const formGroup = input.parentElement;
      if (!formGroup.querySelector('.form-error')) {
        const errorEl = document.createElement('div');
        errorEl.className = 'form-error';
        formGroup.appendChild(errorEl);
      }
    });
    
    leadForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.currentTarget;
      
      // Validate form before submission
      if (!validateForm(form)) {
        showAlert('Please fix the errors in the form', 'error');
        return;
      }
      
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());
      const submitButton = form.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;

      submitButton.disabled = true;
      submitButton.textContent = 'Saving...';
      submitButton.classList.add('btn-loading');

      if (navigator.onLine) {
        // Online: send via AJAX
        try {
          const response = await fetch('/user/lead_save.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': data.csrf_token,
            },
            body: JSON.stringify(data),
          });
          const result = await response.json();
          if (result.ok) {
            showAlert('Lead saved successfully!', 'success');
            form.reset();
            // Reset validation states
            form.querySelectorAll('.error, .success').forEach(el => {
              el.classList.remove('error', 'success');
            });
            form.querySelectorAll('.form-error').forEach(el => {
              el.style.display = 'none';
            });
          } else {
            showAlert('Error: ' + (result.error || 'Could not save lead.'), 'error');
          }
        } catch (error) {
          showAlert('Network error occurred. Your lead has been saved offline.', 'warning');
          saveLeadOffline(data);
        } finally {
          submitButton.disabled = false;
          submitButton.textContent = originalButtonText;
          submitButton.classList.remove('btn-loading');
        }
      } else {
        // Offline: save to localStorage
        saveLeadOffline(data);
        showAlert('Saved offline. Will sync when online.', 'info');
        form.reset();
        // Reset validation states
        form.querySelectorAll('.error, .success').forEach(el => {
          el.classList.remove('error', 'success');
        });
        form.querySelectorAll('.form-error').forEach(el => {
          el.style.display = 'none';
        });
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        submitButton.classList.remove('btn-loading');
      }
    });

    function saveLeadOffline(data) {
        const all = JSON.parse(localStorage.getItem(queueKey) || '[]');
        all.push(data);
        localStorage.setItem(queueKey, JSON.stringify(all));
    }
  }

  // Resend verification: works under CSP since this is external JS
  document.addEventListener('click', function (e) {
    const a = e.target.closest('[data-resend]');
    if (!a) return;
    e.preventDefault();
    const emailInput = document.querySelector('#email, input[name="email"]');
    const form = document.getElementById('resendForm');
    if (!form) return;
    form.querySelector('input[name="email"]').value = (emailInput?.value || '').trim();
    form.submit();
  });

  // Copy helper: any element with data-copy
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    const text = btn.getAttribute('data-copy') || '';
    try {
      await navigator.clipboard.writeText(text);
      showAlert('Copied to clipboard!', 'success');
    } catch (err) {
      showAlert('Failed to copy to clipboard', 'error');
    }
  });

  // WhatsApp helper: data-wa opens wa.me with provided text
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-wa]');
    if (!btn) return;
    const text = encodeURIComponent(btn.getAttribute('data-wa') || '');
    window.open('https://wa.me/?text=' + text, '_blank');
  });

  // Practice counter stored locally
  const counterEl = document.getElementById('practice-count');
  if (counterEl) {
    const key = 'training_practice_count';
    const update = () => { counterEl.textContent = String(parseInt(localStorage.getItem(key) || '0', 10)); };
    update();
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-practice-increment]');
      if (!btn) return;
      const n = parseInt(localStorage.getItem(key) || '0', 10) + 1;
      localStorage.setItem(key, String(n));
      update();
    });
  }

  // Dashboard Chart with enhanced styling
  const weeklyActivityCanvas = document.getElementById('weeklyActivityChart');
  if (weeklyActivityCanvas) {
    const dataEl = document.getElementById('weeklyActivityData');
    try {
      const chartData = JSON.parse(dataEl.textContent);
      new Chart(weeklyActivityCanvas, {
        type: 'bar',
        data: {
          labels: chartData.labels,
          datasets: chartData.datasets.map((dataset, index) => ({
            ...dataset,
            backgroundColor: index === 0 ? 'rgba(59, 130, 246, 0.8)' : 'rgba(16, 185, 129, 0.8)',
            borderColor: index === 0 ? 'rgb(59, 130, 246)' : 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 6,
          })),
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            delay: (context) => {
              let delay = 0;
              if (context.type === 'data' && context.mode === 'default') {
                delay = context.dataIndex * 100 + context.datasetIndex * 100;
              }
              return delay;
            },
          },
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 15,
                usePointStyle: true,
              }
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              borderRadius: 8,
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1,
              },
              grid: {
                borderDash: [5, 5],
              }
            },
            x: {
              grid: {
                display: false,
              }
            }
          }
        }
      });
    } catch (e) {
      console.error('Could not parse chart data', e);
    }
  }

  // AJAX form submission for creating a task with validation
  const createTaskForm = document.getElementById('create-task-form');
  if (createTaskForm) {
    createTaskForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const form = e.currentTarget;
      
      if (!validateForm(form)) {
        showAlert('Please fix the errors in the form', 'error');
        return;
      }
      
      const formData = new FormData(form);
      const submitButton = form.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;

      submitButton.disabled = true;
      submitButton.textContent = 'Saving...';
      submitButton.classList.add('btn-loading');

      fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': formData.get('csrf_token'),
        },
        body: formData,
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showAlert(data.message || 'Task created successfully!', 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showAlert(data.error || 'Failed to create task', 'error');
          submitButton.disabled = false;
          submitButton.textContent = originalButtonText;
          submitButton.classList.remove('btn-loading');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showAlert('An unexpected error occurred. Please try again.', 'error');
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        submitButton.classList.remove('btn-loading');
      });
    });
  }

  // Generic AJAX form handler for simple "save and redirect" forms
  function handleAjaxForm(formId, redirectUrl) {
    const form = document.getElementById(formId);
    if (form) {
      // Add validation elements
      form.querySelectorAll('input, select, textarea').forEach(input => {
        const formGroup = input.parentElement;
        if (!formGroup.querySelector('.form-error')) {
          const errorEl = document.createElement('div');
          errorEl.className = 'form-error';
          formGroup.appendChild(errorEl);
        }
      });
      
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        if (!validateForm(form)) {
          showAlert('Please fix the errors in the form', 'error');
          return;
        }
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;

        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        submitButton.classList.add('btn-loading');

        fetch(form.action, {
          method: 'POST',
          headers: { 'X-CSRF-Token': formData.get('csrf_token') },
          body: formData,
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showAlert(data.message || 'Saved successfully!', 'success');
            if (redirectUrl) {
              setTimeout(() => {
                window.location.href = redirectUrl;
              }, 1000);
            }
          } else {
            showAlert(data.error || 'Failed to save', 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
            submitButton.classList.remove('btn-loading');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showAlert('An unexpected error occurred. Please try again.', 'error');
          submitButton.disabled = false;
          submitButton.textContent = originalButtonText;
          submitButton.classList.remove('btn-loading');
        });
      });
    }
  }

  handleAjaxForm('edit-user-form', '/admin/users.php');
  handleAjaxForm('edit-module-form', '/admin/modules.php');
  handleAjaxForm('edit-resource-form', '/admin/resources.php');
  handleAjaxForm('edit-event-form', '/admin/events.php');
  handleAjaxForm('edit-message-form', '/admin/messages.php');
  handleAjaxForm('edit-achievement-form', '/admin/achievements.php');

  // User-side forms
  handleAjaxForm('user-create-task-form', '/user/tasks.php');

  // Add real-time form validation
  document.addEventListener('input', function(e) {
    if (e.target.matches('input, select, textarea')) {
      const input = e.target;
      const form = input.closest('form');
      if (form && form.hasAttribute('data-validate')) {
        validateInput(input);
      }
    }
  });

  function validateInput(input) {
    const value = input.value.trim();
    const errorEl = input.parentElement.querySelector('.form-error');
    
    // Reset state
    input.classList.remove('error', 'success');
    if (errorEl) errorEl.style.display = 'none';
    
    // Skip if not required and empty
    if (!input.hasAttribute('required') && !value) return;
    
    // Validate based on type
    let isValid = true;
    let errorMessage = '';
    
    if (input.hasAttribute('required') && !value) {
      isValid = false;
      errorMessage = 'This field is required';
    } else if (input.type === 'email' && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
      }
    } else if ((input.type === 'tel' || input.name === 'mobile') && value) {
      const phoneRegex = /^[\d\s\-\+\(\)]+$/;
      if (!phoneRegex.test(value) || value.replace(/\D/g, '').length < 10) {
        isValid = false;
        errorMessage = 'Please enter a valid phone number';
      }
    }
    
    // Show validation state
    if (!isValid) {
      input.classList.add('error');
      if (errorEl) {
        errorEl.textContent = errorMessage;
        errorEl.style.display = 'block';
      }
    } else if (value) {
      input.classList.add('success');
    }
  }

  // Performance optimization: Debounce function
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Add smooth scrolling for anchor links
  document.addEventListener('click', function(e) {
    const link = e.target.closest('a[href^="#"]');
    if (link) {
      e.preventDefault();
      const targetId = link.getAttribute('href').slice(1);
      const targetElement = document.getElementById(targetId);
      if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  });

  // Network status indicator
  function updateNetworkStatus() {
    const indicator = document.getElementById('network-status');
    if (!indicator) return;
    
    if (navigator.onLine) {
      indicator.textContent = 'Online';
      indicator.className = 'badge badge-success';
    } else {
      indicator.textContent = 'Offline';
      indicator.className = 'badge badge-warning';
    }
  }

  window.addEventListener('online', updateNetworkStatus);
  window.addEventListener('offline', updateNetworkStatus);
  updateNetworkStatus();

})();