<?php
// File: /advertising/dashboard.php

require_once __DIR__ . '/init.php';

// Get advertiser information
$advertiser_id = $_SESSION['advertiser_id'];
$stmt = $conn->prepare("SELECT * FROM advertisers WHERE id = ?");
$stmt->bind_param("i", $advertiser_id);
$stmt->execute();
$advertiser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$advertiser) {
    $_SESSION['error_message'] = "Advertiser account not found.";
    header('Location: logout.php');
    exit();
}

// Get campaign statistics (for last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Get total campaigns
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM campaigns WHERE advertiser_id = ?");
$stmt->bind_param("i", $advertiser_id);
$stmt->execute();
$total_campaigns = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get active campaigns
$stmt = $conn->prepare("SELECT COUNT(*) as active FROM campaigns WHERE advertiser_id = ? AND status = 'active'");
$stmt->bind_param("i", $advertiser_id);
$stmt->execute();
$active_campaigns = $stmt->get_result()->fetch_assoc()['active'];
$stmt->close();

// Get recent campaigns
$stmt = $conn->prepare("SELECT id, name, status, created_at FROM campaigns WHERE advertiser_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $advertiser_id);
$stmt->execute();
$recent_campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get campaign performance stats
$stmt = $conn->prepare("
    SELECT 
        SUM(cs.impressions) as total_impressions, 
        SUM(cs.clicks) as total_clicks, 
        SUM(cs.cost) as total_spend
    FROM campaign_stats cs
    JOIN campaigns c ON cs.campaign_id = c.id
    WHERE c.advertiser_id = ? AND cs.stat_date BETWEEN ? AND ?
");
$stmt->bind_param("iss", $advertiser_id, $start_date, $end_date);
$stmt->execute();
$performance = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate metrics
$total_impressions = $performance['total_impressions'] ?? 0;
$total_clicks = $performance['total_clicks'] ?? 0;
$total_spend = $performance['total_spend'] ?? 0;

$ctr = $total_impressions > 0 ? ($total_clicks / $total_impressions) * 100 : 0;
$cpm = $total_impressions > 0 ? ($total_spend / $total_impressions) * 1000 : 0;
$cpc = $total_clicks > 0 ? $total_spend / $total_clicks : 0;

// Get daily statistics for charts
$stmt = $conn->prepare("
    SELECT 
        cs.stat_date, 
        SUM(cs.impressions) as daily_impressions, 
        SUM(cs.clicks) as daily_clicks, 
        SUM(cs.cost) as daily_cost
    FROM campaign_stats cs
    JOIN campaigns c ON cs.campaign_id = c.id
    WHERE c.advertiser_id = ? AND cs.stat_date BETWEEN ? AND ?
    GROUP BY cs.stat_date
    ORDER BY cs.stat_date ASC
");
$stmt->bind_param("iss", $advertiser_id, $start_date, $end_date);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Format data for chart
$chart_data = [
    'dates' => [],
    'impressions' => [],
    'clicks' => [],
    'spend' => []
];

foreach ($daily_stats as $stat) {
    $chart_data['dates'][] = $stat['stat_date'];
    $chart_data['impressions'][] = (int)$stat['daily_impressions'];
    $chart_data['clicks'][] = (int)$stat['daily_clicks'];
    $chart_data['spend'][] = (float)$stat['daily_cost'];
}

$page_title = "Dashboard";
require_once __DIR__ . '/templates/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Advertiser Dashboard</h1>
    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($advertiser['company_name'] ?? $_SESSION['username']); ?>!</p>
    
    <!-- Account Status -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <div class="avatar-lg bg-primary-subtle rounded">
                            <i class="bi bi-wallet2 fs-1 text-primary"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Account Balance</h6>
                        <h3 class="mb-0">$<?php echo number_format($advertiser['balance'] ?? 0, 2); ?></h3>
                    </div>
                    <div class="ms-auto">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Funds
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-6 border-end">
                            <div class="p-3 text-center">
                                <h5 class="mb-0"><?php echo number_format($active_campaigns); ?></h5>
                                <span class="text-muted">Active Campaigns</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center">
                                <h5 class="mb-0"><?php echo number_format($total_campaigns - $active_campaigns); ?></h5>
                                <span class="text-muted">Paused Campaigns</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Impressions</h6>
                            <h3 class="mb-0"><?php echo number_format($total_impressions); ?></h3>
                        </div>
                        <div class="avatar bg-primary-subtle rounded">
                            <i class="bi bi-eye fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Clicks</h6>
                            <h3 class="mb-0"><?php echo number_format($total_clicks); ?></h3>
                        </div>
                        <div class="avatar bg-success-subtle rounded">
                            <i class="bi bi-cursor fs-4 text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-success-subtle text-success">CTR: <?php echo number_format($ctr, 2); ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Spend</h6>
                            <h3 class="mb-0">$<?php echo number_format($total_spend, 2); ?></h3>
                        </div>
                        <div class="avatar bg-danger-subtle rounded">
                            <i class="bi bi-cash-stack fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Cost Metrics</h6>
                            <div class="d-flex flex-column">
                                <span class="text-primary fw-medium">CPM: $<?php echo number_format($cpm, 2); ?></span>
                                <span class="text-success fw-medium">CPC: $<?php echo number_format($cpc, 4); ?></span>
                            </div>
                        </div>
                        <div class="avatar bg-info-subtle rounded">
                            <i class="bi bi-graph-up fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart and Recent Campaigns -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Performance Trend</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active" id="viewImpressions">Impressions</button>
                        <button class="btn btn-outline-secondary" id="viewClicks">Clicks</button>
                        <button class="btn btn-outline-secondary" id="viewSpend">Spend</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Recent Campaigns</h5>
                    <a href="campaigns.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_campaigns)): ?>
                    <div class="text-center p-4">
                        <img src="../assets/images/empty-state.svg" alt="No campaigns" class="mb-3" style="width: 120px;">
                        <h6>No campaigns yet</h6>
                        <p class="text-muted">Start creating your first campaign.</p>
                        <a href="create-campaign.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-2"></i> Create Campaign
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_campaigns as $campaign): 
                            $status_class = $campaign['status'] === 'active' ? 'success' : 'warning';
                        ?>
                        <a href="campaign-details.php?id=<?php echo $campaign['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($campaign['name']); ?></h6>
                                <small class="badge bg-<?php echo $status_class; ?>-subtle text-<?php echo $status_class; ?>">
                                    <?php echo ucfirst($campaign['status']); ?>
                                </small>
                            </div>
                            <small class="text-muted">Created: <?php echo date('M d, Y', strtotime($campaign['created_at'])); ?></small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent border-top">
                    <a href="create-campaign.php" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-circle me-2"></i> Create New Campaign
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="create-campaign.php" class="card h-100 border-0 shadow-hover-sm text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="avatar bg-primary-subtle mx-auto mb-3">
                                        <i class="bi bi-plus-circle fs-4 text-primary"></i>
                                    </div>
                                    <h6 class="mb-2">Create Campaign</h6>
                                    <p class="text-muted small mb-0">Launch a new advertising campaign</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="creatives.php" class="card h-100 border-0 shadow-hover-sm text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="avatar bg-success-subtle mx-auto mb-3">
                                        <i class="bi bi-images fs-4 text-success"></i>
                                    </div>
                                    <h6 class="mb-2">Manage Creatives</h6>
                                    <p class="text-muted small mb-0">Upload and manage ad creatives</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="reports.php" class="card h-100 border-0 shadow-hover-sm text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="avatar bg-info-subtle mx-auto mb-3">
                                        <i class="bi bi-bar-chart fs-4 text-info"></i>
                                    </div>
                                    <h6 class="mb-2">View Reports</h6>
                                    <p class="text-muted small mb-0">Analyze campaign performance</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Funds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="deposit-funds.php" method="post">
                    <div class="mb-3">
                        <label for="depositAmount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="depositAmount" name="amount" min="10" step="0.01" value="100" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <div class="d-flex gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="cardPayment" value="card" checked>
                                <label class="form-check-label" for="cardPayment">
                                    <i class="bi bi-credit-card me-1"></i> Credit Card
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="paypalPayment" value="paypal">
                                <label class="form-check-label" for="paypalPayment">
                                    <i class="bi bi-paypal me-1"></i> PayPal
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="bankPayment" value="bank">
                                <label class="form-check-label" for="bankPayment">
                                    <i class="bi bi-bank me-1"></i> Bank Transfer
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="cardDetails">
                        <div class="mb-3">
                            <label for="cardNumber" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="cardNumber" placeholder="XXXX XXXX XXXX XXXX">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cardExpiry" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="cardExpiry" placeholder="MM/YY">
                            </div>
                            <div class="col-md-6">
                                <label for="cardCvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cardCvv" placeholder="XXX">
                            </div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Performance Chart
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    const dates = <?php echo json_encode($chart_data['dates']); ?>;
    const impressions = <?php echo json_encode($chart_data['impressions']); ?>;
    const clicks = <?php echo json_encode($chart_data['clicks']); ?>;
    const spend = <?php echo json_encode($chart_data['spend']); ?>;
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Impressions',
                data: impressions,
                borderColor: 'rgba(67, 97, 238, 1)',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
    
    // Toggle chart view
    document.getElementById('viewImpressions').addEventListener('click', function() {
        updateChart('Impressions', impressions, 'rgba(67, 97, 238, 1)', 'rgba(67, 97, 238, 0.1)');
        setActiveButton(this);
    });
    
    document.getElementById('viewClicks').addEventListener('click', function() {
        updateChart('Clicks', clicks, 'rgba(16, 185, 129, 1)', 'rgba(16, 185, 129, 0.1)');
        setActiveButton(this);
    });
    
    document.getElementById('viewSpend').addEventListener('click', function() {
        updateChart('Spend', spend, 'rgba(244, 63, 94, 1)', 'rgba(244, 63, 94, 0.1)');
        setActiveButton(this);
    });
    
    function updateChart(label, data, borderColor, backgroundColor) {
        chart.data.datasets[0].label = label;
        chart.data.datasets[0].data = data;
        chart.data.datasets[0].borderColor = borderColor;
        chart.data.datasets[0].backgroundColor = backgroundColor;
        chart.update();
    }
    
    function setActiveButton(button) {
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
    }
    
    // Payment method toggling
    const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
    const cardDetails = document.getElementById('cardDetails');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
    });
    
    // Display timestamp and user info
    const timestampDiv = document.createElement('div');
    timestampDiv.classList.add('text-center', 'text-muted', 'small', 'mt-4');
    timestampDiv.textContent = 'Last updated: 2025-07-24 03:03:14 UTC | User: simoncode12';
    document.querySelector('.container-fluid').appendChild(timestampDiv);
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>