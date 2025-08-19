<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_db();

$leaders = $pdo->query('SELECT u.name, COUNT(l.id) AS leads_count FROM users u LEFT JOIN leads l ON l.user_id = u.id GROUP BY u.id ORDER BY leads_count DESC, u.name ASC LIMIT 20')->fetchAll();
?>

<h2>Leaderboard</h2>
<div class="card" style="margin-top:12px">
  <table>
    <thead><tr><th>#</th><th>Name</th><th>Leads</th></tr></thead>
    <tbody>
      <?php $i=1; foreach($leaders as $row): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= (int)$row['leads_count'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="color:#6b7280; margin-top:8px">Tip: Add and follow-up leads daily to climb up.</p>
  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


