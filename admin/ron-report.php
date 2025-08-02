<?php
// File: /admin/ron-report.php (FINAL & OPTIMIZED - With Hybrid Query)

require_once __DIR__ . '/init.php';

// --- Filter Logic ---
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-6 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$group_by = $_GET['group_by'] ?? 'campaign';
$selected_campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);

// Ambil daftar kampanye RON untuk filter dropdown
$ron_campaigns_list = $conn->query("SELECT id, name FROM campaigns WHERE serve_on_internal = 1 ORDER BY name ASC");

// --- Main Hybrid Query Construction ---
$group_by_field_db = ""; $main_column_header = "Campaign"; $group_by_select_inner = ""; $group_by_clause_outer = "GROUP BY T.campaign_id, c.name";
$join_clause = "";

switch ($group_by) {
    case 'date': 
        $group_by_field_db = "stat_date";
        $main_column_header = "Date";
        $group_by_select_inner = ", stat_date";
        $group_by_clause_outer = "GROUP BY T.stat_date";
        break;
    case 'country': 
        $group_by_field_db = "country";
        $main_column_header = "Country";
        $group_by_select_inner = ", country";
        $group_by_clause_outer = "GROUP BY T.country";
        break;
    case 'site': 
        $group_by_field_db = "zone_id";
        $main_column_header = "Site";
        $join_clause = "LEFT JOIN zones z ON T.zone_id = z.id LEFT JOIN sites si ON z.site_id = si.id";
        $group_by_clause_outer = "GROUP BY si.id, si.url";
        $group_by_select_inner = ", zone_id";
        break;
}

// Tentukan rentang tanggal untuk query historis
$date_hist_from = ($date_from < $today) ? $date_from : $today;
$date_hist_to = ($date_to < $today) ? $date_to : $yesterday;

$sql = "
    SELECT
        " . ($group_by_field_db ? "T.{$group_by_field_db} as group_field," : "") . "
        c.name as campaign_name,
        SUM(T.total_impressions) AS total_impressions,
        SUM(T.total_clicks) AS total_clicks,
        SUM(T.total_cost) AS total_cost
    FROM (
        SELECT campaign_id, impressions AS total_impressions, clicks AS total_clicks, cost AS total_cost {$group_by_select_inner}
        FROM stats_daily_summary
        WHERE stat_date BETWEEN ? AND ?
        UNION ALL
        SELECT campaign_id, impressions AS total_impressions, clicks AS total_clicks, cost AS total_cost {$group_by_select_inner}
        FROM campaign_stats
        WHERE stat_date = ? AND stat_date >= ?
    ) AS T
    JOIN campaigns c ON T.campaign_id = c.id
    {$join_clause}
";

$params = [$date_hist_from, $date_hist_to, $today, $date_from];
$types = "ssss";

$where_clauses = ["c.serve_on_internal = 1"];
if ($selected_campaign_id) {
    $where_clauses[] = "T.campaign_id = ?";
    $params[] = $selected_campaign_id;
    $types .= "i";
}

$sql .= " WHERE " . implode(' AND ', $where_clauses);
$sql .= " {$group_by_clause_outer} ORDER BY total_cost DESC, total_impressions DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$report_rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

require_once __DIR__ . '/templates/header.php';
?>

<h1 class="mt-4 mb-4">RON Campaign Analytics</h1>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-filter me-2"></i>Filter Report</div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">RON Campaign</label><select class="form-select" name="campaign_id"><option value="">All RON Campaigns</option><?php if ($ron_campaigns_list->num_rows > 0): mysqli_data_seek($ron_campaigns_list, 0); while($c = $ron_campaigns_list->fetch_assoc()): ?><option value="<?php echo $c['id']; ?>" <?php if($selected_campaign_id == $c['id']) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endwhile; endif; ?></select></div>
            <div class="col-md-2"><label class="form-label">Group By</label><select class="form-select" name="group_by"><option value="campaign" <?php if($group_by == 'campaign') echo 'selected'; ?>>Campaign</option><option value="date" <?php if($group_by == 'date') echo 'selected'; ?>>Date</option><option value="country" <?php if($group_by == 'country') echo 'selected'; ?>>Country</option><option value="site" <?php if($group_by == 'site') echo 'selected'; ?>>Site</option></select></div>
            <div class="col-md-3"><label class="form-label">Date Range</label><div class="input-group"><input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"><input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"></div></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Apply Filter</button></div>
             <div class="col-md-2"><a href="ron-report.php" class="btn btn-secondary w-100">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Performance Data</div>
    <div class="card-body">
        <div class="table-responsive"><table class="table table-striped table-bordered table-hover">
            <thead class="table-dark"><tr><th><?php echo $main_column_header; ?></th><?php if($group_by !== 'campaign'): ?><th>Campaign</th><?php endif; ?><th>Impressions</th><th>Clicks</th><th>CTR</th><th>Cost ($)</th><th>eCPM ($)</th><th>eCPC ($)</th></tr></thead>
            <tbody>
            <?php if (!empty($report_rows)): $totals = ['impressions' => 0, 'clicks' => 0, 'cost' => 0]; foreach($report_rows as $row): 
                $totals['impressions'] += $row['total_impressions']; $totals['clicks'] += $row['total_clicks']; $totals['cost'] += $row['total_cost'];
                $ctr = ($row['total_impressions'] > 0) ? ($row['total_clicks'] / $row['total_impressions']) * 100 : 0;
                $ecpm = ($row['total_impressions'] > 0) ? ($row['total_cost'] / $row['total_impressions']) * 1000 : 0;
                $ecpc = ($row['total_clicks'] > 0) ? ($row['total_cost'] / $row['total_clicks']) : 0;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['group_field'] ?? $row['campaign_name']); ?></td>
                     <?php if($group_by !== 'campaign'): ?><td><?php echo htmlspecialchars($row['campaign_name']); ?></td><?php endif; ?>
                    <td><?php echo number_format($row['total_impressions']); ?></td>
                    <td><?php echo number_format($row['total_clicks']); ?></td>
                    <td><?php echo number_format($ctr, 2); ?>%</td>
                    <td><?php echo number_format($row['total_cost'], 6); ?></td>
                    <td><?php echo number_format($ecpm, 4); ?></td>
                    <td><?php echo number_format($ecpc, 4); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center">No data found for the selected filters.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($report_rows)): 
                $total_ctr = ($totals['impressions'] > 0) ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
                $total_ecpm = ($totals['impressions'] > 0) ? ($totals['cost'] * 1000 / $totals['impressions']) : 0;
                $total_ecpc = ($totals['clicks'] > 0) ? ($totals['cost'] / $totals['clicks']) : 0;
            ?>
            <tfoot class="table-group-divider fw-bold">
                <tr><td colspan="<?php echo ($group_by !== 'campaign') ? 2 : 1; ?>">Total</td><td><?php echo number_format($totals['impressions']); ?></td><td><?php echo number_format($totals['clicks']); ?></td><td><?php echo number_format($total_ctr, 2); ?>%</td><td><?php echo number_format($totals['cost'], 6); ?></td><td><?php echo number_format($total_ecpm, 4); ?></td><td><?php echo number_format($total_ecpc, 4); ?></td></tr>
            </tfoot>
            <?php endif; ?>
        </table></div>
    </div>
</div>

<?php 
if (isset($ron_campaigns_list)) { $ron_campaigns_list->close(); }
require_once __DIR__ . '/templates/footer.php'; 
?>

