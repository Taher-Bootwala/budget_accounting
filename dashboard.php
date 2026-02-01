<?php
/**
 * ============================================================================
 * ADMIN DASHBOARD - OVERVIEW PANEL
 * ============================================================================
 * 
 * Main Control Center for Budget Accounting & Analytics
 * 
 * This is the primary interface for admins to monitor financial health,
 * track budgets, and interact with the AI-powered analysis features.
 * 
 * UI COMPONENTS:
 * 1. Account Value Card - Shows total budget with quick actions
 * 2. Timeframe Toggle - Switch between Week/Month/Year views
 * 3. Financial Pulse - Real-time alerts for budget issues
 * 4. Mini Charts - Spending trends and transaction activity
 * 5. Intelligence Hub - AI search (Grok integration)
 * 6. Spending Overview - Detailed chart with cost center breakdown
 * 
 * AI FEATURES:
 * - Natural language search bar ("Ask Grok...")
 * - Suggested prompts for common queries
 * - AI response modal with formatted insights
 * 
 * DESIGN:
 * - Premium glassmorphism aesthetic
 * - Furniture-inspired warm color palette
 * - Responsive grid layout
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/controllers/BudgetEngine.php';

requireAdmin();

$timeframe = $_GET['timeframe'] ?? 'month';
$kpis = BudgetEngine::getDashboardKPIs($timeframe);
$pageTitle = 'Overview Panel';
include __DIR__ . '/views/layouts/header.php';
?>

<script>
    // Early declaration of AI search function (before HTML elements that use it)
    window.performSearch = async function (query) {
        if (!query || !query.trim()) return;

        console.log('AI Search triggered for:', query);

        const modal = document.getElementById('aiResponseModal');
        const responseText = document.getElementById('aiResponseText');
        const queryDisplay = document.getElementById('aiQueryDisplay');

        if (!modal || !responseText || !queryDisplay) {
            console.error('AI Modal elements not found! Modal:', modal, 'ResponseText:', responseText, 'QueryDisplay:', queryDisplay);
            alert('AI Modal not loaded yet. Please wait for page to fully load.');
            return;
        }

        modal.style.display = 'flex';
        queryDisplay.textContent = query;
        responseText.innerHTML = '<div style="display: flex; align-items: center; gap: 12px;"><div class="ai-loading"></div> Analyzing your query...</div>';

        try {
            const response = await fetch('/Furniture/api/grok.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query.trim() })
            });

            console.log('API Response status:', response.status);

            if (!response.ok) {
                throw new Error('Network error: ' + response.statusText);
            }

            const data = await response.json();
            console.log('API Data:', data);

            if (data.error) {
                responseText.innerHTML = '<div style="color: #C62828;">⚠️ ' + data.error + '</div>';
            } else {
                let formattedResponse = data.response
                    .replace(/\n/g, '<br>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/•/g, '<br>•');
                responseText.innerHTML = formattedResponse;
            }
        } catch (error) {
            console.error('AI Search Error:', error);
            responseText.innerHTML = '<div style="color: #C62828;">⚠️ Connection error: ' + error.message + '</div>';
        }

        const searchInput = document.getElementById('globalSearchInput');
        if (searchInput) searchInput.value = '';
    };

    window.closeAiModal = function () {
        const modal = document.getElementById('aiResponseModal');
        if (modal) modal.style.display = 'none';
    };
</script>

<!-- Large Title Overlay -->
<div class="page-header anim-fade-up">
    <div
        style="font-size: 12px; color: var(--text-secondary); opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
        Updated <?= date('M d, Y') ?> at <?= date('h:i A') ?>
    </div>
    <h1>Overview Panel</h1>
</div>

<!-- Main Dashboard Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; padding-bottom: 40px;">

    <!-- LEFT SECTION -->
    <div style="display: flex; flex-direction: column; gap: 32px;">

        <!-- Top Row: Account Card + Charts -->
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 24px; align-items: stretch;">

            <!-- 1. Account Value Card -->
            <div class="account-card anim-fade-up delay-1">
                <div
                    style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--text-secondary);">
                    <span>Total Budget</span>
                    <span>FY <?= date('Y') ?></span>
                </div>

                <div class="balance-container">
                    <div class="balance-value">
                        <?php
                        $formatted = formatCurrency($kpis['total_budget']);
                        $parts = explode('.', $formatted);
                        echo $parts[0];
                        if (isset($parts[1])) {
                            echo '<span class="decimal-small">.' . $parts[1] . '</span>';
                        }
                        ?>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">Active Allocations
                    </div>
                </div>

                <div class="action-grid">
                    <a href="/Furniture/views/budgets/index.php" class="action-btn" style="text-decoration: none;">
                        <div class="action-icon"><i class="ri-add-line"></i></div>
                        <span class="action-label">Add Funds</span>
                    </a>
                    <a href="/Furniture/views/documents/create.php" class="action-btn" style="text-decoration: none;">
                        <div class="action-icon" style="background: white; color: var(--text-primary);"><i
                                class="ri-file-add-line"></i></div>
                        <span class="action-label">New Bill</span>
                    </a>
                    <a href="/Furniture/views/reports/budget_vs_actual.php" class="action-btn"
                        style="text-decoration: none;">
                        <div class="action-icon" style="background: white; color: var(--text-primary);"><i
                                class="ri-pie-chart-2-line"></i></div>
                        <span class="action-label">Reports</span>
                    </a>
                </div>
            </div>

            <?php
            $tf = $_GET['timeframe'] ?? 'month';
            ?>

            <!-- 2. Right Column: Spending + Activity Charts -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Spending Overview -->
                <div class="glass-widget anim-fade-up delay-2" style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-weight: 600; font-size: 13px;">Spending Overview</span>
                    </div>
                    <div style="font-size: 22px; font-weight: 700; margin-bottom: 4px;">
                        <?= formatCurrency($kpis['total_actual']) ?>
                    </div>
                    <div style="height: 50px; margin-top: 8px;"><canvas id="miniChart1"></canvas></div>
                </div>

                <!-- Activity -->
                <div class="glass-widget anim-fade-up delay-2" style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-weight: 600; font-size: 13px;">Activity</span>
                    </div>
                    <div style="font-size: 22px; font-weight: 700; margin-bottom: 4px;">
                        <?= array_sum(BudgetEngine::getTransactionVolumeTrend($tf)['data']) ?>
                        <span style="font-size: 12px; font-weight: 500; opacity: 0.6;">transactions</span>
                    </div>
                    <div style="height: 50px; margin-top: 8px;"><canvas id="miniChart2"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: AI Command Center -->
        <div class="glass-widget anim-fade-up delay-3"
            style="min-height: 140px; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: visible; padding: 32px;">

            <!-- Floating Label -->
            <div
                style="position: absolute; top: -12px; left: 32px; background: linear-gradient(135deg, #D4A574, #A1887F); padding: 5px 16px; border-radius: 20px; color: white; font-size: 11px; font-weight: 700; box-shadow: 0 4px 12px rgba(161, 136, 127, 0.4); letter-spacing: 0.5px;">
                <i class="ri-sparkling-fill" style="margin-right: 4px;"></i> INTELLIGENCE HUB
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 40px;">

                <!-- Approvals (Left) -->
                <div style="min-width: 180px;">
                    <?php
                    require_once __DIR__ . '/controllers/DocumentController.php';
                    $pendingCount = DocumentController::getPendingApprovalsCount();
                    ?>
                    <div
                        style="font-size: 12px; font-weight: 700; color: var(--accent-wood); margin-bottom: 4px; letter-spacing: 0.5px; text-transform: uppercase;">
                        Action Items</div>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div
                            style="font-size: 48px; font-weight: 700; line-height: 1; letter-spacing: -2px; color: var(--text-primary);">
                            <?= $pendingCount ?>
                        </div>
                        <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.4;">
                            Documents<br>Pending
                        </div>
                    </div>
                </div>

                <!-- AI Search Input (Right) -->
                <div style="flex: 1;">
                    <!-- Suggested Prompts -->
                    <div style="display: flex; gap: 10px; margin-bottom: 14px; justify-content: flex-end;">
                        <span
                            style="font-size: 11px; font-weight: 600; color: var(--text-light); align-self: center; margin-right: 4px;">Try
                            asking:</span>
                        <button type="button" onclick="window.performSearch('Show me spending this week')"
                            style="font-size: 11px; padding: 6px 14px; background: rgba(255,255,255,0.5); border: 1px solid rgba(0,0,0,0.05); border-radius: 20px; color: var(--text-secondary); cursor: pointer; transition: 0.2s;">
                            "Spending trend?"</button>
                        <button type="button" onclick="window.performSearch('Where can we cut costs?')"
                            style="font-size: 11px; padding: 6px 14px; background: rgba(255,255,255,0.5); border: 1px solid rgba(0,0,0,0.05); border-radius: 20px; color: var(--text-secondary); cursor: pointer; transition: 0.2s;">
                            "Cut costs?"</button>
                    </div>

                    <div class="ai-input-wrapper"
                        style="padding: 14px 24px; background: white; border: 1px solid rgba(139, 90, 43, 0.1); border-radius: 20px; display: flex; align-items: center; gap: 18px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                        <i class="ri-search-2-line"
                            style="font-size: 20px; color: var(--accent-wood); opacity: 0.7;"></i>
                        <input type="text" id="globalSearchInput"
                            placeholder="Ask Grok to analyze budgets, find invoices, or check trends..."
                            style="background: transparent; border: none; outline: none; flex: 1; font-size: 15px; color: var(--text-primary); font-family: 'Inter', sans-serif;">
                        <button type="button" id="aiSearchBtn"
                            style="background: var(--accent-wood); width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 10px rgba(139, 90, 43, 0.2); border: none;">
                            <i class="ri-arrow-right-line" style="color: white; font-size: 18px;"></i>
                        </button>
                    </div>
                    <script>
                        document.getElementById('aiSearchBtn').addEventListener('click', function () {
                            var query = document.getElementById('globalSearchInput').value;
                            console.log('Search button clicked, query:', query);
                            window.performSearch(query);
                        });
                        document.getElementById('globalSearchInput').addEventListener('keypress', function (e) {
                            if (e.key === 'Enter') {
                                console.log('Enter pressed, query:', this.value);
                                window.performSearch(this.value);
                            }
                        });
                    </script>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT SECTION (Phone Widget) -->
    <div style="display: flex; flex-direction: column; gap: 24px;" class="anim-fade-up delay-1">

        <!-- Glass Phone Container -->
        <div class="phone-widget">
            <!-- Header -->
            <div style="padding: 24px; color: var(--text-secondary);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-weight: 600;"><?= date('F j') ?></h3>
                    <div class="clickable"
                        style="width: 32px; height: 32px; border: 1px solid rgba(0,0,0,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="ri-notification-3-line"></i>
                    </div>
                </div>
            </div>

            <!-- Calendar Strip -->
            <div class="calendar-strip">
                <?php
                $today = new DateTime();
                for ($i = 0; $i < 7; $i++) {
                    $date = clone $today;
                    $date->modify("+$i days");
                    $dayName = $date->format('D');
                    $dayNum = $date->format('d');
                    $active = $i === 0 ? 'active' : ''; // Highlight today
                    echo "<div class='cal-day {$active}'>";
                    echo "<span>{$dayName}</span>";
                    echo "<strong>{$dayNum}</strong>";
                    echo "</div>";
                }
                ?>
            </div>

            <!-- White Card with Transactions -->
            <div class="transaction-list-card">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <span style="font-weight: 700; font-size: 13px;">Recent Transactions</span>
                    <a href="/Furniture/views/payments/index.php" style="color: var(--text-light);"><i
                            class="ri-arrow-right-line"></i></a>
                </div>

                <?php
                // Fetch Real Data
                require_once __DIR__ . '/controllers/PaymentController.php';
                $recentPayments = array_slice(PaymentController::getAll(), 0, 5); // Get last 5
                
                if (empty($recentPayments)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 20px; font-size: 13px;">No recent
                        transactions</div>
                <?php else:
                    foreach ($recentPayments as $payment):
                        $isIncome = false; // Assuming payments are expenses for now unless specified
                        $color = $isIncome ? 'success' : 'danger';
                        $sign = $isIncome ? '+' : '-';
                        ?>
                        <div class="trans-item">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div class="brand-icon" style="background: rgba(0,0,0,0.03);"><i
                                        class="ri-shopping-bag-3-line"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 13px;">
                                        <?= sanitize($payment['document_number'] ?? 'Payment') ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-light);">
                                        <?= formatDate($payment['payment_date']) ?>
                                    </div>
                                </div>
                            </div>
                            <div style="font-weight: 700; font-size: 13px; color: var(--text-primary);">
                                <?= $sign ?>         <?= formatCurrency($payment['paid_amount']) ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Budget Gauge -->
        <div class="glass-widget">
            <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
                <span style="font-weight: 600; font-size: 13px;">Budget Limit</span>
                <span class="badge badge-<?= $kpis['health']['color'] ?>"><?= $kpis['health']['status'] ?></span>
            </div>

            <div style="display: flex; align-items: flex-end; gap: 8px; margin-bottom: 16px;">
                <span
                    style="font-size: 28px; font-weight: 700; line-height: 1; color: var(--text-primary);"><?= formatCurrency($kpis['total_actual']) ?></span>
            </div>
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                used of <?= formatCurrency($kpis['total_budget']) ?>
            </div>

            <!-- Progress Bar -->
            <div
                style="height: 8px; background: rgba(0,0,0,0.05); border-radius: 6px; overflow: hidden; margin-bottom: 12px;">
                <div style="height: 100%; width: <?= min($kpis['utilization'], 100) ?>%; 
                            background: linear-gradient(90deg, 
                                <?= $kpis['health']['color'] === 'success' ? '#10b981, #34d399' : ($kpis['health']['color'] === 'warning' ? '#f59e0b, #fbbf24' : '#ef4444, #f87171') ?>
                            );
                            border-radius: 6px; transition: width 1s ease;">
                </div>
            </div>

            <div
                style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                <span style="color: var(--text-light);">0%</span>
                <span style="color: var(--text-secondary);">100%</span>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php
    $spendingTrend = BudgetEngine::getSpendingTrend($timeframe);
    $activityTrend = BudgetEngine::getTransactionVolumeTrend($timeframe);
    ?>

    // Premium Charts Configuration
    const canvas1 = document.getElementById('miniChart1');
    const ctx1 = canvas1.getContext('2d');

    // Wood Brown Gradient (Furniture Theme)
    const gradient1 = ctx1.createLinearGradient(0, 0, 0, 60);
    gradient1.addColorStop(0, 'rgba(139, 90, 43, 0.25)');
    gradient1.addColorStop(1, 'rgba(139, 90, 43, 0.0)');


    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(62, 39, 35, 0.9)',
                padding: 10,
                cornerRadius: 8,
                titleFont: { size: 11 },
                bodyFont: { size: 12, weight: 'bold' }
            }
        },
        scales: {
            x: { display: false },
            y: { display: false, beginAtZero: true }
        },
        elements: {
            point: { radius: 0, hoverRadius: 4 },
            line: { tension: 0.4 }
        },
        layout: { padding: 0 }
    };

    // Spending Chart (Line) - Wood Brown Theme
    new Chart(canvas1, {
        type: 'line',
        data: {
            labels: <?= json_encode($spendingTrend['labels']) ?>,
            datasets: [{
                data: <?= json_encode($spendingTrend['data']) ?>,
                borderColor: '#8B5A2B',
                borderWidth: 2,
                backgroundColor: gradient1,
                fill: true
            }]
        },
        options: commonOptions
    });

    // Activity Chart (Bar) - Forest Green Theme
    new Chart(document.getElementById('miniChart2'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($activityTrend['labels']) ?>,
            datasets: [{
                data: <?= json_encode($activityTrend['data']) ?>,
                backgroundColor: '#2E7D32',
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: commonOptions
    });

    // AI Search Functionality
    // Make functions globally available
    window.performSearch = async function (query) {
        if (!query || !query.trim()) {
            return;
        }

        console.log('Starting AI search for:', query);

        const searchInput = document.getElementById('globalSearchInput');
        const modal = document.getElementById('aiResponseModal');
        const responseText = document.getElementById('aiResponseText');
        const queryDisplay = document.getElementById('aiQueryDisplay');

        if (!modal || !responseText || !queryDisplay) {
            console.error('AI Modal elements not found!');
            return;
        }

        // Show loading state
        modal.style.display = 'flex';
        queryDisplay.textContent = query;
        responseText.innerHTML = '<div style="display: flex; align-items: center; gap: 12px;"><div class="ai-loading"></div> Analyzing your query...</div>';

        try {
            console.log('Sending request to API...');
            const response = await fetch('/Furniture/api/grok.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query.trim() })
            });

            console.log('API Response status:', response.status);

            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }

            const data = await response.json();
            console.log('API Data received:', data);

            if (data.error) {
                responseText.innerHTML = '<div style="color: #C62828;">⚠️ ' + data.error + '</div>';
            } else {
                // Format the response with markdown-like styling
                let formattedResponse = data.response
                    .replace(/\n/g, '<br>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/•/g, '<br>•');
                responseText.innerHTML = formattedResponse;
            }
        } catch (error) {
            console.error('AI Search Error:', error);
            responseText.innerHTML = '<div style="color: #C62828;">⚠️ Connection error. Please Check Console for details.</div>';
        }

        if (searchInput) searchInput.value = '';
    };

    // Close modal function
    window.closeAiModal = function () {
        const modal = document.getElementById('aiResponseModal');
        if (modal) modal.style.display = 'none';
    };

    // Handle Enter key in search
    const aiSearchInput = document.getElementById('globalSearchInput');
    if (aiSearchInput) {
        aiSearchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                window.performSearch(this.value);
            }
        });
    }
</script>

<!-- AI Response Modal -->
<div id="aiResponseModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(62, 39, 35, 0.5); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; border-radius: 24px; width: 90%; max-width: 600px; max-height: 80vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <!-- Header -->
        <div
            style="padding: 20px 24px; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="background: linear-gradient(135deg, #8B5A2B, #C19A6B); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="ri-sparkling-2-fill" style="color: white; font-size: 18px;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: var(--text-primary);">Grok Analysis</div>
                    <div id="aiQueryDisplay"
                        style="font-size: 12px; color: var(--text-secondary); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    </div>
                </div>
            </div>
            <button onclick="closeAiModal()" style="background: none; border: none; cursor: pointer; padding: 8px;">
                <i class="ri-close-line" style="font-size: 24px; color: var(--text-secondary);"></i>
            </button>
        </div>

        <!-- Response Body -->
        <div style="padding: 24px; max-height: 60vh; overflow-y: auto;">
            <div id="aiResponseText" style="font-size: 14px; line-height: 1.7; color: var(--text-primary);"></div>
        </div>

        <!-- Footer -->
        <div
            style="padding: 16px 24px; border-top: 1px solid rgba(0,0,0,0.06); display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="closeAiModal()"
                style="padding: 10px 20px; background: var(--accent-wood); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">
                Got it
            </button>
        </div>
    </div>
</div>

<style>
    .ai-loading {
        width: 20px;
        height: 20px;
        border: 2px solid #C19A6B;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>