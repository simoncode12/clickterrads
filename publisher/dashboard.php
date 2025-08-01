<?php
// File: /publisher/dashboard.php (REDESIGNED & FIXED - Removed problematic query)

require_once __DIR__ . '/init.php';

// --- 1. GET PUBLISHER INFO ---
$publisher_id = $_SESSION['publisher_id'];
$stmt_user = $conn->prepare("SELECT revenue_share FROM users WHERE id = ?");
$stmt_user->bind_param("i", $publisher_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
$revenue_share = (float)($user['revenue_share'] ?? 0);


// --- 2. SETUP FILTER & DATA FETCHING (OPTIMIZED) ---
$range = $_GET['range'] ?? 'this_week';
$today = date('Y-m-d');

switch ($range) {
    case 'today':
        $date_from = $date_to = $today;
        $range_text = 'Today';
        break;
    case 'yesterday':
        $date_from = $date_to = date('Y-m-d', strtotime('-1 day'));
        $range_text = 'Yesterday';
        break;
    case 'this_month':
        $date_from = date('Y-m-d', strtotime('first day of this month'));
        $date_to = $today;
        $range_text = 'This Month';
        break;
    case 'custom':
        $date_from = $_GET['date_from'] ?? $today;
        $date_to = $_GET['date_to'] ?? $today;
        $range_text = 'Custom Range';
        break;
    case 'this_week':
    default:
        $date_from = date('Y-m-d', strtotime('monday this week'));
        $date_to = $today;
        $range_text = 'This Week';
        break;
}

// Helper
function get_query_results($conn, $sql, $params = [], $types = '') {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { return []; }
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $data;
}

// Semua query sekarang HANYA ke tabel ringkasan yang cepat
$base_summary_query = "FROM stats_daily_summary sds JOIN zones z ON sds.zone_id = z.id JOIN sites si ON z.site_id = si.id WHERE si.user_id = ?";

// Data untuk kartu ringkasan
$summary_sql = "SELECT SUM(sds.impressions) as total_impressions, SUM(sds.clicks) as total_clicks, SUM(sds.publisher_payout) as total_earnings {$base_summary_query} AND sds.stat_date BETWEEN ? AND ?";
$summary_data = get_query_results($conn, $summary_sql, [$publisher_id, $date_from, $date_to], "iss")[0] ?? [];
$total_impressions = $summary_data['total_impressions'] ?? 0;
$total_clicks = $summary_data['total_clicks'] ?? 0;
$total_earnings = $summary_data['total_earnings'] ?? 0;
$total_ctr = ($total_impressions > 0) ? ($total_clicks / $total_impressions) * 100 : 0;

// Data untuk chart
// --- PERBAIKAN UTAMA ADA DI SINI: BETWEEN ? AND ? ---
$chart_sql = "SELECT sds.stat_date, SUM(sds.impressions) as daily_impressions, SUM(sds.publisher_payout) as daily_earnings {$base_summary_query} AND sds.stat_date BETWEEN ? AND ? GROUP BY sds.stat_date ORDER BY sds.stat_date ASC";
$chart_result = get_query_results($conn, $chart_sql, [$publisher_id, $date_from, $date_to], "iss");

// Proses data untuk Chart.js
$chart_impressions_data = []; $chart_earnings_data = [];
if (!empty($chart_result)) {
    $period = new DatePeriod(new DateTime($date_from), new DateInterval('P1D'), (new DateTime($date_to))->modify('+1 day'));
    $daily_data = array_column($chart_result, null, 'stat_date');
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $chart_impressions_data[] = ['x' => $date_str, 'y' => (int)($daily_data[$date_str]['daily_impressions'] ?? 0)];
        $chart_earnings_data[] = ['x' => $date_str, 'y' => round((float)($daily_data[$date_str]['daily_earnings'] ?? 0), 6)];
    }
}
$chart_data_json = json_encode(['impressions' => $chart_impressions_data, 'earnings' => $chart_earnings_data]);

// HAPUS QUERY TOP SITES YANG MENYEBABKAN ERROR

require_once __DIR__ . '/templates/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="fw-bold mb-1">Dashboard</h4>
        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['publisher_username']); ?>!</p>
    </div>
    <div class="date-filter mt-3 mt-md-0">
        <a href="?range=today" class="btn <?php echo ($range == 'today') ? 'active' : 'btn-light'; ?>">Today</a>
        <a href="?range=yesterday" class="btn <?php echo ($range == 'yesterday') ? 'active' : 'btn-light'; ?>">Yesterday</a>
        <a href="?range=this_week" class="btn <?php echo ($range == 'this_week') ? 'active' : 'btn-light'; ?>">This Week</a>
        <a href="?range=this_month" class="btn <?php echo ($range == 'this_month') ? 'active' : 'btn-light'; ?>">This Month</a>
        <form method="GET" class="custom-date-form">
            <input type="hidden" name="range" value="custom">
            <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            <span class="text-muted">to</span>
            <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Estimated Earnings</div>
                    <div class="stat-value text-success">$<?php echo number_format($total_earnings, 4); ?></div>
                    <div class="small text-muted mt-2"><?php echo $range_text; ?></div>
                </div>
                <i class="bi bi-cash-coin stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Impressions</div>
                    <div class="stat-value"><?php echo number_format($total_impressions); ?></div>
                    <div class="small text-muted mt-2"><?php echo $range_text; ?></div>
                </div>
                <i class="bi bi-eye stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Clicks</div>
                    <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
                    <div class="small text-muted mt-2"><?php echo $range_text; ?></div>
                </div>
                <i class="bi bi-cursor stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">CTR</div>
                    <div class="stat-value"><?php echo number_format($total_ctr, 2); ?>%</div>
                    <div class="small text-muted mt-2"><?php echo $range_text; ?></div>
                </div>
                <i class="bi bi-pie-chart stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title">Performance Overview</h5>
        <div class="dropdown">
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#"><i class="bi bi-download me-2"></i>Export Data</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-arrow-repeat me-2"></i>Refresh</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body p-4">
        <div style="height: 400px;">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="sites.php?action=new" class="card h-100 text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                    <i class="bi bi-plus-lg text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Add New Site</h6>
                                    <p class="text-muted small mb-0">Create a new website to monetize</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="withdraw.php" class="card h-100 text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                                    <i class="bi bi-cash text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Request Payout</h6>
                                    <p class="text-muted small mb-0">Withdraw your earnings</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="support.php" class="card h-100 text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                                    <i class="bi bi-headset text-info"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Get Support</h6>
                                    <p class="text-muted small mb-0">Contact our support team</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart');
    if (ctx) {
        const chartData = <?php echo $chart_data_json; ?>;
        
        // Create gradient fill for impressions line
        const gradientFill = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradientFill.addColorStop(0, 'rgba(67, 97, 238, 0.3)');
        gradientFill.addColorStop(1, 'rgba(67, 97, 238, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Impressions',
                    data: chartData.impressions,
                    yAxisID: 'yViews',
                    borderColor: 'rgb(67, 97, 238)',
                    backgroundColor: gradientFill,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgb(67, 97, 238)',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHitRadius: 10
                }, {
                    label: 'Earnings ($)',
                    data: chartData.earnings,
                    yAxisID: 'yEarnings',
                    borderColor: '#4ade80',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4ade80',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHitRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 6,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#111827',
                        bodyColor: '#4b5563',
                        borderColor: 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            title: function(tooltipItems) {
                                const date = new Date(tooltipItems[0].parsed.x);
                                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label === 'Earnings ($)') {
                                    return label + ': $' + context.parsed.y.toFixed(4);
                                }
                                return label + ': ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM d, yyyy'
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 11
                            },
                            color: '#9ca3af'
                        }
                    },
                    yViews: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Impressions',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12
                            },
                            color: '#4b5563'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 11
                            },
                            color: '#9ca3af'
                        }
                    },
                    yEarnings: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Earnings (USD)',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12
                            },
                            color: '#4b5563'
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            callback: value => '$' + value.toFixed(2),
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 11
                            },
                            color: '#9ca3af'
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
