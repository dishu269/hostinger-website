<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$action = $_GET['action'] ?? 'list';
$team_id = $_GET['id'] ?? null;

// Handle team operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = $_POST['name'];
        $parent_team_id = $_POST['parent_team_id'] ?: null;
        $team_lead_id = $_POST['team_lead_id'] ?: null;
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("INSERT INTO teams (name, parent_team_id, team_lead_id, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $parent_team_id, $team_lead_id, $description]);
        
        header('Location: /admin/teams.php?success=Team created successfully');
        exit;
    } elseif ($action === 'update' && $team_id) {
        $name = $_POST['name'];
        $parent_team_id = $_POST['parent_team_id'] ?: null;
        $team_lead_id = $_POST['team_lead_id'] ?: null;
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("UPDATE teams SET name = ?, parent_team_id = ?, team_lead_id = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $parent_team_id, $team_lead_id, $description, $team_id]);
        
        header('Location: /admin/teams.php?success=Team updated successfully');
        exit;
    }
}

// Get team details for view/edit
$team = null;
if ($team_id && ($action === 'view' || $action === 'edit')) {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               tl.name as team_lead_name,
               pt.name as parent_team_name,
               COUNT(DISTINCT u.id) as member_count
        FROM teams t
        LEFT JOIN users tl ON t.team_lead_id = tl.id
        LEFT JOIN teams pt ON t.parent_team_id = pt.id
        LEFT JOIN users u ON u.team_id = t.id
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();
}

// Get all teams for listing
$teams = $pdo->query("
    SELECT t.*, 
           tl.name as team_lead_name,
           tl.avatar_url as team_lead_avatar,
           pt.name as parent_team_name,
           COUNT(DISTINCT u.id) as member_count,
           COALESCE(SUM(pm.revenue_generated), 0) as total_revenue,
           COALESCE(AVG(pm.leads_converted * 100.0 / NULLIF(pm.leads_contacted, 0)), 0) as avg_conversion_rate
    FROM teams t
    LEFT JOIN users tl ON t.team_lead_id = tl.id
    LEFT JOIN teams pt ON t.parent_team_id = pt.id
    LEFT JOIN users u ON u.team_id = t.id
    LEFT JOIN performance_metrics pm ON pm.user_id = u.id AND pm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY t.id
    ORDER BY t.name
")->fetchAll();

// Get available users for team lead selection
$available_users = $pdo->query("SELECT id, name FROM users WHERE role = 'member' ORDER BY name")->fetchAll();

// Get team members if viewing a specific team
$team_members = [];
if ($team_id && $action === 'view') {
    $stmt = $pdo->prepare("
        SELECT u.*,
               COALESCE(SUM(pm.leads_contacted), 0) as leads_contacted_30d,
               COALESCE(SUM(pm.leads_converted), 0) as leads_converted_30d,
               COALESCE(SUM(pm.revenue_generated), 0) as revenue_30d,
               MAX(u.last_login) as last_seen
        FROM users u
        LEFT JOIN performance_metrics pm ON pm.user_id = u.id AND pm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        WHERE u.team_id = ?
        GROUP BY u.id
        ORDER BY revenue_30d DESC
    ");
    $stmt->execute([$team_id]);
    $team_members = $stmt->fetchAll();
}
?>

<?php if ($action === 'list'): ?>
<div class="teams-management">
    <div class="page-header">
        <h1>Team Management</h1>
        <a href="/admin/teams.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Team
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="teams-grid">
        <?php foreach($teams as $team): ?>
        <div class="team-card">
            <div class="team-header">
                <h3><?= htmlspecialchars($team['name']) ?></h3>
                <?php if ($team['parent_team_name']): ?>
                <span class="parent-team">Under: <?= htmlspecialchars($team['parent_team_name']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="team-lead">
                <?php if ($team['team_lead_name']): ?>
                <img src="<?= $team['team_lead_avatar'] ?: '/assets/img/placeholder.svg' ?>" class="lead-avatar">
                <span>Led by <?= htmlspecialchars($team['team_lead_name']) ?></span>
                <?php else: ?>
                <span class="no-lead">No team lead assigned</span>
                <?php endif; ?>
            </div>

            <div class="team-stats">
                <div class="stat">
                    <i class="fas fa-users"></i>
                    <span><?= $team['member_count'] ?> members</span>
                </div>
                <div class="stat">
                    <i class="fas fa-rupee-sign"></i>
                    <span>₹<?= number_format($team['total_revenue']) ?></span>
                </div>
                <div class="stat">
                    <i class="fas fa-percentage"></i>
                    <span><?= round($team['avg_conversion_rate'], 1) ?>% conv</span>
                </div>
            </div>

            <div class="team-actions">
                <a href="/admin/teams.php?action=view&id=<?= $team['id'] ?>" class="btn-small">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="/admin/teams.php?action=edit&id=<?= $team['id'] ?>" class="btn-small">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<div class="team-form">
    <h1><?= $action === 'create' ? 'Create New Team' : 'Edit Team' ?></h1>
    
    <form method="POST" class="form-modern">
        <div class="form-group">
            <label for="name">Team Name</label>
            <input type="text" id="name" name="name" value="<?= $team ? htmlspecialchars($team['name']) : '' ?>" required>
        </div>

        <div class="form-group">
            <label for="parent_team_id">Parent Team (Optional)</label>
            <select id="parent_team_id" name="parent_team_id">
                <option value="">No parent team</option>
                <?php foreach($teams as $t): ?>
                    <?php if ($t['id'] != $team_id): ?>
                    <option value="<?= $t['id'] ?>" <?= $team && $team['parent_team_id'] == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="team_lead_id">Team Lead</label>
            <select id="team_lead_id" name="team_lead_id">
                <option value="">No team lead</option>
                <?php foreach($available_users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $team && $team['team_lead_id'] == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= $team ? htmlspecialchars($team['description']) : '' ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'create' ? 'Create Team' : 'Update Team' ?>
            </button>
            <a href="/admin/teams.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php elseif ($action === 'view' && $team): ?>
<div class="team-view">
    <div class="team-header-detail">
        <div>
            <h1><?= htmlspecialchars($team['name']) ?></h1>
            <?php if ($team['description']): ?>
            <p class="team-description"><?= htmlspecialchars($team['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <a href="/admin/teams.php?action=edit&id=<?= $team['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Team
            </a>
            <a href="/admin/teams.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Teams
            </a>
        </div>
    </div>

    <div class="team-info-grid">
        <div class="info-card">
            <h3>Team Details</h3>
            <div class="info-item">
                <span class="label">Team Lead:</span>
                <span class="value"><?= $team['team_lead_name'] ?: 'Not assigned' ?></span>
            </div>
            <div class="info-item">
                <span class="label">Parent Team:</span>
                <span class="value"><?= $team['parent_team_name'] ?: 'None' ?></span>
            </div>
            <div class="info-item">
                <span class="label">Total Members:</span>
                <span class="value"><?= $team['member_count'] ?></span>
            </div>
        </div>

        <div class="info-card">
            <h3>Performance (Last 30 Days)</h3>
            <div class="performance-stats">
                <div class="perf-stat">
                    <div class="perf-value">₹<?= number_format($team['total_revenue']) ?></div>
                    <div class="perf-label">Total Revenue</div>
                </div>
                <div class="perf-stat">
                    <div class="perf-value"><?= round($team['avg_conversion_rate'], 1) ?>%</div>
                    <div class="perf-label">Avg Conversion</div>
                </div>
            </div>
        </div>
    </div>

    <div class="team-members-section">
        <h2>Team Members</h2>
        <div class="members-table">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Role</th>
                        <th>Leads (30d)</th>
                        <th>Conversions</th>
                        <th>Revenue</th>
                        <th>Last Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($team_members as $member): ?>
                    <tr>
                        <td>
                            <div class="member-info">
                                <img src="<?= $member['avatar_url'] ?: '/assets/img/placeholder.svg' ?>" class="member-avatar">
                                <span><?= htmlspecialchars($member['name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <?= $member['id'] == $team['team_lead_id'] ? '<span class="badge badge-primary">Team Lead</span>' : 'Member' ?>
                        </td>
                        <td><?= $member['leads_contacted_30d'] ?></td>
                        <td>
                            <?= $member['leads_converted_30d'] ?>
                            <?php if ($member['leads_contacted_30d'] > 0): ?>
                            <span class="conversion-rate">(<?= round($member['leads_converted_30d'] * 100 / $member['leads_contacted_30d'], 1) ?>%)</span>
                            <?php endif; ?>
                        </td>
                        <td>₹<?= number_format($member['revenue_30d']) ?></td>
                        <td><?= $member['last_seen'] ? time_ago($member['last_seen']) : 'Never' ?></td>
                        <td>
                            <a href="/admin/users.php?action=view&id=<?= $member['id'] ?>" class="btn-small">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.teams-management {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.teams-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.team-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}

.team-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.team-header h3 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.parent-team {
    font-size: 0.85rem;
    color: #6c757d;
}

.team-lead {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.lead-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

.no-lead {
    color: #6c757d;
    font-style: italic;
}

.team-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.stat {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    color: #495057;
}

.stat i {
    color: #667eea;
}

.team-actions {
    display: flex;
    gap: 10px;
}

.team-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.form-modern {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    font-size: 1rem;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.team-view {
    padding: 20px;
}

.team-header-detail {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 30px;
}

.team-description {
    color: #6c757d;
    margin-top: 10px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.team-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.info-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.info-item .label {
    color: #6c757d;
}

.info-item .value {
    font-weight: 600;
    color: #2c3e50;
}

.performance-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.perf-stat {
    text-align: center;
}

.perf-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #667eea;
}

.perf-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 5px;
}

.team-members-section {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.members-table {
    overflow-x: auto;
    margin-top: 20px;
}

.members-table table {
    width: 100%;
    border-collapse: collapse;
}

.members-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.members-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.member-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.member-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
}

.badge-primary {
    background: #667eea;
    color: white;
}

.conversion-rate {
    font-size: 0.85rem;
    color: #6c757d;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
</style>

<script>
function time_ago(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    return Math.floor(seconds / 86400) + ' days ago';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>