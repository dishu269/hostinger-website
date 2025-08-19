<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $persona = in_array(($_POST['persona'] ?? 'general'), ['health','income','general'], true) ? $_POST['persona'] : 'general';
    $interest = in_array(($_POST['interest'] ?? 'Warm'), ['Hot','Warm','Cold'], true) ? $_POST['interest'] : 'Warm';
    $body = sanitize_text($_POST['body'] ?? '', 2000);
    $pdo->prepare('INSERT INTO whatsapp_templates (persona, interest, body) VALUES (?,?,?) ON DUPLICATE KEY UPDATE body = VALUES(body)')
        ->execute([$persona, $interest, $body]);
    set_flash('success', 'Template saved.');
    header('Location: /admin/whatsapp_templates.php');
    exit;
  }
}

$rows = $pdo->query('SELECT * FROM whatsapp_templates ORDER BY persona, interest')->fetchAll();
?>

<h2>WhatsApp Templates</h2>
<div class="grid cols-2" style="margin-top:12px">
  <div class="card">
    <h3>Edit Template</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <label>Persona</label>
      <select name="persona">
        <option value="health">Health</option>
        <option value="income">Income</option>
        <option value="general" selected>General</option>
      </select>
      <label>Interest</label>
      <select name="interest">
        <option>Hot</option>
        <option selected>Warm</option>
        <option>Cold</option>
      </select>
      <label>Body</label>
      <textarea name="body" rows="6" placeholder="Hi {{name}}, ... — {{brand}}"></textarea>
      <p style="color:#6b7280">Placeholders: {{name}}, {{brand}}</p>
      <div style="margin-top:12px"><button class="btn">Save</button></div>
    </form>
  </div>
  <div class="card">
    <h3>Current Templates</h3>
    <table>
      <thead><tr><th>Persona</th><th>Interest</th><th>Body</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars(ucfirst($r['persona'])) ?></td>
          <td><?= htmlspecialchars($r['interest']) ?></td>
          <td><?= nl2br(htmlspecialchars($r['body'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


