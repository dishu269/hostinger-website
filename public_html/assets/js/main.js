// Main JS for Asclepius Wellness App
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // Progressive enhancement: register service worker
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
  }

  // Intersection fade-ins
  const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      if (entry.isIntersecting) entry.target.classList.add('fade-in');
    }
  }, { threshold: 0.2 });
  document.querySelectorAll('.card, .kpi, .motivation').forEach((el) => observer.observe(el));

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

  // AI role-play (very simple keyword-based)
  window.aiRespond = function (message) {
    const text = (message || '').toLowerCase();
    if (text.includes('price') || text.includes('cost')) {
      return 'Great question! Focus on value: quality, health benefits, and long-term savings.';
    }
    if (text.includes('risk') || text.includes('scam')) {
      return 'Share compliance, company certifications, and transparent business plan details.';
    }
    if (text.includes('time') || text.includes('busy')) {
      return 'Suggest micro-steps: 30 minutes daily, leverage templates, and team support.';
    }
    return 'Listen actively, ask open questions, and connect needs to benefits. Offer a follow-up call.';
  };

  // Lead form offline fallback
  const leadForm = document.querySelector('#lead-form');
  if (leadForm) {
    leadForm.addEventListener('submit', async (e) => {
      const form = e.currentTarget;
      if (!navigator.onLine) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());
        const all = JSON.parse(localStorage.getItem(queueKey) || '[]');
        all.push(data);
        localStorage.setItem(queueKey, JSON.stringify(all));
        alert('Saved offline. Will sync when online.');
        form.reset();
      }
    });
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
})();


