<?php
// File: /publisher/statistics.php (REDESIGNED - Modern UI with Advanced Features)

require_once __DIR__ . '/init.php';

// --- 1. SETUP & FILTER LOGIC ---
$publisher_id = $_SESSION['publisher_id'] ?? null;

// Jika publisher_id tidak ditemukan, arahkan atau tampilkan pesan error
if (!$publisher_id) {
    die("Akses tidak sah: Publisher ID tidak ditemukan.");
}

// Default ke 7 hari terakhir dari data yang tersedia (sampai kemarin)
// Menggunakan 'yesterday' untuk memastikan data sudah final dan ringkasan sudah dibuat
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('-1 day'));

$group_by = $_GET['group_by'] ?? 'date';
$filter_site_id = filter_input(INPUT_GET, 'site_id', FILTER_VALIDATE_INT);
$filter_zone_id = filter_input(INPUT_GET, 'zone_id', FILTER_VALIDATE_INT);

// Ambil revenue_share dari database (bisa di-cache di init.php jika sering diakses)
$revenue_share_query = get_query_results($conn, "SELECT revenue_share FROM users WHERE id = ?", [$publisher_id], "i");
$revenue_share = $revenue_share_query[0]['revenue_share'] ?? 0;

// Ambil daftar situs dan zona milik publisher untuk dropdown filter
$sites_list = get_query_results($conn, "SELECT id, url FROM sites WHERE user_id = ? AND status = 'approved' ORDER BY url ASC", [$publisher_id], "i");
$zones_list = [];
if ($filter_site_id) {
    // Pastikan zona yang diambil juga milik publisher melalui site_id yang terkait dengan publisher_id
    $zones_list = get_query_results($conn, "SELECT z.id, z.name FROM zones z JOIN sites s ON z.site_id = s.id WHERE z.site_id = ? AND s.user_id = ?", [$filter_site_id, $publisher_id], "ii");
}

// --- 2. MAIN OPTIMIZED QUERY DARI stats_daily_summary ---
$group_by_select = "T.stat_date as group_field";
$group_by_clause = "GROUP BY T.stat_date";
$main_column_header = "Date";
$join_clause = "LEFT JOIN zones z ON T.zone_id = z.id LEFT JOIN sites si ON z.site_id = si.id";

switch ($group_by) {
    case 'site':
        $group_by_select = "si.url as group_field";
        $group_by_clause = "GROUP BY si.id, si.url"; // Group by ID juga untuk memastikan keunikan jika ada URL yang sama
        $main_column_header = "Site";
        break;
    case 'zone':
        // Pastikan format group_field sesuai dengan kebutuhan Anda
        $group_by_select = "CONCAT(z.name, ' (', z.size, ')') as group_field";
        $group_by_clause = "GROUP BY z.id, z.name, z.size"; // Group by ID juga
        $main_column_header = "Zone";
        break;
    case 'country':
        $group_by_select = "T.country as group_field";
        $group_by_clause = "GROUP BY T.country";
        $main_column_header = "Country";
        break;
}

$sql = "
    SELECT
        {$group_by_select},
        SUM(T.impressions) AS total_impressions,
        SUM(T.clicks) AS total_clicks,
        SUM(T.publisher_payout) AS total_earnings
    FROM stats_daily_summary AS T
    {$join_clause}
";

$params = [$date_from, $date_to, $publisher_id];
$types = "ssi";
$where_clauses = ["T.stat_date BETWEEN ? AND ?", "si.user_id = ?"];

if ($filter_site_id) {
    $where_clauses[] = "si.id = ?";
    $params[] = $filter_site_id;
    $types .= "i";
}
if ($filter_zone_id) {
    $where_clauses[] = "z.id = ?";
    $params[] = $filter_zone_id;
    $types .= "i";
}

$sql .= " WHERE " . implode(' AND ', $where_clauses);
$sql .= " {$group_by_clause} ORDER BY group_field DESC";
$report_rows = get_query_results($conn, $sql, $params, $types);

// --- 3. DATA UNTUK SUMMARY CARDS & CHART ---
$totals = ['impressions' => 0, 'clicks' => 0, 'earnings' => 0];
$chart_impressions_data = [];
$chart_earnings_data = [];
$daily_data_for_chart = [];

if ($group_by === 'date' && !empty($report_rows)) {
    foreach($report_rows as $row) {
        $daily_data_for_chart[$row['group_field']] = $row;
    }
}

// Hitung total keseluruhan
foreach($report_rows as $row) {
    $totals['impressions'] += $row['total_impressions'];
    $totals['clicks'] += $row['total_clicks'];
    $totals['earnings'] += $row['total_earnings'];
}

// Siapkan data untuk chart jika dikelompokkan berdasarkan tanggal
if ($group_by === 'date') {
    $period = new DatePeriod(new DateTime($date_from), new DateInterval('P1D'), (new DateTime($date_to))->modify('+1 day'));
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $impressions = (int)($daily_data_for_chart[$date_str]['total_impressions'] ?? 0);
        $earnings = (float)($daily_data_for_chart[$date_str]['total_earnings'] ?? 0);
        $chart_impressions_data[] = ['x' => $date_str, 'y' => $impressions];
        $chart_earnings_data[] = ['x' => $date_str, 'y' => round($earnings, 6)];
    }
}
$chart_data_json = json_encode(['impressions' => $chart_impressions_data, 'earnings' => $chart_earnings_data]);

$totals['ctr'] = ($totals['impressions'] > 0) ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
$totals['ecpm'] = ($totals['impressions'] > 0) ? ($totals['earnings'] / $totals['impressions']) * 1000 : 0;

require_once __DIR__ . '/templates/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="fw-bold mb-1">Detailed Statistics</h4>
        <p class="text-muted mb-0">Analyze your performance metrics in depth</p>
    </div>
    
    <div class="d-flex align-items-center">
        <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-download me-1"></i> Export
        </button>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="quickDateRange" data-bs-toggle="dropdown">
                <i class="bi bi-calendar3 me-1"></i> Quick Select
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?date_from=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&date_to=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&group_by=<?php echo $group_by; ?>&site_id=<?php echo $filter_site_id ?: ''; ?>&zone_id=<?php echo $filter_zone_id ?: ''; ?>">Last 7 Days</a></li>
                <li><a class="dropdown-item" href="?date_from=<?php echo date('Y-m-d', strtotime('-14 days')); ?>&date_to=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&group_by=<?php echo $group_by; ?>&site_id=<?php echo $filter_site_id ?: ''; ?>&zone_id=<?php echo $filter_zone_id ?: ''; ?>">Last 14 Days</a></li>
                <li><a class="dropdown-item" href="?date_from=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&date_to=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&group_by=<?php echo $group_by; ?>&site_id=<?php echo $filter_site_id ?: ''; ?>&zone_id=<?php echo $filter_zone_id ?: ''; ?>">Last 30 Days</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?date_from=<?php echo date('Y-m-d', strtotime('first day of this month')); ?>&date_to=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&group_by=<?php echo $group_by; ?>&site_id=<?php echo $filter_site_id ?: ''; ?>&zone_id=<?php echo $filter_zone_id ?: ''; ?>">This Month</a></li>
                <li><a class="dropdown-item" href="?date_from=<?php echo date('Y-m-d', strtotime('first day of last month')); ?>&date_to=<?php echo date('Y-m-d', strtotime('last day of last month')); ?>&group_by=<?php echo $group_by; ?>&site_id=<?php echo $filter_site_id ?: ''; ?>&zone_id=<?php echo $filter_zone_id ?: ''; ?>">Last Month</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 d-flex align-items-center mb-4 shadow-sm" style="background-color: rgba(56, 189, 248, 0.1);">
    <i class="bi bi-info-circle-fill text-info me-3 fs-4"></i>
    <div>
        <span class="fw-medium">Data information:</span> Reports are based on summarized data up to yesterday for maximum performance.
        <span class="d-block text-muted small mt-1">Current date range: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?></span>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Impressions</div>
                    <div class="stat-value"><?php echo number_format($totals['impressions']); ?></div>
                </div>
                <i class="bi bi-eye stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Clicks</div>
                    <div class="stat-value"><?php echo number_format($totals['clicks']); ?></div>
                </div>
                <i class="bi bi-cursor stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">CTR</div>
                    <div class="stat-value"><?php echo number_format($totals['ctr'], 2); ?>%</div>
                </div>
                <i class="bi bi-pie-chart stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Earnings</div>
                    <div class="stat-value text-success">$<?php echo number_format($totals['earnings'], 4); ?></div>
                </div>
                <i class="bi bi-cash-coin stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0">
            <i class="bi bi-sliders2 me-2 text-primary"></i> Filter & Group Settings
        </h5>
        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <form id="filterForm" method="GET" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-medium">Date Range</label>
                    <div class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-medium">Filter by Site</label>
                    <select class="form-select" name="site_id" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All My Sites</option>
                        <?php foreach($sites_list as $site): ?>
                            <option value="<?php echo $site['id']; ?>" <?php if($filter_site_id == $site['id']) echo 'selected'; ?>><?php echo htmlspecialchars($site['url']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-medium">Filter by Zone</label>
                    <select class="form-select" name="zone_id">
                        <option value="">All Zones</option>
                        <?php foreach($zones_list as $zone): ?>
                            <option value="<?php echo $zone['id']; ?>" <?php if($filter_zone_id == $zone['id']) echo 'selected'; ?>><?php echo htmlspecialchars($zone['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-medium">Group By</label>
                    <select class="form-select" name="group_by">
                        <option value="date" <?php if($group_by=='date') echo 'selected';?>>Date</option>
                        <option value="site" <?php if($group_by=='site') echo 'selected';?>>Site</option>
                        <option value="zone" <?php if($group_by=='zone') echo 'selected';?>>Zone</option>
                        <option value="country" <?php if($group_by=='country') echo 'selected';?>>Country</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Apply Filters
                    </button>
                    <a href="statistics.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-lg me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($group_by === 'date' && !empty($chart_data_json) && !empty($report_rows)): ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0">Performance Chart</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-light" id="chartTypeLineBtn">
                <i class="bi bi-graph-up"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" id="chartTypeBarBtn">
                <i class="bi bi-bar-chart"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <div style="height: 350px;">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0">
            <i class="bi bi-table me-2 text-primary"></i> <?php echo htmlspecialchars($main_column_header); ?> Report
        </h5>
        <div class="d-flex gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calendar3"></i> <?php echo date('M d', strtotime($date_from)); ?> - <?php echo date('M d', strtotime($date_to)); ?>
            </span>
            <?php if($filter_site_id): ?>
            <span class="badge bg-success bg-opacity-10 text-success">
                <i class="bi bi-globe"></i> Site Filtered
            </span>
            <?php endif; ?>
            <?php if($filter_zone_id): ?>
            <span class="badge bg-info bg-opacity-10 text-info">
                <i class="bi bi-grid"></i> Zone Filtered
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4"><?php echo $main_column_header; ?></th>
                        <th>Impressions</th>
                        <th>Clicks</th>
                        <th>CTR</th>
                        <th>eCPM ($)</th>
                        <th class="pe-4">Earnings ($)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($report_rows)): foreach($report_rows as $row): ?>
                    <?php
                        $ctr = ($row['total_impressions'] > 0) ? ($row['total_clicks'] / $row['total_impressions']) * 100 : 0;
                        $ecpm = ($row['total_impressions'] > 0) ? ($row['total_earnings'] / $row['total_impressions']) * 1000 : 0;
                    ?>
                    <tr>
                        <td class="ps-4 fw-medium"><?php echo htmlspecialchars($row['group_field'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($row['total_impressions']); ?></td>
                        <td><?php echo number_format($row['total_clicks']); ?></td>
                        <td><?php echo number_format($ctr, 2); ?>%</td>
                        <td><?php echo number_format($ecpm, 4); ?></td>
                        <td class="pe-4 fw-bold text-success">$<?php echo number_format($row['total_earnings'], 6); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="py-3">
                                <i class="bi bi-clipboard-x fs-2 text-muted mb-2"></i>
                                <p class="text-muted mb-0">No data found for the selected filters.</p>
                                <a href="statistics.php" class="btn btn-sm btn-outline-primary mt-3">Reset Filters</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-4">Total</td>
                        <td><?php echo number_format($totals['impressions']); ?></td>
                        <td><?php echo number_format($totals['clicks']); ?></td>
                        <td><?php echo number_format($totals['ctr'], 2); ?>%</td>
                        <td><?php echo number_format($totals['ecpm'], 4); ?></td>
                        <td class="pe-4 text-success">$<?php echo number_format($totals['earnings'], 6); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Report Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Export Format</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatCSV" checked>
                            <label class="form-check-label" for="formatCSV">
                                <i class="bi bi-filetype-csv me-1"></i> CSV
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatXLS">
                            <label class="form-check-label" for="formatXLS">
                                <i class="bi bi-filetype-xlsx me-1"></i> Excel
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatPDF">
                            <label class="form-check-label" for="formatPDF">
                                <i class="bi bi-filetype-pdf me-1"></i> PDF
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select">
                        <option value="current">Current View (<?php echo ucfirst($group_by); ?> Report)</option>
                        <option value="summary">Summary Report</option>
                        <option value="detailed">Detailed Report</option>
                    </select>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="includeChart">
                    <label class="form-check-label" for="includeChart">
                        Include visualization charts
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">
                    <i class="bi bi-download me-1"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($group_by === 'date' && !empty($chart_data_json)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart');
    if (ctx) {
        const chartData = <?php echo $chart_data_json; ?>;
        
        // Create gradient fill for impressions line
        const impressionsGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
        impressionsGradient.addColorStop(0, 'rgba(67, 97, 238, 0.3)');
        impressionsGradient.addColorStop(1, 'rgba(67, 97, 238, 0.02)');
        
        // Create gradient fill for earnings line
        const earningsGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
        earningsGradient.addColorStop(0, 'rgba(74, 222, 128, 0.3)');
        earningsGradient.addColorStop(1, 'rgba(74, 222, 128, 0.02)');
        
        let chartType = 'line'; // Default chart type
        
        const chart = new Chart(ctx, {
            type: chartType,
            data: {
                datasets: [{
                    label: 'Impressions',
                    data: chartData.impressions,
                    yAxisID: 'yImpressions',
                    borderColor: 'rgb(67, 97, 238)',
                    backgroundColor: impressionsGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgb(67, 97, 238)',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                    order: 2
                }, {
                    label: 'Earnings ($)',
                    data: chartData.earnings,
                    yAxisID: 'yEarnings',
                    borderColor: '#4ade80',
                    backgroundColor: earningsGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4ade80',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                    order: 1
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
                    yImpressions: {
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
        
        // Chart type toggle buttons
        document.getElementById('chartTypeLineBtn').addEventListener('click', function() {
            changeChartType('line');
            this.classList.add('active');
            document.getElementById('chartTypeBarBtn').classList.remove('active');
        });
        
        document.getElementById('chartTypeBarBtn').addEventListener('click', function() {
            changeChartType('bar');
            this.classList.add('active');
            document.getElementById('chartTypeLineBtn').classList.remove('active');
        });
        
        function changeChartType(newType) {
            chart.config.type = newType;
            chart.data.datasets[0].fill = newType === 'line';
            chart.data.datasets[1].fill = newType === 'line';
            chart.update();
        }
        
        // Initialize with line chart active
        document.getElementById('chartTypeLineBtn').classList.add('active');
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>