<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$view = $_GET['view'] ?? 'kanban';

// Get all tasks with their relationships
$tasks = $pdo->query("
    SELECT t.*,
           u.name as assigned_to_name,
           u.avatar_url as assigned_avatar,
           COUNT(DISTINCT td.depends_on_task_id) as dependency_count,
           COUNT(DISTINCT ut.user_id) as completed_by_count
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN task_dependencies td ON t.id = td.task_id
    LEFT JOIN user_tasks ut ON t.id = ut.task_id
    WHERE t.is_template = 0
    GROUP BY t.id
    ORDER BY t.priority DESC, t.due_time
")->fetchAll();

// Get task dependencies for Gantt view
$dependencies = $pdo->query("
    SELECT td.*, t1.title as task_title, t2.title as depends_on_title
    FROM task_dependencies td
    JOIN tasks t1 ON td.task_id = t1.id
    JOIN tasks t2 ON td.depends_on_task_id = t2.id
")->fetchAll();

// Get team members for assignment
$team_members = $pdo->query("SELECT id, name, avatar_url FROM users WHERE role = 'member' ORDER BY name")->fetchAll();

// Get task states for kanban view
$task_states = [];
if ($view === 'kanban') {
    $states = $pdo->query("
        SELECT uts.*, u.name as user_name, u.avatar_url
        FROM user_task_state uts
        JOIN users u ON uts.user_id = u.id
    ")->fetchAll();
    
    foreach ($states as $state) {
        $task_states[$state['task_id']][$state['state']][] = $state;
    }
}
?>

<div class="task-management-enhanced">
    <div class="page-header">
        <h1>Task Management</h1>
        <div class="header-actions">
            <div class="view-switcher">
                <a href="?view=kanban" class="view-btn <?= $view === 'kanban' ? 'active' : '' ?>">
                    <i class="fas fa-columns"></i> Kanban
                </a>
                <a href="?view=gantt" class="view-btn <?= $view === 'gantt' ? 'active' : '' ?>">
                    <i class="fas fa-project-diagram"></i> Gantt
                </a>
                <a href="?view=list" class="view-btn <?= $view === 'list' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> List
                </a>
            </div>
            <button onclick="createTask()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Task
            </button>
        </div>
    </div>

    <?php if ($view === 'kanban'): ?>
    <!-- Kanban Board View -->
    <div class="kanban-board">
        <div class="kanban-column" data-state="todo">
            <div class="column-header">
                <h3>To Do</h3>
                <span class="task-count"><?= count(array_filter($tasks, fn($t) => !isset($task_states[$t['id']]['doing']) && !isset($task_states[$t['id']]['done']))) ?></span>
            </div>
            <div class="kanban-tasks" ondrop="dropTask(event, 'todo')" ondragover="allowDrop(event)">
                <?php foreach ($tasks as $task): ?>
                    <?php if (!isset($task_states[$task['id']]['doing']) && !isset($task_states[$task['id']]['done'])): ?>
                    <div class="kanban-task" draggable="true" ondragstart="dragTask(event, <?= $task['id'] ?>)" data-task-id="<?= $task['id'] ?>">
                        <div class="task-header">
                            <span class="task-priority <?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                            <span class="task-type"><?= ucfirst($task['type']) ?></span>
                        </div>
                        <h4><?= htmlspecialchars($task['title']) ?></h4>
                        <p class="task-description"><?= htmlspecialchars(substr($task['description'] ?: '', 0, 100)) ?>...</p>
                        <div class="task-meta">
                            <?php if ($task['assigned_to_name']): ?>
                            <div class="assigned-to">
                                <img src="<?= $task['assigned_avatar'] ?: '/assets/img/placeholder.svg' ?>" class="assignee-avatar">
                                <span><?= htmlspecialchars($task['assigned_to_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($task['due_time']): ?>
                            <div class="due-date">
                                <i class="fas fa-clock"></i> <?= date('M d', strtotime($task['task_date'] ?: 'today')) ?> <?= date('H:i', strtotime($task['due_time'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="task-stats">
                            <?php if ($task['dependency_count'] > 0): ?>
                            <span class="stat"><i class="fas fa-link"></i> <?= $task['dependency_count'] ?> deps</span>
                            <?php endif; ?>
                            <?php if ($task['checklist']): ?>
                            <span class="stat"><i class="fas fa-check-square"></i> <?= count(json_decode($task['checklist'], true)) ?> items</span>
                            <?php endif; ?>
                            <span class="stat"><i class="fas fa-users"></i> <?= $task['completed_by_count'] ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="kanban-column" data-state="doing">
            <div class="column-header">
                <h3>In Progress</h3>
                <span class="task-count"><?= count(array_filter($tasks, fn($t) => isset($task_states[$t['id']]['doing']))) ?></span>
            </div>
            <div class="kanban-tasks" ondrop="dropTask(event, 'doing')" ondragover="allowDrop(event)">
                <?php foreach ($tasks as $task): ?>
                    <?php if (isset($task_states[$task['id']]['doing'])): ?>
                    <div class="kanban-task in-progress" draggable="true" ondragstart="dragTask(event, <?= $task['id'] ?>)" data-task-id="<?= $task['id'] ?>">
                        <!-- Same task card structure -->
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="kanban-column" data-state="done">
            <div class="column-header">
                <h3>Done</h3>
                <span class="task-count"><?= count(array_filter($tasks, fn($t) => isset($task_states[$t['id']]['done']))) ?></span>
            </div>
            <div class="kanban-tasks" ondrop="dropTask(event, 'done')" ondragover="allowDrop(event)">
                <?php foreach ($tasks as $task): ?>
                    <?php if (isset($task_states[$task['id']]['done'])): ?>
                    <div class="kanban-task done" draggable="true" ondragstart="dragTask(event, <?= $task['id'] ?>)" data-task-id="<?= $task['id'] ?>">
                        <!-- Same task card structure -->
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php elseif ($view === 'gantt'): ?>
    <!-- Gantt Chart View -->
    <div class="gantt-container">
        <div class="gantt-timeline">
            <canvas id="ganttChart"></canvas>
        </div>
        <div class="gantt-sidebar">
            <h3>Task Dependencies</h3>
            <div class="dependencies-list">
                <?php foreach ($dependencies as $dep): ?>
                <div class="dependency-item">
                    <span class="dep-task"><?= htmlspecialchars($dep['task_title']) ?></span>
                    <i class="fas fa-arrow-right"></i>
                    <span class="dep-depends"><?= htmlspecialchars($dep['depends_on_title']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- List View -->
    <div class="task-list-view">
        <table class="task-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Progress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td>
                        <div class="task-title-cell">
                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                            <?php if ($task['description']): ?>
                            <p class="task-desc-preview"><?= htmlspecialchars(substr($task['description'], 0, 60)) ?>...</p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span class="task-type-badge <?= $task['type'] ?>"><?= ucfirst($task['type']) ?></span>
                    </td>
                    <td>
                        <span class="priority-badge <?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                    </td>
                    <td>
                        <?php if ($task['assigned_to_name']): ?>
                        <div class="assignee-cell">
                            <img src="<?= $task['assigned_avatar'] ?: '/assets/img/placeholder.svg' ?>" class="assignee-avatar-small">
                            <?= htmlspecialchars($task['assigned_to_name']) ?>
                        </div>
                        <?php else: ?>
                        <span class="unassigned">Unassigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($task['task_date']): ?>
                        <?= date('M d, Y', strtotime($task['task_date'])) ?>
                        <?php if ($task['due_time']): ?>
                        <br><small><?= date('H:i', strtotime($task['due_time'])) ?></small>
                        <?php endif; ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="progress-indicator">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $task['completed_by_count'] * 10 ?>%"></div>
                            </div>
                            <span class="progress-text"><?= $task['completed_by_count'] ?> completed</span>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="editTask(<?= $task['id'] ?>)" class="btn-small">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="viewTaskDetails(<?= $task['id'] ?>)" class="btn-small">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn-small btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Task Creation/Edit Modal -->
<div id="taskModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create Task</h2>
            <span class="close" onclick="closeTaskModal()">&times;</span>
        </div>
        <form id="taskForm" onsubmit="saveTask(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label for="taskTitle">Title*</label>
                    <input type="text" id="taskTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="taskType">Type</label>
                    <select id="taskType" name="type">
                        <option value="custom">Custom</option>
                        <option value="prospecting">Prospecting</option>
                        <option value="followup">Follow-up</option>
                        <option value="training">Training</option>
                        <option value="event">Event</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskPriority">Priority</label>
                    <select id="taskPriority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskAssignee">Assign To</label>
                    <select id="taskAssignee" name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($team_members as $member): ?>
                        <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskDate">Due Date</label>
                    <input type="date" id="taskDate" name="task_date">
                </div>
                <div class="form-group">
                    <label for="taskTime">Due Time</label>
                    <input type="time" id="taskTime" name="due_time">
                </div>
            </div>
            <div class="form-group full-width">
                <label for="taskDescription">Description</label>
                <textarea id="taskDescription" name="description" rows="4"></textarea>
            </div>
            <div class="form-group full-width">
                <label for="taskChecklist">Checklist (one item per line)</label>
                <textarea id="taskChecklist" name="checklist" rows="3" placeholder="Item 1&#10;Item 2&#10;Item 3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Task</button>
                <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.task-management-enhanced {
    padding: 20px;
    background: #f5f6fa;
    min-height: 100vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-actions {
    display: flex;
    gap: 20px;
    align-items: center;
}

.view-switcher {
    display: flex;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-btn {
    padding: 10px 20px;
    text-decoration: none;
    color: #6c757d;
    border-right: 1px solid #e9ecef;
    transition: all 0.2s;
}

.view-btn:last-child {
    border-right: none;
}

.view-btn.active,
.view-btn:hover {
    background: #667eea;
    color: white;
}

/* Kanban Board Styles */
.kanban-board {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.kanban-column {
    background: #e9ecef;
    border-radius: 10px;
    padding: 15px;
    min-height: 600px;
}

.column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.column-header h3 {
    margin: 0;
    color: #2c3e50;
}

.task-count {
    background: #667eea;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
}

.kanban-tasks {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.kanban-task {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: move;
    transition: all 0.2s;
}

.kanban-task:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.kanban-task.dragging {
    opacity: 0.5;
}

.task-header {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.task-priority {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.task-priority.high { background: #fee; color: #e55; }
.task-priority.medium { background: #ffeaa7; color: #f39c12; }
.task-priority.low { background: #dfe6e9; color: #636e72; }

.task-type {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    background: #e3f2fd;
    color: #1976d2;
}

.kanban-task h4 {
    margin: 0 0 8px 0;
    font-size: 1rem;
    color: #2c3e50;
}

.task-description {
    font-size: 0.85rem;
    color: #6c757d;
    margin: 0 0 10px 0;
}

.task-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.assigned-to {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85rem;
}

.assignee-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
}

.due-date {
    font-size: 0.85rem;
    color: #6c757d;
}

.task-stats {
    display: flex;
    gap: 10px;
    font-size: 0.8rem;
    color: #6c757d;
}

/* Gantt Chart Styles */
.gantt-container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
}

.gantt-timeline {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.gantt-sidebar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.dependencies-list {
    margin-top: 15px;
}

.dependency-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

/* List View Styles */
.task-list-view {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.task-table {
    width: 100%;
    border-collapse: collapse;
}

.task-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.task-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.task-title-cell strong {
    display: block;
    margin-bottom: 4px;
}

.task-desc-preview {
    font-size: 0.85rem;
    color: #6c757d;
    margin: 0;
}

.task-type-badge,
.priority-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

.assignee-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.assignee-avatar-small {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    object-fit: cover;
}

.unassigned {
    color: #6c757d;
    font-style: italic;
}

.progress-indicator {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.progress-text {
    font-size: 0.85rem;
    color: #6c757d;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-small {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    background: #667eea;
    color: white;
    cursor: pointer;
    font-size: 0.85rem;
}

.btn-small:hover {
    background: #5a67d8;
}

.btn-small.btn-danger {
    background: #e74c3c;
}

.btn-small.btn-danger:hover {
    background: #c0392b;
}

/* Modal Styles */
.modal {
    display: none;
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
    max-width: 600px;
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

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    padding: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    margin-bottom: 5px;
    font-weight: 600;
    color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 1rem;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
// Kanban drag and drop functionality
let draggedTaskId = null;

function allowDrop(ev) {
    ev.preventDefault();
}

function dragTask(ev, taskId) {
    draggedTaskId = taskId;
    ev.target.classList.add('dragging');
}

function dropTask(ev, newState) {
    ev.preventDefault();
    if (draggedTaskId) {
        // Update task state in database
        fetch('/admin/ajax_admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_task_state',
                task_id: draggedTaskId,
                state: newState
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Task modal functions
function createTask() {
    document.getElementById('modalTitle').textContent = 'Create Task';
    document.getElementById('taskForm').reset();
    document.getElementById('taskModal').style.display = 'block';
}

function editTask(taskId) {
    // Load task data and populate form
    fetch(`/admin/ajax_admin_actions.php?action=get_task&id=${taskId}`)
        .then(response => response.json())
        .then(task => {
            document.getElementById('modalTitle').textContent = 'Edit Task';
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskType').value = task.type;
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskAssignee').value = task.assigned_to || '';
            document.getElementById('taskDate').value = task.task_date || '';
            document.getElementById('taskTime').value = task.due_time || '';
            document.getElementById('taskDescription').value = task.description || '';
            // Handle checklist
            if (task.checklist) {
                const checklist = JSON.parse(task.checklist);
                document.getElementById('taskChecklist').value = checklist.join('\n');
            }
            document.getElementById('taskModal').style.display = 'block';
        });
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

function saveTask(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const taskData = Object.fromEntries(formData);
    
    // Convert checklist to JSON array
    if (taskData.checklist) {
        taskData.checklist = taskData.checklist.split('\n').filter(item => item.trim());
    }
    
    fetch('/admin/ajax_admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'save_task',
            ...taskData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function viewTaskDetails(taskId) {
    window.location.href = `/admin/edit_task.php?id=${taskId}`;
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch('/admin/ajax_admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_task',
                task_id: taskId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Initialize Gantt chart if in Gantt view
<?php if ($view === 'gantt'): ?>
const tasksData = <?= json_encode($tasks) ?>;
const dependenciesData = <?= json_encode($dependencies) ?>;

// Create Gantt chart using Chart.js or custom canvas drawing
const canvas = document.getElementById('ganttChart');
const ctx = canvas.getContext('2d');

// Simple Gantt visualization (you can enhance this with a proper Gantt library)
function drawGanttChart() {
    // Implementation for Gantt chart visualization
    // This is a placeholder - consider using a library like frappe-gantt or dhtmlx-gantt
}

drawGanttChart();
<?php endif; ?>

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('taskModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>