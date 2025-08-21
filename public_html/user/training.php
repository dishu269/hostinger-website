<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

// Fetch top 5 published modules for quick access
$modules = $pdo->query('SELECT id, title, category, content_url, type FROM learning_modules WHERE published = 1 ORDER BY order_index ASC, id DESC LIMIT 5')->fetchAll();
?>

<h2>Training & Motivation Hub</h2>

<div class="grid cols-3" style="margin-top:12px">
  <div class="card">
    <h3>AI Role-play (Hinglish)</h3>
    <p>Prospect kya bol raha hai likho, aur turant coaching tip pao.</p>
    <textarea id="ai-input" rows="3" placeholder="Prospect says..."></textarea>
    <button class="btn" style="margin-top:8px" onclick="aiRespond(document.getElementById('ai-input').value, this); document.querySelector('[data-practice-increment]')?.click();">Tip Lo</button>
    <div class="card" style="margin-top:8px"><strong>Coach:</strong> <span id="ai-output">Yahan pe suggestion aayega.</span></div>
    <details style="margin-top:8px">
      <summary>Suggested Openers</summary>
      <ul>
        <li>Hi, ek helpful cheez share karna chahta/chahti hoon 2-min ka video hai.</li>
        <li>Aap health/income me interest rakhte ho? Short info bhej du?</li>
        <li>Kal 10-min call set kar le? Aapke time pe.</li>
      </ul>
    </details>
    <div style="margin-top:8px">
      <button class="btn btn-outline" data-copy="Hi {{name}}, ek short video share kar raha/rahi hoon. Aap dekh ke batana. — <?= SITE_BRAND ?>">Copy Script</button>
      <button class="btn btn-outline" data-wa="Hi, ek short video share kar raha/rahi hoon. Dekh ke batayein. — <?= SITE_BRAND ?>">Send on WhatsApp</button>
    </div>
  </div>
  <div class="card">
    <h3>Quick Access Modules</h3>
    <ul>
      <?php foreach($modules as $m): ?>
        <li>
          <strong><?= htmlspecialchars($m['title']) ?></strong>
          <span style="color:#6b7280">(<?= htmlspecialchars($m['category']) ?>)</span>
          <?php if ($m['content_url']): ?>
            - <a class="btn btn-outline" target="_blank" href="<?= htmlspecialchars($m['content_url']) ?>">Open</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <a class="btn" href="/user/learning.php" style="margin-top:8px">All Modules</a>
  </div>
  <div class="card motivation">
    <h3>Daily Motivation</h3>
    <p>Believe in your value. Roz 1 step aage badho.</p>
    <p style="margin-top:8px; color:#fff">Practice Count: <strong id="practice-count">0</strong></p>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px">
      <button class="btn btn-outline" data-practice-increment>+1 Practice</button>
      <a class="btn" href="/user/tasks.php?view=kanban">Open Kanban</a>
    </div>
  </div>
</div>

<div class="card" style="margin-top:12px">
  <h3>Practice Scripts</h3>
  <div class="grid cols-2">
    <div>
      <strong>Health Persona</strong>
      <pre style="white-space:pre-wrap; background:#f8fafc; padding:10px; border-radius:8px">Hi {{name}}, ek short wellness video share kar raha/rahi hoon. Aap dekh ke batana kaisa laga. Agar helpful lage to 10-min call set karte hain. — <?= SITE_BRAND ?></pre>
    </div>
    <div>
      <strong>Income Persona</strong>
      <pre style="white-space:pre-wrap; background:#f8fafc; padding:10px; border-radius:8px">Hi {{name}}, ek simple business plan video bhej raha/rahi hoon. Aapke time pe 10-min discuss kar lete hain, shayad ye aapke goals me fit ho. — <?= SITE_BRAND ?></pre>
    </div>
  </div>
  <p style="color:#6b7280">Tip: Inhe WhatsApp me paste karke personalize karein ({{name}} replace karein).</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


