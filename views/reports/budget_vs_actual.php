<?php
/**
 * Budget vs Actual Report
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../controllers/BudgetEngine.php';

requireAdmin();

$budgetData = BudgetEngine::getBudgetVsActual();
$kpis = BudgetEngine::getDashboardKPIs();
$chartData = BudgetEngine::getChartData();

$pageTitle = 'Budget vs Actual Report';
include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Budget vs Actual Report</h1>
    <button class="btn btn-secondary" onclick="exportReport('budget_vs_actual')">📥 Export CSV</button>
</div>

<!-- Stats Row -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px;">
    <div class="glass-widget">
        <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600;">Total Budget</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 8px;"><?= formatCurrency($kpis['total_budget']) ?>
        </div>
    </div>
    <div class="glass-widget">
        <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600;">Total Actual</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 8px;"><?= formatCurrency($kpis['total_actual']) ?>
        </div>
    </div>
    <div class="glass-widget">
        <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600;">Remaining</div>
        <div
            style="font-size: 28px; font-weight: 700; margin-top: 8px; color: <?= $kpis['total_remaining'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
            <?= formatCurrency($kpis['total_remaining']) ?>
        </div>
    </div>
    <div class="glass-widget">
        <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600;">Utilization</div>
        <div style="font-size: 28px; font-weight: 700; margin-top: 8px;"><?= $kpis['utilization'] ?>%</div>
        <div style="height: 4px; background: rgba(0,0,0,0.05); border-radius: 2px; margin-top: 12px; overflow: hidden;">
            <div style="height: 100%; width: <?= min($kpis['utilization'], 100) ?>%; background: var(--accent-blue);">
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="glass-widget" style="margin-bottom: 32px; padding: 32px;">
    <div style="display: flex; gap: 32px; flex-wrap: wrap;">
        <!-- Bar Chart -->
        <div style="flex: 1; min-width: 400px;">
            <h3 style="margin-bottom: 24px;">Budget vs Actual by Cost Center</h3>
            <div style="height: 300px; width: 100%;">
                <canvas id="budgetActualChart"></canvas>
            </div>
        </div>
        <!-- Pie Chart -->
        <div style="flex: 0 0 350px; min-width: 300px;">
            <h3 style="margin-bottom: 24px;">Budget Distribution</h3>
            <div style="height: 300px; width: 100%; display: flex; align-items: center; justify-content: center;">
                <canvas id="budgetPieChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="glass-widget">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3>Detailed Breakdown</h3>
        <div style="background: rgba(255,255,255,0.5); padding: 8px 16px; border-radius: 50px; font-size: 12px;">
            <i class="ri-filter-3-line"></i> Filter
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Cost Center</th>
                    <th>Budget</th>
                    <th>Actual</th>
                    <th>Remaining</th>
                    <th>Utilization</th>
                    <th>Timeline</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($budgetData as $row): ?>
                    <tr>
                        <td>
                            <a href="/Furniture/views/cost_centers/drilldown.php?id=<?= $row['cost_center_id'] ?>"
                                style="font-weight: 600; font-size: 14px; text-decoration: none; color: var(--text-primary);">
                                <?= sanitize($row['cost_center_name']) ?>
                            </a>
                        </td>
                        <td style="font-weight: 500;"><?= formatCurrency($row['budget_amount']) ?></td>
                        <td style="font-weight: 500;"><?= formatCurrency($row['actual_spend']) ?></td>
                        <td
                            style="font-weight: 600; color: <?= $row['remaining'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                            <?= formatCurrency($row['remaining']) ?>
                        </td>
                        <td style="width: 150px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div
                                    style="flex: 1; height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                                    <div
                                        style="height: 100%; width: <?= min($row['utilization'], 100) ?>%; background: <?= $row['health_color'] === 'success' ? '#10b981' : ($row['health_color'] === 'warning' ? '#f59e0b' : '#ef4444') ?>;">
                                    </div>
                                </div>
                                <span style="font-size: 11px; width: 30px;"><?= $row['utilization'] ?>%</span>
                            </div>
                        </td>
                        <td style="font-size: 12px; color: var(--text-secondary);"><?= $row['time_elapsed'] ?>% elapsed</td>
                        <td>
                            <span class="badge badge-<?= $row['health_color'] ?>">
                                <?= $row['health_status'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bar Chart - Budget vs Actual
    const ctx = document.getElementById('budgetActualChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartData['labels']) ?>,
            datasets: [
                {
                    label: 'Budget',
                    data: <?= json_encode($chartData['budget']) ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1
                },
                {
                    label: 'Actual',
                    data: <?= json_encode($chartData['actual']) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Pie Chart - Budget Distribution
    const pieColors = [
        'rgba(99, 102, 241, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(34, 211, 238, 0.8)',
        'rgba(251, 146, 60, 0.8)'
    ];
    
    const pieCtx = document.getElementById('budgetPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($chartData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($chartData['budget']) ?>,
                backgroundColor: pieColors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': ₹' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>