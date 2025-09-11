<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$report_type = $_GET['type'] ?? 'performance';
$export = $_GET['export'] ?? false;

// Get saved reports
$saved_reports = $pdo->query("
    SELECT sr.*, u.name as created_by_name
    FROM saved_reports sr
    JOIN users u ON sr.created_by = u.id
    ORDER BY sr.created_at DESC
")->fetchAll();

// Date range handling
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Generate report based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'performance':
        $report_title = 'Team Performance Report';
        $report_data = $pdo->prepare("
            SELECT 
                u.name,
                u.email,
                t.name as team_name,
                COUNT(DISTINCT l.id) as leads_generated,
                COUNT(DISTINCT CASE WHEN l.status = 'converted' THEN l.id END) as leads_converted,
                COALESCE(SUM(fr.amount), 0) as revenue_generated,
                COUNT(DISTINCT ut.task_id) as tasks_completed,
                COALESCE(AVG(pm.leads_converted * 100.0 / NULLIF(pm.leads_contacted, 0)), 0) as conversion_rate
            FROM users u
            LEFT JOIN teams t ON u.team_id = t.id
            LEFT JOIN leads l ON l.user_id = u.id AND l.created_at BETWEEN ? AND ?
            LEFT JOIN financial_records fr ON fr.user_id = u.id AND fr.record_type = 'sale' AND fr.record_date BETWEEN ? AND ?
            LEFT JOIN user_tasks ut ON ut.user_id = u.id AND ut.completed_at BETWEEN ? AND ?
            LEFT JOIN performance_metrics pm ON pm.user_id = u.id AND pm.metric_date BETWEEN ? AND ?
            WHERE u.role = 'member'
            GROUP BY u.id
            ORDER BY revenue_generated DESC
        ");
        $report_data->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        break;

    case 'financial':
        $report_title = 'Financial Summary Report';
        $report_data = $pdo->prepare("
            SELECT 
                DATE(record_date) as date,
                SUM(CASE WHEN record_type = 'sale' THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN record_type = 'commission' THEN amount ELSE 0 END) as commissions,
                SUM(CASE WHEN record_type = 'expense' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN record_type = 'bonus' THEN amount ELSE 0 END) as bonuses,
                COUNT(DISTINCT CASE WHEN record_type = 'sale' THEN user_id END) as active_sellers
            FROM financial_records
            WHERE record_date BETWEEN ? AND ?
            GROUP BY DATE(record_date)
            ORDER BY date DESC
        ");
        $report_data->execute([$start_date, $end_date]);
        break;

    case 'leads':
        $report_title = 'Leads Analysis Report';
        $report_data = $pdo->prepare("
            SELECT 
                l.interest_level,
                l.status,
                COUNT(*) as count,
                COUNT(DISTINCT l.user_id) as unique_users,
                AVG(DATEDIFF(l.updated_at, l.created_at)) as avg_days_to_close,
                GROUP_CONCAT(DISTINCT l.city) as cities
            FROM leads l
            WHERE l.created_at BETWEEN ? AND ?
            GROUP BY l.interest_level, l.status
            ORDER BY count DESC
        ");
        $report_data->execute([$start_date, $end_date]);
        break;

    case 'training':
        $report_title = 'Training Progress Report';
        $report_data = $pdo->prepare("
            SELECT 
                lm.title as module_name,
                lm.category,
                COUNT(DISTINCT mp.user_id) as users_started,
                COUNT(DISTINCT CASE WHEN mp.completed_at IS NOT NULL THEN mp.user_id END) as users_completed,
                AVG(mp.progress_percent) as avg_progress,
                AVG(CASE WHEN mp.completed_at IS NOT NULL THEN DATEDIFF(mp.completed_at, u.created_at) END) as avg_days_to_complete
            FROM learning_modules lm
            LEFT JOIN module_progress mp ON lm.id = mp.module_id
            LEFT JOIN users u ON mp.user_id = u.id
            GROUP BY lm.id
            ORDER BY lm.order_index
        ");
        $report_data->execute();
        break;

    case 'custom':
        $report_title = 'Custom Report Builder';
        // Custom report builder interface
        break;
}

$results = $report_data ? $report_data->fetchAll() : [];

// Handle export
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $report_title)) . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    if (!empty($results)) {
        fputcsv($output, array_keys($results[0]));
    }
    
    // Add data
    foreach ($results as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>

<div class="reports-module">
    <div class="page-header">
        <h1>Reports & Analytics</h1>
        <div class="header-actions">
            <button onclick="createCustomReport()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Custom Report
            </button>
        </div>
    </div>

    <!-- Report Type Selector -->
    <div class="report-selector">
        <a href="?type=performance" class="report-type <?= $report_type === 'performance' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Team Performance</span>
        </a>
        <a href="?type=financial" class="report-type <?= $report_type === 'financial' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>Financial Summary</span>
        </a>
        <a href="?type=leads" class="report-type <?= $report_type === 'leads' ? 'active' : '' ?>">
            <i class="fas fa-user-friends"></i>
            <span>Leads Analysis</span>
        </a>
        <a href="?type=training" class="report-type <?= $report_type === 'training' ? 'active' : '' ?>">
            <i class="fas fa-graduation-cap"></i>
            <span>Training Progress</span>
        </a>
        <a href="?type=custom" class="report-type <?= $report_type === 'custom' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Custom Report</span>
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="filters-section">
        <form method="GET" class="date-filter">
            <input type="hidden" name="type" value="<?= htmlspecialchars($report_type) ?>">
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= $end_date ?>">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
        </form>
        
        <div class="export-actions">
            <a href="?type=<?= $report_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&export=csv" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </a>
            <button onclick="printReport()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="saveReport()" class="btn btn-secondary">
                <i class="fas fa-save"></i> Save Report
            </button>
        </div>
    </div>

    <?php if ($report_type !== 'custom'): ?>
    <!-- Report Display -->
    <div class="report-content" id="reportContent">
        <div class="report-header">
            <h2><?= $report_title ?></h2>
            <p class="report-period">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
        </div>

        <?php if ($report_type === 'performance'): ?>
        <div class="performance-report">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Team</th>
                        <th>Leads Generated</th>
                        <th>Leads Converted</th>
                        <th>Conversion Rate</th>
                        <th>Revenue</th>
                        <th>Tasks Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['team_name'] ?: 'No Team') ?></td>
                        <td><?= number_format($row['leads_generated']) ?></td>
                        <td><?= number_format($row['leads_converted']) ?></td>
                        <td><?= round($row['conversion_rate'], 1) ?>%</td>
                        <td>₹<?= number_format($row['revenue_generated']) ?></td>
                        <td><?= number_format($row['tasks_completed']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th><?= number_format(array_sum(array_column($results, 'leads_generated'))) ?></th>
                        <th><?= number_format(array_sum(array_column($results, 'leads_converted'))) ?></th>
                        <th><?= round(array_sum(array_column($results, 'conversion_rate')) / count($results), 1) ?>%</th>
                        <th>₹<?= number_format(array_sum(array_column($results, 'revenue_generated'))) ?></th>
                        <th><?= number_format(array_sum(array_column($results, 'tasks_completed'))) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php elseif ($report_type === 'financial'): ?>
        <div class="financial-report">
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Revenue</h3>
                    <div class="summary-value">₹<?= number_format(array_sum(array_column($results, 'revenue'))) ?></div>
                </div>
                <div class="summary-card">
                    <h3>Total Commissions</h3>
                    <div class="summary-value">₹<?= number_format(array_sum(array_column($results, 'commissions'))) ?></div>
                </div>
                <div class="summary-card">
                    <h3>Total Expenses</h3>
                    <div class="summary-value">₹<?= number_format(array_sum(array_column($results, 'expenses'))) ?></div>
                </div>
                <div class="summary-card">
                    <h3>Net Profit</h3>
                    <div class="summary-value">₹<?= number_format(
                        array_sum(array_column($results, 'revenue')) - 
                        array_sum(array_column($results, 'commissions')) - 
                        array_sum(array_column($results, 'expenses'))
                    ) ?></div>
                </div>
            </div>

            <canvas id="financialChart" height="80"></canvas>

            <table class="report-table mt-4">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Revenue</th>
                        <th>Commissions</th>
                        <th>Expenses</th>
                        <th>Bonuses</th>
                        <th>Active Sellers</th>
                        <th>Net Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                        <td>₹<?= number_format($row['revenue']) ?></td>
                        <td>₹<?= number_format($row['commissions']) ?></td>
                        <td>₹<?= number_format($row['expenses']) ?></td>
                        <td>₹<?= number_format($row['bonuses']) ?></td>
                        <td><?= $row['active_sellers'] ?></td>
                        <td>₹<?= number_format($row['revenue'] - $row['commissions'] - $row['expenses']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($report_type === 'leads'): ?>
        <div class="leads-report">
            <div class="leads-overview">
                <canvas id="leadsChart" height="100"></canvas>
            </div>

            <table class="report-table mt-4">
                <thead>
                    <tr>
                        <th>Interest Level</th>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Unique Users</th>
                        <th>Avg Days to Close</th>
                        <th>Top Cities</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr>
                        <td>
                            <span class="interest-badge <?= strtolower($row['interest_level']) ?>">
                                <?= $row['interest_level'] ?>
                            </span>
                        </td>
                        <td><?= ucfirst($row['status']) ?></td>
                        <td><?= number_format($row['count']) ?></td>
                        <td><?= number_format($row['unique_users']) ?></td>
                        <td><?= round($row['avg_days_to_close'] ?: 0) ?> days</td>
                        <td><?= htmlspecialchars(substr($row['cities'] ?: '', 0, 50)) ?>...</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($report_type === 'training'): ?>
        <div class="training-report">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Category</th>
                        <th>Users Started</th>
                        <th>Users Completed</th>
                        <th>Completion Rate</th>
                        <th>Avg Progress</th>
                        <th>Avg Days to Complete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['module_name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= number_format($row['users_started']) ?></td>
                        <td><?= number_format($row['users_completed']) ?></td>
                        <td>
                            <?= $row['users_started'] > 0 ? round($row['users_completed'] * 100 / $row['users_started'], 1) : 0 ?>%
                        </td>
                        <td>
                            <div class="progress-bar-cell">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= round($row['avg_progress']) ?>%"></div>
                                </div>
                                <span><?= round($row['avg_progress']) ?>%</span>
                            </div>
                        </td>
                        <td><?= round($row['avg_days_to_complete'] ?: 0) ?> days</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Custom Report Builder -->
    <div class="custom-report-builder">
        <h2>Custom Report Builder</h2>
        <form id="customReportForm" onsubmit="generateCustomReport(event)">
            <div class="builder-section">
                <h3>Select Data Source</h3>
                <select id="dataSource" onchange="updateFields()">
                    <option value="">Choose a data source</option>
                    <option value="users">Users</option>
                    <option value="leads">Leads</option>
                    <option value="financial">Financial Records</option>
                    <option value="tasks">Tasks</option>
                    <option value="training">Training Progress</option>
                </select>
            </div>

            <div class="builder-section" id="fieldsSection" style="display: none;">
                <h3>Select Fields</h3>
                <div id="fieldsList" class="fields-grid"></div>
            </div>

            <div class="builder-section" id="filtersSection" style="display: none;">
                <h3>Add Filters</h3>
                <div id="filtersList"></div>
                <button type="button" onclick="addFilter()" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Add Filter
                </button>
            </div>

            <div class="builder-section" id="groupingSection" style="display: none;">
                <h3>Group By</h3>
                <select id="groupBy">
                    <option value="">No grouping</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <button type="button" onclick="resetBuilder()" class="btn btn-secondary">Reset</button>
            </div>
        </form>

        <div id="customReportResults"></div>
    </div>
    <?php endif; ?>

    <!-- Saved Reports -->
    <div class="saved-reports-section">
        <h2>Saved Reports</h2>
        <div class="saved-reports-grid">
            <?php foreach ($saved_reports as $report): ?>
            <div class="saved-report-card">
                <h4><?= htmlspecialchars($report['name']) ?></h4>
                <p><?= htmlspecialchars($report['description']) ?></p>
                <div class="report-meta">
                    <span><i class="fas fa-chart-bar"></i> <?= ucfirst($report['report_type']) ?></span>
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($report['created_by_name']) ?></span>
                    <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($report['created_at'])) ?></span>
                </div>
                <div class="report-actions">
                    <button onclick="loadSavedReport(<?= $report['id'] ?>)" class="btn-small">
                        <i class="fas fa-folder-open"></i> Open
                    </button>
                    <button onclick="deleteSavedReport(<?= $report['id'] ?>)" class="btn-small btn-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.reports-module {
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

.report-selector {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.report-type {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 15px 25px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    transition: all 0.2s;
    white-space: nowrap;
}

.report-type:hover,
.report-type.active {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.report-type i {
    font-size: 1.5rem;
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.date-filter {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
}

.filter-group input {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.export-actions {
    display: flex;
    gap: 10px;
}

.report-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.report-header {
    text-align: center;
    margin-bottom: 30px;
}

.report-header h2 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.report-period {
    color: #6c757d;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.report-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.report-table tfoot th {
    background: #e9ecef;
    font-weight: bold;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.summary-card h3 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.summary-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
}

.interest-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.interest-badge.hot { background: #fee; color: #e55; }
.interest-badge.warm { background: #ffeaa7; color: #f39c12; }
.interest-badge.cold { background: #dfe6e9; color: #636e72; }

.progress-bar-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #667eea;
}

/* Custom Report Builder */
.custom-report-builder {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.builder-section {
    margin-bottom: 30px;
}

.builder-section h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.field-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

/* Saved Reports */
.saved-reports-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.saved-reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.saved-report-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.2s;
}

.saved-report-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
}

.saved-report-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.saved-report-card p {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0 0 15px 0;
}

.report-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 15px;
}

.report-meta i {
    margin-right: 4px;
}

.report-actions {
    display: flex;
    gap: 8px;
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

.mt-4 {
    margin-top: 2rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($report_type === 'financial' && !empty($results)): ?>
// Financial chart
const financialData = <?= json_encode($results) ?>;
const ctx = document.getElementById('financialChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: financialData.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        datasets: [{
            label: 'Revenue',
            data: financialData.map(d => d.revenue),
            backgroundColor: '#667eea'
        }, {
            label: 'Commissions',
            data: financialData.map(d => d.commissions),
            backgroundColor: '#f39c12'
        }, {
            label: 'Expenses',
            data: financialData.map(d => d.expenses),
            backgroundColor: '#e74c3c'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
<?php endif; ?>

<?php if ($report_type === 'leads' && !empty($results)): ?>
// Leads chart
const leadsData = <?= json_encode($results) ?>;
const leadsCtx = document.getElementById('leadsChart').getContext('2d');

const groupedData = {};
leadsData.forEach(row => {
    if (!groupedData[row.interest_level]) {
        groupedData[row.interest_level] = 0;
    }
    groupedData[row.interest_level] += parseInt(row.count);
});

new Chart(leadsCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(groupedData),
        datasets: [{
            data: Object.values(groupedData),
            backgroundColor: ['#e74c3c', '#f39c12', '#95a5a6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
<?php endif; ?>

// Report functions
function printReport() {
    window.print();
}

function saveReport() {
    const reportName = prompt('Enter report name:');
    if (reportName) {
        const reportData = {
            name: reportName,
            report_type: '<?= $report_type ?>',
            filters: {
                start_date: '<?= $start_date ?>',
                end_date: '<?= $end_date ?>'
            }
        };
        
        fetch('/admin/ajax_admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_report',
                ...reportData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Report saved successfully');
                location.reload();
            }
        });
    }
}

function createCustomReport() {
    window.location.href = '?type=custom';
}

function loadSavedReport(reportId) {
    // Load saved report configuration
    fetch(`/admin/ajax_admin_actions.php?action=load_report&id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `?type=${data.report_type}&${new URLSearchParams(data.filters).toString()}`;
            }
        });
}

function deleteSavedReport(reportId) {
    if (confirm('Are you sure you want to delete this report?')) {
        fetch('/admin/ajax_admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete_report',
                report_id: reportId
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

// Custom report builder functions
const dataFields = {
    users: ['name', 'email', 'role', 'team', 'created_at', 'last_login', 'points'],
    leads: ['name', 'mobile', 'city', 'interest_level', 'status', 'created_at', 'follow_up_date'],
    financial: ['user', 'record_type', 'amount', 'record_date', 'description'],
    tasks: ['title', 'type', 'priority', 'assigned_to', 'due_date', 'completed_count'],
    training: ['module', 'category', 'progress', 'completed_at']
};

function updateFields() {
    const source = document.getElementById('dataSource').value;
    const fieldsSection = document.getElementById('fieldsSection');
    const fieldsList = document.getElementById('fieldsList');
    
    if (source) {
        fieldsSection.style.display = 'block';
        fieldsList.innerHTML = '';
        
        dataFields[source].forEach(field => {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'field-checkbox';
            fieldDiv.innerHTML = `
                <input type="checkbox" id="field_${field}" name="fields[]" value="${field}">
                <label for="field_${field}">${field.replace('_', ' ').charAt(0).toUpperCase() + field.slice(1)}</label>
            `;
            fieldsList.appendChild(fieldDiv);
        });
        
        document.getElementById('filtersSection').style.display = 'block';
        document.getElementById('groupingSection').style.display = 'block';
    }
}

function addFilter() {
    // Implementation for adding filters
    alert('Filter builder to be implemented');
}

function generateCustomReport(event) {
    event.preventDefault();
    // Implementation for generating custom report
    alert('Custom report generation to be implemented');
}

function resetBuilder() {
    document.getElementById('customReportForm').reset();
    document.getElementById('fieldsSection').style.display = 'none';
    document.getElementById('filtersSection').style.display = 'none';
    document.getElementById('groupingSection').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>