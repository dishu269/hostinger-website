// Main JS for Asclepius Wellness App
(function () {
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
        // Optional: unobserve after animating
        // observer.unobserve(entry.target);
      }
    }
  }, { threshold: 0.1 }); // Lowered threshold to trigger sooner

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
    for (const item of queue) {
      try {
        const res = await fetch('/user/lead_save.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify(item),
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('sync failed');
      } catch (_) {
        return; // Stay queued
      }
    }
    localStorage.removeItem(queueKey);
  }
  window.addEventListener('online', trySyncQueue);
  trySyncQueue();

  // Lead form AJAX and offline fallback
  const leadForm = document.querySelector('#lead-form');
  if (leadForm) {
    leadForm.addEventListener('submit', async (e) => {
      e.preventDefault(); // Always prevent default for AJAX/offline handling
      const form = e.currentTarget;
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());
      const submitButton = form.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;

      submitButton.disabled = true;
      submitButton.textContent = 'Saving...';

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
            alert('Lead saved successfully!');
            form.reset();
            // Optionally, reload to see the new lead in the list
            // location.reload();
          } else {
            alert('Error: ' + (result.error || 'Could not save lead.'));
          }
        } catch (error) {
          alert('An network error occurred. Your lead has been saved offline instead.');
          saveLeadOffline(data);
        } finally {
          submitButton.disabled = false;
          submitButton.textContent = originalButtonText;
        }
      } else {
        // Offline: save to localStorage
        saveLeadOffline(data);
        alert('Saved offline. Will sync when online.');
        form.reset();
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
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
    try { await navigator.clipboard.writeText(text); alert('Copied'); } catch (_) {}
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

  // Dashboard Chart
  const weeklyActivityCanvas = document.getElementById('weeklyActivityChart');
  if (weeklyActivityCanvas) {
    const dataEl = document.getElementById('weeklyActivityData');
    try {
      const chartData = JSON.parse(dataEl.textContent);
      new Chart(weeklyActivityCanvas, {
        type: 'bar',
        data: {
          labels: chartData.labels,
          datasets: chartData.datasets,
        },
        options: {
          responsive: true,
          animation: {
            delay: (context) => {
              let delay = 0;
              if (context.type === 'data' && context.mode === 'default') {
                delay = context.dataIndex * 100 + context.datasetIndex * 100;
              }
              return delay;
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1,
              }
            }
          }
        }
      });
    } catch (e) {
      console.error('Could not parse chart data', e);
    }
  }

  // AJAX form submission for creating a task
  const createTaskForm = document.getElementById('create-task-form');
  if (createTaskForm) {
    createTaskForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const form = e.currentTarget;
      const formData = new FormData(form);
      const submitButton = form.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;

      submitButton.disabled = true;
      submitButton.textContent = 'Saving...';

      fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': formData.get('csrf_token'),
        },
        body: formData,
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message || data.error);
        if (data.success) {
          location.reload();
        } else {
          submitButton.disabled = false;
          submitButton.textContent = originalButtonText;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred. Please try again.');
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
      });
    });
  }

  // Generic AJAX form handler for simple "save and redirect" forms
  function handleAjaxForm(formId, redirectUrl) {
    const form = document.getElementById(formId);
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;

        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        fetch(form.action, {
          method: 'POST',
          headers: { 'X-CSRF-Token': formData.get('csrf_token') },
          body: formData,
        })
        .then(response => response.json())
        .then(data => {
          alert(data.message || data.error);
          if (data.success && redirectUrl) {
            window.location.href = redirectUrl;
          } else {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An unexpected error occurred. Please try again.');
          submitButton.disabled = false;
          submitButton.textContent = originalButtonText;
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
})();


