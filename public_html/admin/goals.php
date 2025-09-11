<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$action = $_GET['action'] ?? 'list';
$goal_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $goal_type = $_POST['goal_type'];
        $owner_id = $_POST['owner_id'] ?: null;
        $team_id = $_POST['team_id'] ?: null;
        $parent_goal_id = $_POST['parent_goal_id'] ?: null;
        $target_value = $_POST['target_value'];
        $unit = $_POST['unit'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'] ?? 'draft';
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO goals (title, description, goal_type, owner_id, team_id, parent_goal_id, 
                                  target_value, unit, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $goal_type, $owner_id, $team_id, $parent_goal_id,
                           $target_value, $unit, $start_date, $end_date, $status]);
            $goal_id = $pdo->lastInsertId();
            
            // Add key results
            if (isset($_POST['key_results'])) {
                foreach ($_POST['key_results'] as $kr) {
                    if (!empty($kr['title'])) {
                        $stmt = $pdo->prepare("
                            INSERT INTO key_results (goal_id, title, target_value, unit, weight)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$goal_id, $kr['title'], $kr['target_value'], $kr['unit'] ?? '', $kr['weight'] ?? 100]);
                    }
                }
            }
            
            header('Location: /admin/goals.php?success=Goal created successfully');
            exit;
        } else {
            $stmt = $pdo->prepare("
                UPDATE goals SET title = ?, description = ?, goal_type = ?, owner_id = ?, team_id = ?,
                                parent_goal_id = ?, target_value = ?, unit = ?, start_date = ?, end_date = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $goal_type, $owner_id, $team_id, $parent_goal_id,
                           $target_value, $unit, $start_date, $end_date, $status, $goal_id]);
            
            header('Location: /admin/goals.php?success=Goal updated successfully');
            exit;
        }
    } elseif ($action === 'update_progress') {
        $current_value = $_POST['current_value'];
        $stmt = $pdo->prepare("UPDATE goals SET current_value = ? WHERE id = ?");
        $stmt->execute([$current_value, $goal_id]);
        
        // Update key results if provided
        if (isset($_POST['kr_progress'])) {
            foreach ($_POST['kr_progress'] as $kr_id => $value) {
                $stmt = $pdo->prepare("UPDATE key_results SET current_value = ? WHERE id = ?");
                $stmt->execute([$value, $kr_id]);
            }
        }
        
        header('Location: /admin/goals.php?action=view&id=' . $goal_id . '&success=Progress updated');
        exit;
    }
}

// Get all goals with hierarchy
$goals_tree = $pdo->query("
    SELECT g.*,
           CASE 
               WHEN g.goal_type = 'company' THEN 'Company'
               WHEN g.goal_type = 'team' THEN t.name
               WHEN g.goal_type = 'individual' THEN u.name
           END as owner_name,
           pg.title as parent_goal_title,
           (SELECT COUNT(*) FROM goals WHERE parent_goal_id = g.id) as child_count,
           (g.current_value / NULLIF(g.target_value, 0) * 100) as progress_percent
    FROM goals g
    LEFT JOIN teams t ON g.team_id = t.id
    LEFT JOIN users u ON g.owner_id = u.id
    LEFT JOIN goals pg ON g.parent_goal_id = pg.id
    ORDER BY g.goal_type, g.created_at DESC
")->fetchAll();

// Get specific goal details
$goal = null;
$key_results = [];
$child_goals = [];
if ($goal_id && ($action === 'view' || $action === 'edit')) {
    $stmt = $pdo->prepare("SELECT * FROM goals WHERE id = ?");
    $stmt->execute([$goal_id]);
    $goal = $stmt->fetch();
    
    // Get key results
    $stmt = $pdo->prepare("SELECT * FROM key_results WHERE goal_id = ?");
    $stmt->execute([$goal_id]);
    $key_results = $stmt->fetchAll();
    
    // Get child goals
    $stmt = $pdo->prepare("
        SELECT g.*, 
               CASE 
                   WHEN g.goal_type = 'team' THEN t.name
                   WHEN g.goal_type = 'individual' THEN u.name
               END as owner_name,
               (g.current_value / NULLIF(g.target_value, 0) * 100) as progress_percent
        FROM goals g
        LEFT JOIN teams t ON g.team_id = t.id
        LEFT JOIN users u ON g.owner_id = u.id
        WHERE g.parent_goal_id = ?
    ");
    $stmt->execute([$goal_id]);
    $child_goals = $stmt->fetchAll();
}

// Get teams and users for assignment
$teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'member' ORDER BY name")->fetchAll();
?>

<?php if ($action === 'list'): ?>
<div class="goals-management">
    <div class="page-header">
        <h1>Goals & OKRs Management</h1>
        <a href="/admin/goals.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Goal
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <!-- Goals Overview -->
    <div class="goals-overview">
        <div class="overview-card">
            <h3>Active Goals</h3>
            <div class="overview-value"><?= count(array_filter($goals_tree, fn($g) => $g['status'] === 'active')) ?></div>
        </div>
        <div class="overview-card">
            <h3>Average Progress</h3>
            <div class="overview-value">
                <?= round(array_sum(array_column(array_filter($goals_tree, fn($g) => $g['status'] === 'active'), 'progress_percent')) / max(count(array_filter($goals_tree, fn($g) => $g['status'] === 'active')), 1)) ?>%
            </div>
        </div>
        <div class="overview-card">
            <h3>Due This Quarter</h3>
            <div class="overview-value">
                <?= count(array_filter($goals_tree, fn($g) => $g['status'] === 'active' && strtotime($g['end_date']) <= strtotime('+3 months'))) ?>
            </div>
        </div>
        <div class="overview-card">
            <h3>Completed</h3>
            <div class="overview-value"><?= count(array_filter($goals_tree, fn($g) => $g['status'] === 'completed')) ?></div>
        </div>
    </div>

    <!-- Goals Tree -->
    <div class="goals-tree">
        <h2>Goals Hierarchy</h2>
        
        <!-- Company Goals -->
        <div class="goal-section">
            <h3><i class="fas fa-building"></i> Company Goals</h3>
            <div class="goals-list">
                <?php foreach(array_filter($goals_tree, fn($g) => $g['goal_type'] === 'company' && !$g['parent_goal_id']) as $goal): ?>
                <div class="goal-card <?= $goal['status'] ?>">
                    <div class="goal-header">
                        <h4><?= htmlspecialchars($goal['title']) ?></h4>
                        <span class="goal-status <?= $goal['status'] ?>"><?= ucfirst($goal['status']) ?></span>
                    </div>
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= round($goal['progress_percent']) ?>%"></div>
                        </div>
                        <span class="progress-text">
                            <?= number_format($goal['current_value']) ?> / <?= number_format($goal['target_value']) ?> <?= htmlspecialchars($goal['unit']) ?>
                            (<?= round($goal['progress_percent']) ?>%)
                        </span>
                    </div>
                    <div class="goal-meta">
                        <span><i class="fas fa-calendar"></i> <?= date('M d', strtotime($goal['start_date'])) ?> - <?= date('M d, Y', strtotime($goal['end_date'])) ?></span>
                        <?php if ($goal['child_count'] > 0): ?>
                        <span><i class="fas fa-sitemap"></i> <?= $goal['child_count'] ?> sub-goals</span>
                        <?php endif; ?>
                    </div>
                    <div class="goal-actions">
                        <a href="/admin/goals.php?action=view&id=<?= $goal['id'] ?>" class="btn-small">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="/admin/goals.php?action=edit&id=<?= $goal['id'] ?>" class="btn-small">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Team Goals -->
        <div class="goal-section">
            <h3><i class="fas fa-users"></i> Team Goals</h3>
            <div class="goals-list">
                <?php foreach(array_filter($goals_tree, fn($g) => $g['goal_type'] === 'team') as $goal): ?>
                <div class="goal-card <?= $goal['status'] ?>">
                    <div class="goal-header">
                        <h4><?= htmlspecialchars($goal['title']) ?></h4>
                        <span class="goal-owner"><?= htmlspecialchars($goal['owner_name']) ?></span>
                    </div>
                    <?php if ($goal['parent_goal_title']): ?>
                    <div class="parent-goal">
                        <i class="fas fa-link"></i> Linked to: <?= htmlspecialchars($goal['parent_goal_title']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= round($goal['progress_percent']) ?>%"></div>
                        </div>
                        <span class="progress-text"><?= round($goal['progress_percent']) ?>%</span>
                    </div>
                    <div class="goal-actions">
                        <a href="/admin/goals.php?action=view&id=<?= $goal['id'] ?>" class="btn-small">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Individual Goals -->
        <div class="goal-section">
            <h3><i class="fas fa-user"></i> Individual Goals</h3>
            <div class="goals-list">
                <?php foreach(array_filter($goals_tree, fn($g) => $g['goal_type'] === 'individual') as $goal): ?>
                <div class="goal-card <?= $goal['status'] ?>">
                    <div class="goal-header">
                        <h4><?= htmlspecialchars($goal['title']) ?></h4>
                        <span class="goal-owner"><?= htmlspecialchars($goal['owner_name']) ?></span>
                    </div>
                    <div class="goal-progress">
                        <div class="progress-bar small">
                            <div class="progress-fill" style="width: <?= round($goal['progress_percent']) ?>%"></div>
                        </div>
                        <span class="progress-text"><?= round($goal['progress_percent']) ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<div class="goal-form">
    <h1><?= $action === 'create' ? 'Create New Goal' : 'Edit Goal' ?></h1>
    
    <form method="POST" class="form-modern">
        <div class="form-section">
            <h3>Goal Details</h3>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title">Goal Title*</label>
                    <input type="text" id="title" name="title" value="<?= $goal ? htmlspecialchars($goal['title']) : '' ?>" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= $goal ? htmlspecialchars($goal['description']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="goal_type">Goal Type*</label>
                    <select id="goal_type" name="goal_type" required onchange="updateOwnerOptions()">
                        <option value="company" <?= $goal && $goal['goal_type'] === 'company' ? 'selected' : '' ?>>Company Goal</option>
                        <option value="team" <?= $goal && $goal['goal_type'] === 'team' ? 'selected' : '' ?>>Team Goal</option>
                        <option value="individual" <?= $goal && $goal['goal_type'] === 'individual' ? 'selected' : '' ?>>Individual Goal</option>
                    </select>
                </div>
                
                <div class="form-group" id="owner-group" style="display: none;">
                    <label for="owner_id">Owner</label>
                    <select id="owner_id" name="owner_id">
                        <option value="">Select owner</option>
                        <?php foreach($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= $goal && $goal['owner_id'] == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="team-group" style="display: none;">
                    <label for="team_id">Team</label>
                    <select id="team_id" name="team_id">
                        <option value="">Select team</option>
                        <?php foreach($teams as $team): ?>
                        <option value="<?= $team['id'] ?>" <?= $goal && $goal['team_id'] == $team['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="parent_goal_id">Parent Goal (Optional)</label>
                    <select id="parent_goal_id" name="parent_goal_id">
                        <option value="">No parent goal</option>
                        <?php foreach($goals_tree as $g): ?>
                            <?php if (!$goal || $g['id'] != $goal_id): ?>
                            <option value="<?= $g['id'] ?>" <?= $goal && $goal['parent_goal_id'] == $g['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['title']) ?> (<?= ucfirst($g['goal_type']) ?>)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="draft" <?= $goal && $goal['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="active" <?= $goal && $goal['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $goal && $goal['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $goal && $goal['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Measurement</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="target_value">Target Value*</label>
                    <input type="number" id="target_value" name="target_value" step="0.01" value="<?= $goal ? $goal['target_value'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="unit">Unit*</label>
                    <input type="text" id="unit" name="unit" value="<?= $goal ? htmlspecialchars($goal['unit']) : '' ?>" placeholder="e.g., leads, revenue, %" required>
                </div>
                
                <div class="form-group">
                    <label for="start_date">Start Date*</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $goal ? $goal['start_date'] : date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date*</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $goal ? $goal['end_date'] : '' ?>" required>
                </div>
            </div>
        </div>

        <?php if ($action === 'create'): ?>
        <div class="form-section">
            <h3>Key Results (Optional)</h3>
            <div id="keyResultsContainer">
                <div class="key-result-item">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>KR Title</label>
                            <input type="text" name="key_results[0][title]" placeholder="Key result title">
                        </div>
                        <div class="form-group">
                            <label>Target</label>
                            <input type="number" name="key_results[0][target_value]" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Unit</label>
                            <input type="text" name="key_results[0][unit]" placeholder="Unit">
                        </div>
                        <div class="form-group">
                            <label>Weight</label>
                            <input type="number" name="key_results[0][weight]" value="100" min="0" max="100">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" onclick="addKeyResult()" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Add Key Result
            </button>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'create' ? 'Create Goal' : 'Update Goal' ?>
            </button>
            <a href="/admin/goals.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php elseif ($action === 'view' && $goal): ?>
<div class="goal-detail">
    <div class="detail-header">
        <div>
            <h1><?= htmlspecialchars($goal['title']) ?></h1>
            <p class="goal-description"><?= htmlspecialchars($goal['description']) ?></p>
        </div>
        <div class="header-actions">
            <a href="/admin/goals.php?action=edit&id=<?= $goal['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Goal
            </a>
            <button onclick="updateProgress()" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i> Update Progress
            </button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <!-- Goal Overview -->
    <div class="goal-overview-cards">
        <div class="overview-card">
            <h3>Progress</h3>
            <div class="big-progress">
                <div class="circular-progress" data-progress="<?= round($goal['current_value'] * 100 / max($goal['target_value'], 1)) ?>">
                    <span><?= round($goal['current_value'] * 100 / max($goal['target_value'], 1)) ?>%</span>
                </div>
            </div>
            <div class="progress-details">
                <?= number_format($goal['current_value']) ?> / <?= number_format($goal['target_value']) ?> <?= htmlspecialchars($goal['unit']) ?>
            </div>
        </div>
        
        <div class="overview-card">
            <h3>Timeline</h3>
            <div class="timeline-info">
                <div class="date-item">
                    <span class="label">Start:</span>
                    <span class="value"><?= date('M d, Y', strtotime($goal['start_date'])) ?></span>
                </div>
                <div class="date-item">
                    <span class="label">End:</span>
                    <span class="value"><?= date('M d, Y', strtotime($goal['end_date'])) ?></span>
                </div>
                <div class="date-item">
                    <span class="label">Days Left:</span>
                    <span class="value"><?= max(0, floor((strtotime($goal['end_date']) - time()) / 86400)) ?></span>
                </div>
            </div>
        </div>
        
        <div class="overview-card">
            <h3>Details</h3>
            <div class="detail-list">
                <div class="detail-item">
                    <span class="label">Type:</span>
                    <span class="value"><?= ucfirst($goal['goal_type']) ?> Goal</span>
                </div>
                <div class="detail-item">
                    <span class="label">Status:</span>
                    <span class="status-badge <?= $goal['status'] ?>"><?= ucfirst($goal['status']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Results -->
    <?php if (!empty($key_results)): ?>
    <div class="key-results-section">
        <h2>Key Results</h2>
        <div class="key-results-list">
            <?php foreach($key_results as $kr): ?>
            <div class="kr-item">
                <div class="kr-header">
                    <h4><?= htmlspecialchars($kr['title']) ?></h4>
                    <span class="kr-weight">Weight: <?= $kr['weight'] ?>%</span>
                </div>
                <div class="kr-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= round($kr['current_value'] * 100 / max($kr['target_value'], 1)) ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?= number_format($kr['current_value']) ?> / <?= number_format($kr['target_value']) ?> <?= htmlspecialchars($kr['unit']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Child Goals -->
    <?php if (!empty($child_goals)): ?>
    <div class="child-goals-section">
        <h2>Cascading Goals</h2>
        <div class="child-goals-list">
            <?php foreach($child_goals as $child): ?>
            <div class="child-goal-card">
                <div class="child-header">
                    <h4><?= htmlspecialchars($child['title']) ?></h4>
                    <span class="child-owner"><?= htmlspecialchars($child['owner_name']) ?></span>
                </div>
                <div class="child-progress">
                    <div class="progress-bar small">
                        <div class="progress-fill" style="width: <?= round($child['progress_percent']) ?>%"></div>
                    </div>
                    <span><?= round($child['progress_percent']) ?>%</span>
                </div>
                <a href="/admin/goals.php?action=view&id=<?= $child['id'] ?>" class="view-link">View Details →</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Progress Update Modal -->
<div id="progressModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Update Progress</h2>
            <span class="close" onclick="closeProgressModal()">&times;</span>
        </div>
        <form method="POST" action="/admin/goals.php?action=update_progress&id=<?= $goal['id'] ?>">
            <div class="form-group">
                <label>Goal Progress</label>
                <input type="number" name="current_value" step="0.01" value="<?= $goal['current_value'] ?>" required>
                <small>Target: <?= number_format($goal['target_value']) ?> <?= htmlspecialchars($goal['unit']) ?></small>
            </div>
            
            <?php if (!empty($key_results)): ?>
            <h3>Key Results Progress</h3>
            <?php foreach($key_results as $kr): ?>
            <div class="form-group">
                <label><?= htmlspecialchars($kr['title']) ?></label>
                <input type="number" name="kr_progress[<?= $kr['id'] ?>]" step="0.01" value="<?= $kr['current_value'] ?>">
                <small>Target: <?= number_format($kr['target_value']) ?> <?= htmlspecialchars($kr['unit']) ?></small>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Progress</button>
                <button type="button" onclick="closeProgressModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.goals-management {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
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

.goals-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.overview-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.overview-card h3 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.overview-value {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.goals-tree {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.goal-section {
    margin-bottom: 30px;
}

.goal-section h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.goals-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.goal-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.2s;
}

.goal-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.goal-card.completed {
    background: #f8f9fa;
    opacity: 0.8;
}

.goal-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.goal-header h4 {
    margin: 0;
    color: #2c3e50;
}

.goal-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.goal-status.draft { background: #e9ecef; color: #6c757d; }
.goal-status.active { background: #d1ecf1; color: #0c5460; }
.goal-status.completed { background: #d4edda; color: #155724; }
.goal-status.cancelled { background: #f8d7da; color: #721c24; }

.goal-owner {
    font-size: 0.85rem;
    color: #667eea;
    font-weight: 600;
}

.parent-goal {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 10px;
}

.goal-progress {
    margin-bottom: 15px;
}

.progress-bar {
    height: 10px;
    background: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-bar.small {
    height: 6px;
}

.progress-fill {
    height: 100%;
    background: #667eea;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.85rem;
    color: #6c757d;
}

.goal-meta {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 15px;
}

.goal-actions {
    display: flex;
    gap: 10px;
}

/* Form Styles */
.goal-form {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.form-modern {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    font-size: 1rem;
}

.form-group small {
    margin-top: 5px;
    color: #6c757d;
}

.key-result-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

/* Detail View Styles */
.goal-detail {
    padding: 20px;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 30px;
}

.goal-description {
    color: #6c757d;
    margin-top: 10px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.goal-overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.big-progress {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.circular-progress {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: conic-gradient(#667eea var(--progress), #e9ecef 0);
    display: flex;
    align-items: center;
    justify-content: center;
}

.circular-progress::before {
    content: '';
    position: absolute;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
}

.circular-progress span {
    position: relative;
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.progress-details {
    text-align: center;
    color: #6c757d;
}

.timeline-info,
.detail-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.date-item,
.detail-item {
    display: flex;
    justify-content: space-between;
}

.date-item .label,
.detail-item .label {
    color: #6c757d;
}

.date-item .value,
.detail-item .value {
    font-weight: 600;
    color: #2c3e50;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.key-results-section,
.child-goals-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.key-results-list {
    display: grid;
    gap: 15px;
    margin-top: 20px;
}

.kr-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.kr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.kr-header h4 {
    margin: 0;
    font-size: 1rem;
}

.kr-weight {
    font-size: 0.85rem;
    color: #6c757d;
}

.kr-progress {
    display: flex;
    align-items: center;
    gap: 15px;
}

.child-goals-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.child-goal-card {
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.2s;
}

.child-goal-card:hover {
    border-color: #667eea;
}

.child-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.child-header h4 {
    margin: 0;
    font-size: 1rem;
}

.child-owner {
    font-size: 0.85rem;
    color: #667eea;
}

.child-progress {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.view-link {
    font-size: 0.85rem;
    color: #667eea;
    text-decoration: none;
}

.view-link:hover {
    text-decoration: underline;
}

/* Modal Styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    width: 80%;
    max-width: 500px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.close {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

.modal form {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-small {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    background: #667eea;
    color: white;
    cursor: pointer;
    font-size: 0.85rem;
    text-decoration: none;
    display: inline-block;
}

.btn-small:hover {
    background: #5a67d8;
}
</style>

<script>
// Update owner options based on goal type
function updateOwnerOptions() {
    const goalType = document.getElementById('goal_type').value;
    const ownerGroup = document.getElementById('owner-group');
    const teamGroup = document.getElementById('team-group');
    
    if (goalType === 'individual') {
        ownerGroup.style.display = 'block';
        teamGroup.style.display = 'none';
    } else if (goalType === 'team') {
        ownerGroup.style.display = 'none';
        teamGroup.style.display = 'block';
    } else {
        ownerGroup.style.display = 'none';
        teamGroup.style.display = 'none';
    }
}

// Add key result fields
let krIndex = 1;
function addKeyResult() {
    const container = document.getElementById('keyResultsContainer');
    const newKr = document.createElement('div');
    newKr.className = 'key-result-item';
    newKr.innerHTML = `
        <div class="form-grid">
            <div class="form-group">
                <label>KR Title</label>
                <input type="text" name="key_results[${krIndex}][title]" placeholder="Key result title">
            </div>
            <div class="form-group">
                <label>Target</label>
                <input type="number" name="key_results[${krIndex}][target_value]" step="0.01">
            </div>
            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="key_results[${krIndex}][unit]" placeholder="Unit">
            </div>
            <div class="form-group">
                <label>Weight</label>
                <input type="number" name="key_results[${krIndex}][weight]" value="100" min="0" max="100">
            </div>
        </div>
    `;
    container.appendChild(newKr);
    krIndex++;
}

// Progress modal functions
function updateProgress() {
    document.getElementById('progressModal').style.display = 'block';
}

function closeProgressModal() {
    document.getElementById('progressModal').style.display = 'none';
}

// Initialize circular progress
document.addEventListener('DOMContentLoaded', function() {
    const circularProgress = document.querySelector('.circular-progress');
    if (circularProgress) {
        const progress = circularProgress.dataset.progress;
        circularProgress.style.setProperty('--progress', progress + '%');
    }
    
    // Initialize form if in edit mode
    updateOwnerOptions();
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('progressModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>