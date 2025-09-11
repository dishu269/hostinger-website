<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$pdo = get_db();
$view = $_GET['view'] ?? 'overview';
$period = $_GET['period'] ?? 'month';

// Calculate date ranges
$today = date('Y-m-d');
$start_date = match($period) {
    'week' => date('Y-m-d', strtotime('-7 days')),
    'month' => date('Y-m-01'),
    'quarter' => date('Y-m-d', strtotime('-3 months')),
    'year' => date('Y-01-01'),
    default => date('Y-m-01')
};

// Get financial overview
$overview = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN record_type = 'sale' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN record_type = 'commission' THEN amount ELSE 0 END) as total_commissions,
        SUM(CASE WHEN record_type = 'expense' THEN amount ELSE 0 END) as total_expenses,
        SUM(CASE WHEN record_type = 'bonus' THEN amount ELSE 0 END) as total_bonuses,
        COUNT(DISTINCT CASE WHEN record_type = 'sale' THEN user_id END) as active_sellers,
        COUNT(CASE WHEN record_type = 'sale' THEN 1 END) as total_transactions
    FROM financial_records
    WHERE record_date >= ?
");
$overview->execute([$start_date]);
$financial_overview = $overview->fetch();

// Calculate profit
$profit = $financial_overview['total_revenue'] - $financial_overview['total_commissions'] - $financial_overview['total_expenses'];

// Revenue trend
$revenue_trend = $pdo->prepare("
    SELECT 
        DATE(record_date) as date,
        SUM(CASE WHEN record_type = 'sale' THEN amount ELSE 0 END) as revenue,
        SUM(CASE WHEN record_type = 'commission' THEN amount ELSE 0 END) as commission,
        SUM(CASE WHEN record_type = 'expense' THEN amount ELSE 0 END) as expense
    FROM financial_records
    WHERE record_date >= ?
    GROUP BY DATE(record_date)
    ORDER BY date
");
$revenue_trend->execute([$start_date]);
$trend_data = $revenue_trend->fetchAll();

// Top earners
$top_earners = $pdo->prepare("
    SELECT 
        u.id,
        u.name,
        u.avatar_url,
        SUM(CASE WHEN fr.record_type = 'sale' THEN fr.amount ELSE 0 END) as sales_revenue,
        SUM(CASE WHEN fr.record_type = 'commission' THEN fr.amount ELSE 0 END) as commissions_earned,
        COUNT(DISTINCT CASE WHEN fr.record_type = 'sale' THEN fr.id END) as transaction_count,
        AVG(CASE WHEN fr.record_type = 'sale' THEN fr.amount ELSE NULL END) as avg_transaction_value
    FROM users u
    JOIN financial_records fr ON u.id = fr.user_id
    WHERE fr.record_date >= ?
    GROUP BY u.id
    ORDER BY sales_revenue DESC
    LIMIT 10
");
$top_earners->execute([$start_date]);
$earners = $top_earners->fetchAll();

// Commission structure (mock data - you can customize this)
$commission_tiers = [
    ['tier' => 'Starter', 'range' => '₹0 - ₹50,000', 'rate' => '10%', 'members' => 45],
    ['tier' => 'Silver', 'range' => '₹50,001 - ₹1,00,000', 'rate' => '15%', 'members' => 23],
    ['tier' => 'Gold', 'range' => '₹1,00,001 - ₹5,00,000', 'rate' => '20%', 'members' => 12],
    ['tier' => 'Platinum', 'range' => '₹5,00,001+', 'rate' => '25%', 'members' => 5]
];

// Recent transactions
$recent_transactions = $pdo->prepare("
    SELECT 
        fr.*,
        u.name as user_name,
        u.avatar_url
    FROM financial_records fr
    JOIN users u ON fr.user_id = u.id
    ORDER BY fr.created_at DESC
    LIMIT 20
");
$recent_transactions->execute();
$transactions = $recent_transactions->fetchAll();

// Forecast next month (simple linear projection)
$last_month_revenue = $pdo->prepare("
    SELECT SUM(amount) as revenue
    FROM financial_records
    WHERE record_type = 'sale' 
    AND record_date >= DATE_SUB(?, INTERVAL 1 MONTH)
    AND record_date < ?
");
$last_month_revenue->execute([$start_date, $start_date]);
$last_month = $last_month_revenue->fetch()['revenue'] ?: 0;

$growth_rate = $financial_overview['total_revenue'] > 0 && $last_month > 0 
    ? (($financial_overview['total_revenue'] - $last_month) / $last_month) * 100 
    : 0;

$forecast_revenue = $financial_overview['total_revenue'] * (1 + ($growth_rate / 100));
?>

<div class="finance-module">
    <div class="page-header">
        <h1>Financial Management</h1>
        <div class="header-controls">
            <div class="period-selector">
                <a href="?period=week" class="period-btn <?= $period === 'week' ? 'active' : '' ?>">Week</a>
                <a href="?period=month" class="period-btn <?= $period === 'month' ? 'active' : '' ?>">Month</a>
                <a href="?period=quarter" class="period-btn <?= $period === 'quarter' ? 'active' : '' ?>">Quarter</a>
                <a href="?period=year" class="period-btn <?= $period === 'year' ? 'active' : '' ?>">Year</a>
            </div>
            <button onclick="recordTransaction()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Record Transaction
            </button>
        </div>
    </div>

    <!-- Financial KPIs -->
    <div class="financial-kpis">
        <div class="kpi-card revenue">
            <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div class="kpi-content">
                <div class="kpi-value">₹<?= number_format($financial_overview['total_revenue']) ?></div>
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-change positive">
                    <i class="fas fa-arrow-up"></i> <?= round($growth_rate, 1) ?>% from last period
                </div>
            </div>
        </div>
        
        <div class="kpi-card profit">
            <div class="kpi-icon"><i class="fas fa-wallet"></i></div>
            <div class="kpi-content">
                <div class="kpi-value">₹<?= number_format($profit) ?></div>
                <div class="kpi-label">Net Profit</div>
                <div class="kpi-subtext">
                    <?= round($profit * 100 / max($financial_overview['total_revenue'], 1), 1) ?>% margin
                </div>
            </div>
        </div>

        <div class="kpi-card commissions">
            <div class="kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="kpi-content">
                <div class="kpi-value">₹<?= number_format($financial_overview['total_commissions']) ?></div>
                <div class="kpi-label">Commissions Paid</div>
                <div class="kpi-subtext"><?= $financial_overview['active_sellers'] ?> active sellers</div>
            </div>
        </div>

        <div class="kpi-card forecast">
            <div class="kpi-icon"><i class="fas fa-chart-area"></i></div>
            <div class="kpi-content">
                <div class="kpi-value">₹<?= number_format($forecast_revenue) ?></div>
                <div class="kpi-label">Next Month Forecast</div>
                <div class="kpi-subtext">Based on current trend</div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="chart-section">
        <div class="section-header">
            <h2>Revenue & Expense Trend</h2>
            <div class="chart-controls">
                <button onclick="exportChart()" class="btn-small">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        <canvas id="revenueChart" height="80"></canvas>
    </div>

    <div class="finance-grid">
        <!-- Top Earners -->
        <div class="finance-section">
            <h2>Top Performers</h2>
            <div class="earners-list">
                <?php foreach($earners as $index => $earner): ?>
                <div class="earner-card">
                    <div class="rank">#<?= $index + 1 ?></div>
                    <img src="<?= $earner['avatar_url'] ?: '/assets/img/placeholder.svg' ?>" class="earner-avatar">
                    <div class="earner-info">
                        <div class="earner-name"><?= htmlspecialchars($earner['name']) ?></div>
                        <div class="earner-stats">
                            <span>₹<?= number_format($earner['sales_revenue']) ?> sales</span>
                            <span class="separator">•</span>
                            <span><?= $earner['transaction_count'] ?> transactions</span>
                        </div>
                    </div>
                    <div class="commission-badge">
                        ₹<?= number_format($earner['commissions_earned']) ?>
                        <small>commission</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Commission Structure -->
        <div class="finance-section">
            <h2>Commission Structure</h2>
            <div class="commission-tiers">
                <?php foreach($commission_tiers as $tier): ?>
                <div class="tier-card <?= strtolower($tier['tier']) ?>">
                    <div class="tier-header">
                        <h4><?= $tier['tier'] ?></h4>
                        <span class="tier-rate"><?= $tier['rate'] ?></span>
                    </div>
                    <div class="tier-range"><?= $tier['range'] ?></div>
                    <div class="tier-members">
                        <i class="fas fa-users"></i> <?= $tier['members'] ?> members
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button onclick="editCommissionStructure()" class="btn btn-secondary mt-3">
                <i class="fas fa-edit"></i> Edit Structure
            </button>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="transactions-section">
        <div class="section-header">
            <h2>Recent Transactions</h2>
            <a href="/admin/reports.php?type=transactions" class="btn-small">View All</a>
        </div>
        <div class="transactions-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $transaction): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($transaction['record_date'])) ?></td>
                        <td>
                            <div class="user-cell">
                                <img src="<?= $transaction['avatar_url'] ?: '/assets/img/placeholder.svg' ?>" class="user-avatar-small">
                                <?= htmlspecialchars($transaction['user_name']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="transaction-type <?= $transaction['record_type'] ?>">
                                <?= ucfirst($transaction['record_type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($transaction['description'] ?: '-') ?></td>
                        <td class="amount <?= $transaction['record_type'] === 'expense' ? 'expense' : '' ?>">
                            <?= $transaction['record_type'] === 'expense' ? '-' : '+' ?>₹<?= number_format($transaction['amount']) ?>
                        </td>
                        <td>
                            <button onclick="editTransaction(<?= $transaction['id'] ?>)" class="btn-small">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="summary-section">
        <h2>Period Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Transactions</span>
                <span class="summary-value"><?= number_format($financial_overview['total_transactions']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Average Transaction</span>
                <span class="summary-value">₹<?= number_format($financial_overview['total_revenue'] / max($financial_overview['total_transactions'], 1)) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Commission Rate</span>
                <span class="summary-value"><?= round($financial_overview['total_commissions'] * 100 / max($financial_overview['total_revenue'], 1), 1) ?>%</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Operating Expenses</span>
                <span class="summary-value">₹<?= number_format($financial_overview['total_expenses']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Modal -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Record Transaction</h2>
            <span class="close" onclick="closeTransactionModal()">&times;</span>
        </div>
        <form id="transactionForm" onsubmit="saveTransaction(event)">
            <div class="form-group">
                <label for="user_id">User</label>
                <select id="user_id" name="user_id" required>
                    <option value="">Select user</option>
                    <?php
                    $users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
                    foreach($users as $user):
                    ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="record_type">Type</label>
                <select id="record_type" name="record_type" required>
                    <option value="sale">Sale</option>
                    <option value="commission">Commission</option>
                    <option value="expense">Expense</option>
                    <option value="bonus">Bonus</option>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="record_date">Date</label>
                <input type="date" id="record_date" name="record_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Transaction</button>
                <button type="button" class="btn btn-secondary" onclick="closeTransactionModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.finance-module {
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

.header-controls {
    display: flex;
    gap: 20px;
    align-items: center;
}

.period-selector {
    display: flex;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.period-btn {
    padding: 10px 20px;
    text-decoration: none;
    color: #6c757d;
    border-right: 1px solid #e9ecef;
    transition: all 0.2s;
}

.period-btn:last-child {
    border-right: none;
}

.period-btn.active,
.period-btn:hover {
    background: #667eea;
    color: white;
}

.financial-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.kpi-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    gap: 20px;
    align-items: center;
    transition: transform 0.2s;
}

.kpi-card:hover {
    transform: translateY(-3px);
}

.kpi-icon {
    font-size: 2.5rem;
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
}

.kpi-card.revenue .kpi-icon {
    background: #e3f2fd;
    color: #1976d2;
}

.kpi-card.profit .kpi-icon {
    background: #e8f5e9;
    color: #388e3c;
}

.kpi-card.commissions .kpi-icon {
    background: #fff3e0;
    color: #f57c00;
}

.kpi-card.forecast .kpi-icon {
    background: #f3e5f5;
    color: #7b1fa2;
}

.kpi-value {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.kpi-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 5px;
}

.kpi-change {
    font-size: 0.85rem;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.kpi-change.positive {
    color: #27ae60;
}

.kpi-change.negative {
    color: #e74c3c;
}

.kpi-subtext {
    font-size: 0.85rem;
    color: #667eea;
    margin-top: 5px;
}

.chart-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.finance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.finance-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.finance-section h2 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.earners-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.earner-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: background 0.2s;
}

.earner-card:hover {
    background: #e9ecef;
}

.rank {
    font-size: 1.2rem;
    font-weight: bold;
    color: #6c757d;
    width: 30px;
}

.earner-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.earner-info {
    flex: 1;
}

.earner-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.earner-stats {
    font-size: 0.85rem;
    color: #6c757d;
}

.separator {
    margin: 0 8px;
}

.commission-badge {
    text-align: center;
    padding: 10px 15px;
    background: #667eea;
    color: white;
    border-radius: 8px;
}

.commission-badge small {
    display: block;
    font-size: 0.75rem;
    opacity: 0.9;
}

.commission-tiers {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.tier-card {
    padding: 20px;
    border-radius: 10px;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.tier-card:hover {
    transform: translateY(-3px);
}

.tier-card.starter {
    background: #f0f4ff;
    border-color: #667eea;
}

.tier-card.silver {
    background: #f5f5f5;
    border-color: #9e9e9e;
}

.tier-card.gold {
    background: #fffbf0;
    border-color: #ffd700;
}

.tier-card.platinum {
    background: #f9f0ff;
    border-color: #9c27b0;
}

.tier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.tier-header h4 {
    margin: 0;
    color: #2c3e50;
}

.tier-rate {
    font-size: 1.2rem;
    font-weight: bold;
    color: #667eea;
}

.tier-range {
    color: #495057;
    margin-bottom: 10px;
}

.tier-members {
    font-size: 0.85rem;
    color: #6c757d;
}

.transactions-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.transactions-table {
    overflow-x: auto;
    margin-top: 20px;
}

.transactions-table table {
    width: 100%;
    border-collapse: collapse;
}

.transactions-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.transactions-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-avatar-small {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    object-fit: cover;
}

.transaction-type {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.transaction-type.sale {
    background: #d4edda;
    color: #155724;
}

.transaction-type.commission {
    background: #fff3cd;
    color: #856404;
}

.transaction-type.expense {
    background: #f8d7da;
    color: #721c24;
}

.transaction-type.bonus {
    background: #d1ecf1;
    color: #0c5460;
}

.amount {
    font-weight: 600;
    color: #27ae60;
}

.amount.expense {
    color: #e74c3c;
}

.summary-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.summary-item {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.summary-label {
    display: block;
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 10px;
}

.summary-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
}

/* Modal styles */
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

#transactionForm {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
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

.mt-3 {
    margin-top: 1rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue trend chart
const trendData = <?= json_encode($trend_data) ?>;
const ctx = document.getElementById('revenueChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendData.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        datasets: [{
            label: 'Revenue',
            data: trendData.map(d => d.revenue),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.3,
            fill: true
        }, {
            label: 'Commissions',
            data: trendData.map(d => d.commission),
            borderColor: '#f39c12',
            backgroundColor: 'rgba(243, 156, 18, 0.1)',
            tension: 0.3,
            fill: true
        }, {
            label: 'Expenses',
            data: trendData.map(d => d.expense),
            borderColor: '#e74c3c',
            backgroundColor: 'rgba(231, 76, 60, 0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ₹' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
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

// Modal functions
function recordTransaction() {
    document.getElementById('transactionModal').style.display = 'block';
}

function closeTransactionModal() {
    document.getElementById('transactionModal').style.display = 'none';
}

function saveTransaction(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('/admin/ajax_admin_actions.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'record_transaction',
            ...Object.fromEntries(formData)
        }),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error recording transaction');
        }
    });
}

function editTransaction(id) {
    // Load transaction data and populate form
    alert('Edit transaction functionality to be implemented');
}

function editCommissionStructure() {
    alert('Commission structure editor to be implemented');
}

function exportChart() {
    const canvas = document.getElementById('revenueChart');
    const url = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = url;
    a.download = 'revenue-chart.png';
    a.click();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('transactionModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>