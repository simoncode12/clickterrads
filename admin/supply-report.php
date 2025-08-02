<?php
// File: /admin/supply-report.php (FINAL & FIXED - Corrected Hybrid Query)

require_once __DIR__ . '/init.php';

// --- Filter Logic ---
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-6 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$group_by = $_GET['group_by'] ?? 'partner';
$selected_partner_id = filter_input(INPUT_GET, 'partner_id', FILTER_VALIDATE_INT);

$partners_list = $conn->query("SELECT id, name FROM rtb_supply_sources ORDER BY name ASC");

// --- Main Hybrid Query Construction (REBUILT FOR STABILITY) ---
$group_by_field = "";
$main_column_header = "Supply Partner";
$group_by_clause = "GROUP BY T.partner_id, T.partner_name";

switch ($group_by) {
    case 'date':
        $group_by_field = "T.stat_date as group_field";
        $group_by_clause = "GROUP BY T.stat_date";
        $main_column_header = "Date";
        break;
    case 'country':
        $group_by_field = "T.country as group_field";
        $group_by_clause = "GROUP BY T.country";
        $main_column_header = "Country";
        break;
    default: // 'partner'
        $group_by_field = "T.partner_name as group_field";
        break;
}

// Tentukan rentang tanggal untuk query historis
$date_hist_from = ($date_from < $today) ? $date_from : '1970-01-01';
$date_hist_to = ($date_to < $today) ? $date_to : $yesterday;

$sql = "
    SELECT
        {$group_by_field},
        SUM(T.total_impressions) AS total_impressions,
        SUM(T.total_clicks) AS total_clicks,
        SUM(T.total_revenue) AS total_revenue
    FROM (
        -- Data historis dari tabel ringkasan
        SELECT 
            sds.stat_date, sds.country, rs.id as partner_id, rs.name as partner_name,
            sds.impressions AS total_impressions, 
            sds.clicks AS total_clicks, 
            sds.cost AS total_revenue
        FROM stats_daily_summary sds
        JOIN zones z ON sds.zone_id = z.id
        JOIN sites si ON z.site_id = si.id
        JOIN rtb_supply_sources rs ON si.user_id = rs.user_id
        WHERE sds.stat_date BETWEEN ? AND ?

        UNION ALL

        -- Data hari ini dari tabel mentah
        SELECT 
            cs.stat_date, cs.country, rs.id as partner_id, rs.name as partner_name,
            SUM(cs.impressions) AS total_impressions, 
            SUM(cs.clicks) AS total_clicks, 
            SUM(cs.cost) AS total_revenue
        FROM campaign_stats cs
        JOIN zones z ON cs.zone_id = z.id
        JOIN sites si ON z.site_id = si.id
        JOIN rtb_supply_sources rs ON si.user_id = rs.user_id
        WHERE cs.stat_date = ? AND cs.stat_date >= ?
        GROUP BY cs.stat_date, cs.country, rs.id, rs.name
    ) AS T
";

$params = [$date_hist_from, $date_hist_to, $today, $date_from];
$types = "ssss";

if ($selected_partner_id) {
    $sql .= " WHERE T.partner_id = ?";
    $params[] = $selected_partner_id;
    $types .= "i";
}

$sql .= " {$group_by_clause} ORDER BY total_revenue DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$report_rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

require_once __DIR__ . '/templates/header.php';
?>

<h1 class="mt-4 mb-4">RTB Supply Partner Analytics</h1>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-filter me-2"></i>Filter Report</div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Supply Partner</label><select class="form-select" name="partner_id"><option value="">All Partners</option><?php if ($partners_list && $partners_list->num_rows > 0): mysqli_data_seek($partners_list, 0); while($partner = $partners_list->fetch_assoc()): ?><option value="<?php echo $partner['id']; ?>" <?php if($selected_partner_id == $partner['id']) echo 'selected'; ?>><?php echo htmlspecialchars($partner['name']); ?></option><?php endwhile; endif; ?></select></div>
            <div class="col-md-2"><label class="form-label">Group By</label><select class="form-select" name="group_by"><option value="partner" <?php if($group_by == 'partner') echo 'selected'; ?>>Partner</option><option value="date" <?php if($group_by == 'date') echo 'selected'; ?>>Date</option><option value="country" <?php if($group_by == 'country') echo 'selected'; ?>>Country</option></select></div>
            <div class="col-md-3"><label class="form-label">Date Range</label><div class="input-group"><input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"><input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"></div></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Apply Filter</button></div>
            <div class="col-md-2"><a href="supply-report.php" class="btn btn-secondary w-100">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-bar-chart-line-fill me-2"></i>Performance Data</div>
    <div class="card-body">
        <div class="table-responsive"><table class="table table-striped table-bordered table-hover">
            <thead class="table-dark"><tr><th><?php echo $main_column_header; ?></th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>Revenue ($)</th><th>eCPM ($)</th></tr></thead>
            <tbody>
            <?php if (!empty($report_rows)): $totals = ['impressions' => 0, 'clicks' => 0, 'revenue' => 0]; foreach($report_rows as $row): 
                $totals['impressions'] += $row['total_impressions']; $totals['clicks'] += $row['total_clicks']; $totals['revenue'] += $row['total_revenue'];
                $ctr = ($row['total_impressions'] > 0) ? ($row['total_clicks'] / $row['total_impressions']) * 100 : 0;
                $ecpm = ($row['total_impressions'] > 0) ? ($row['total_revenue'] / $row['total_impressions']) * 1000 : 0;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['group_field'] ?: 'N/A'); ?></td>
                    <td><?php echo number_format($row['total_impressions']); ?></td>
                    <td><?php echo number_format($row['total_clicks']); ?></td>
                    <td><?php echo number_format($ctr, 2); ?>%</td>
                    <td><?php echo number_format($row['total_revenue'], 6); ?></td>
                    <td><?php echo number_format($ecpm, 4); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center">No data found for the selected filters.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($report_rows)): 
                $total_ctr = ($totals['impressions'] > 0) ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
                $total_ecpm = ($totals['impressions'] > 0) ? ($totals['revenue'] * 1000 / $totals['impressions']) : 0;
            ?>
            <tfoot class="table-group-divider fw-bold">
                <tr><td>Total</td><td><?php echo number_format($totals['impressions']); ?></td><td><?php echo number_format($totals['clicks']); ?></td><td><?php echo number_format($total_ctr, 2); ?>%</td><td><?php echo number_format($totals['revenue'], 6); ?></td><td><?php echo number_format($total_ecpm, 4); ?></td></tr>
            </tfoot>
            <?php endif; ?>
        </table></div>
    </div>
</div>

<?php 
if (isset($partners_list)) { $partners_list->close(); }
require_once __DIR__ . '/templates/footer.php'; 
?>