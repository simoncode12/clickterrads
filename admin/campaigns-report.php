<?php
// File: /admin/campaigns-report.php (FINAL & FIXED - Uses LEFT JOIN for all traffic)

require_once __DIR__ . '/init.php';

// --- Filter Logic ---
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-6 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$group_by = $_GET['group_by'] ?? 'summary';
$selected_campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);
$selected_format_id = filter_input(INPUT_GET, 'ad_format_id', FILTER_VALIDATE_INT);

// Data untuk dropdown filter
$campaigns_list = $conn->query("SELECT id, name FROM campaigns WHERE id > 0 ORDER BY name ASC");
$ad_formats_list = $conn->query("SELECT id, name FROM ad_formats WHERE status = 1 ORDER BY name ASC");

// --- Main Hybrid Query Construction ---
$allowed_group_by = ['daily'=>'stat_date', 'country'=>'country', 'browser'=>'browser', 'os'=>'os', 'device'=>'device'];
$group_by_field_db = $allowed_group_by[$group_by] ?? null;

// Tentukan alias dan header kolom utama
$main_column_header = "Campaign";
$show_campaign_col = false;
$select_clause = "COALESCE(c.name, 'External RTB') as group_field, T.campaign_id";
$group_by_clause_outer = "GROUP BY T.campaign_id, group_field";

if ($group_by_field_db) {
    $select_clause = "T.{$group_by_field_db} as group_field, COALESCE(c.name, 'External RTB') as campaign_name, T.campaign_id";
    $group_by_clause_outer = "GROUP BY group_field, T.campaign_id, campaign_name";
    $main_column_header = ucfirst(str_replace('_', ' ', $group_by));
    $show_campaign_col = true;
    if ($group_by === 'daily') {
        $group_by_clause_outer = "GROUP BY group_field";
        $show_campaign_col = false;
    }
}

// Tentukan rentang tanggal untuk query historis
$date_hist_from = ($date_from < $today) ? $date_from : '1970-01-01';
$date_hist_to = ($date_to < $today) ? $date_to : $yesterday;

// Bangun subquery UNION ALL
$subquery_sql = "
    (
        SELECT campaign_id, impressions, clicks, cost, stat_date, country, os, browser, device
        FROM stats_daily_summary
        WHERE stat_date BETWEEN ? AND ?
    )
    UNION ALL
    (
        SELECT campaign_id, impressions, clicks, cost, stat_date, country, os, browser, device
        FROM campaign_stats
        WHERE stat_date = ? AND stat_date >= ?
    )
";

$sql = "
    SELECT 
        {$select_clause},
        SUM(T.impressions) AS total_impressions,
        SUM(T.clicks) AS total_clicks,
        SUM(T.cost) AS total_cost
    FROM ({$subquery_sql}) AS T
    LEFT JOIN campaigns c ON T.campaign_id = c.id
";

$params = [$date_hist_from, $date_hist_to, $today, $date_from];
$types = "ssss";

$where_clauses = [];
if ($selected_campaign_id) {
    $where_clauses[] = "T.campaign_id = ?";
    $params[] = $selected_campaign_id;
    $types .= "i";
}
if ($selected_format_id) {
    $where_clauses[] = "(c.ad_format_id = ? OR T.campaign_id = -1)";
    $params[] = $selected_format_id;
    $types .= "i";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " {$group_by_clause_outer} ORDER BY total_cost DESC, total_impressions DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("SQL Prepare Error: " . $conn->error); }
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$report_rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Siapkan data chart jika dikelompokkan harian
$chart_data_json = null;
if ($group_by === 'daily' && !empty($report_rows)) {
    $daily_data_for_chart = array_column($report_rows, null, 'group_field');
    $chart_impressions_data = []; $chart_clicks_data = [];
    $period = new DatePeriod(new DateTime($date_from), new DateInterval('P1D'), (new DateTime($date_to))->modify('+1 day'));
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $chart_impressions_data[] = ['x' => $date_str, 'y' => (int)($daily_data_for_chart[$date_str]['total_impressions'] ?? 0)];
        $chart_clicks_data[] = ['x' => $date_str, 'y' => (int)($daily_data_for_chart[$date_str]['total_clicks'] ?? 0)];
    }
    $chart_data_json = json_encode(['impressions' => $chart_impressions_data, 'clicks' => $chart_clicks_data]);
}

require_once __DIR__ . '/templates/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="mt-4 mb-0">Campaign Analytics</h1></div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-filter me-2"></i>Filter Report</div>
    <div class="card-body">
        <div class="alert alert-info small"><i class="bi bi-info-circle-fill"></i> Note: Reports use summarized data for performance. Today's data is real-time.</div>
        <form method="GET" class="row g-3 align-items-end mt-2">
            <div class="col-lg-3 col-md-6"><label class="form-label">Campaign</label><select class="form-select" name="campaign_id"><option value="">All Campaigns</option><?php if($campaigns_list) { mysqli_data_seek($campaigns_list, 0); while($campaign = $campaigns_list->fetch_assoc()): ?><option value="<?php echo $campaign['id']; ?>" <?php if($selected_campaign_id == $campaign['id']) echo 'selected'; ?>><?php echo htmlspecialchars($campaign['name']); ?></option><?php endwhile; } ?></select></div>
            <div class="col-lg-2 col-md-6"><label class="form-label">Ad Format</label><select class="form-select" name="ad_format_id"><option value="">All Formats</option><?php if($ad_formats_list) { mysqli_data_seek($ad_formats_list, 0); while($format = $ad_formats_list->fetch_assoc()): ?><option value="<?php echo $format['id']; ?>" <?php if($selected_format_id == $format['id']) echo 'selected'; ?>><?php echo htmlspecialchars($format['name']); ?></option><?php endwhile; } ?></select></div>
            <div class="col-lg-2 col-md-6"><label class="form-label">Group By</label><select class="form-select" name="group_by"><option value="summary" <?php if($group_by == 'summary') echo 'selected'; ?>>Summary</option><option value="daily" <?php if($group_by == 'daily') echo 'selected'; ?>>Daily</option><option value="country" <?php if($group_by == 'country') echo 'selected'; ?>>Country</option><option value="browser" <?php if($group_by == 'browser') echo 'selected'; ?>>Browser</option><option value="os" <?php if($group_by == 'os') echo 'selected'; ?>>OS</option><option value="device" <?php if($group_by == 'device') echo 'selected'; ?>>Device</option></select></div>
            <div class="col-lg-3 col-md-6"><label class="form-label">Date Range</label><div class="input-group"><input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"><input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"></div></div>
            <div class="col-lg-2 col-md-12"><button type="submit" class="btn btn-primary w-100 mt-3 mt-lg-0">Apply Filter</button></div>
        </form>
    </div>
</div>

<?php if ($group_by === 'daily' && isset($chart_data_json)): ?>
<div class="card mb-4"><div class="card-header"><i class="bi bi-graph-up me-2"></i>Daily Performance Chart</div><div class="card-body"><canvas id="performanceChart" style="height: 300px; width: 100%;"></canvas></div></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Performance Data</div>
    <div class="card-body">
        <div class="table-responsive"><table class="table table-striped table-bordered table-hover">
            <thead class="table-dark"><tr><th><?php echo $main_column_header; ?></th><?php if($show_campaign_col): ?><th>Campaign</th><?php endif; ?><th>Impr.</th><th>Clicks</th><th>CTR</th><th>Cost ($)</th><th>eCPM ($)</th><th>eCPC ($)</th></tr></thead>
            <tbody>
                <?php if (!empty($report_rows)): $grand_total = ['impressions' => 0, 'clicks' => 0, 'cost' => 0]; foreach($report_rows as $row): ?>
                    <?php
                        $ctr = ($row['total_impressions'] > 0) ? ($row['total_clicks'] / $row['total_impressions']) * 100 : 0;
                        $ecpm = ($row['total_impressions'] > 0) ? ($row['total_cost'] / $row['total_impressions']) * 1000 : 0;
                        $ecpc = ($row['total_clicks'] > 0) ? ($row['total_cost'] / $row['total_clicks']) : 0;
                        $grand_total['impressions'] += $row['total_impressions']; $grand_total['clicks'] += $row['total_clicks']; $grand_total['cost'] += $row['total_cost'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['group_field'] ?? ($row['campaign_name'] ?? 'External RTB')); ?></td>
                        <?php if($show_campaign_col): ?><td><?php echo htmlspecialchars($row['campaign_name']); ?></td><?php endif; ?>
                        <td><?php echo number_format($row['total_impressions']); ?></td>
                        <td><?php echo number_format($row['total_clicks']); ?></td>
                        <td><?php echo number_format($ctr, 2); ?>%</td>
                        <td><?php echo number_format($row['total_cost'], 6); ?></td>
                        <td><?php echo number_format($ecpm, 4); ?></td>
                        <td><?php echo number_format($ecpc, 4); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="<?php echo $show_campaign_col ? '8' : '7'; ?>" class="text-center">No performance data found for the selected filter.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($report_rows)): ?>
            <tfoot class="table-group-divider fw-bold">
                <?php 
                    $total_ctr = ($grand_total['impressions'] > 0) ? ($grand_total['clicks'] / $grand_total['impressions']) * 100 : 0;
                    $total_ecpm = ($grand_total['impressions'] > 0) ? ($grand_total['cost'] * 1000 / $grand_total['impressions']) : 0;
                    $total_ecpc = ($grand_total['clicks'] > 0) ? ($grand_total['cost'] / $grand_total['clicks']) : 0;
                ?>
                <tr><td colspan="<?php echo $show_campaign_col ? 2 : 1; ?>">Total</td><td><?php echo number_format($grand_total['impressions']); ?></td><td><?php echo number_format($grand_total['clicks']); ?></td><td><?php echo number_format($total_ctr, 2); ?>%</td><td><?php echo number_format($grand_total['cost'], 6); ?></td><td><?php echo number_format($total_ecpm, 4); ?></td><td><?php echo number_format($total_ecpc, 4); ?></td></tr>
            </tfoot>
            <?php endif; ?>
        </table></div>
    </div>
</div>

<script>
<?php if ($group_by === 'daily' && isset($chart_data_json)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart');
    if(ctx) {
        const chartData = <?php echo $chart_data_json; ?>;
        if (chartData.impressions && chartData.impressions.length > 0) {
            new Chart(ctx, { 
                type: 'line', 
                data: { datasets: [{ label: 'Impressions', data: chartData.impressions, borderColor: 'rgba(54, 162, 235, 1)', tension: 0.1, yAxisID: 'y' }, { label: 'Clicks', data: chartData.clicks, borderColor: 'rgba(255, 99, 132, 1)', tension: 0.1, yAxisID: 'y1' }] }, 
                options: { 
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, 
                    scales: { 
                        x: { type: 'time', time: { unit: 'day', tooltipFormat: 'd MMM' } }, 
                        y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Impressions' } }, 
                        y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Clicks' }, grid: { drawOnChartArea: false } } 
                    } 
                } 
            });
        }
    }
});
<?php endif; ?>
</script>

<?php 
if(isset($campaigns_list)) { $campaigns_list->close(); }
if(isset($ad_formats_list)) { $ad_formats_list->close(); }
require_once __DIR__ . '/templates/footer.php'; 
?>