<?php
// File: /admin/debug-publisher-stats.php (Diagnostic Tool)

require_once __DIR__ . '/init.php';

$selected_publisher_id = filter_input(INPUT_GET, 'publisher_id', FILTER_VALIDATE_INT);
$publishers = $conn->query("SELECT id, username FROM users WHERE role = 'publisher' ORDER BY username ASC");

function run_query($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params)); // Treat all as strings for simplicity
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

require_once __DIR__ . '/templates/header.php';
?>

<h1 class="mt-4 mb-4">Publisher Statistics Diagnostic Tool</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4">
                <label for="publisher_id" class="form-label">Select Publisher to Debug</label>
                <select name="publisher_id" id="publisher_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Choose a Publisher --</option>
                    <?php while ($pub = $publishers->fetch_assoc()): ?>
                        <option value="<?php echo $pub['id']; ?>" <?php if ($pub['id'] == $selected_publisher_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($pub['username']); ?> (ID: <?php echo $pub['id']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_publisher_id): ?>
    <div class="p-3 mb-2 bg-light rounded-3">
    <?php
        echo "<h3>Debugging for Publisher ID: {$selected_publisher_id}</h3><hr>";

        // 1. Check Sites
        echo "<h4>Step 1: Finding Sites owned by this Publisher</h4>";
        $sites_result = run_query($conn, "SELECT id, url FROM sites WHERE user_id = ?", [$selected_publisher_id]);
        if ($sites_result->num_rows > 0) {
            echo "<p class='text-success'>SUCCESS: Found " . $sites_result->num_rows . " site(s).</p><ul>";
            $site_ids = [];
            while ($site = $sites_result->fetch_assoc()) {
                $site_ids[] = $site['id'];
                echo "<li>Site ID: <strong>" . $site['id'] . "</strong>, URL: " . htmlspecialchars($site['url']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='text-danger'><strong>ERROR: No sites found for this publisher. This is the main problem. Please create a site for this publisher in 'Site Management'.</strong></p>";
        }
        
        // 2. Check Zones
        if (!empty($site_ids)) {
            echo "<hr><h4>Step 2: Finding Zones within those Sites</h4>";
            $zone_ids_sql = "SELECT id, name, size FROM zones WHERE site_id IN (" . implode(',', $site_ids) . ")";
            $zones_result = run_query($conn, $zone_ids_sql);
            if ($zones_result->num_rows > 0) {
                echo "<p class='text-success'>SUCCESS: Found " . $zones_result->num_rows . " zone(s).</p><ul>";
                $zone_ids = [];
                while ($zone = $zones_result->fetch_assoc()) {
                    $zone_ids[] = $zone['id'];
                    echo "<li>Zone ID: <strong>" . $zone['id'] . "</strong>, Name: " . htmlspecialchars($zone['name']) . ", Size: " . $zone['size'] . "</li>";
                }
                echo "</ul>";
            } else {
                 echo "<p class='text-danger'><strong>ERROR: This publisher has site(s), but no zones were found. Please create a zone for their site in 'Zone Management'.</strong></p>";
            }
        }

        // 3. Check Stats
        if (!empty($zone_ids)) {
             echo "<hr><h4>Step 3: Finding Stats linked to these Zones</h4>";
             $stats_sql = "SELECT zone_id, SUM(impressions) as total_imp, SUM(cost) as total_cst FROM campaign_stats WHERE zone_id IN (" . implode(',', $zone_ids) . ") GROUP BY zone_id";
             $stats_result = run_query($conn, $stats_sql);
             if($stats_result->num_rows > 0) {
                echo "<p class='text-success'>SUCCESS: Found stats records linked to this publisher's zones.</p>";
                echo "<table class='table table-bordered'><thead><tr><th>Zone ID</th><th>Total Impressions</th><th>Total Cost</th></tr></thead><tbody>";
                while($stat = $stats_result->fetch_assoc()){
                    echo "<tr><td>".$stat['zone_id']."</td><td>".$stat['total_imp']."</td><td>".number_format($stat['total_cst'], 8)."</td></tr>";
                }
                echo "</tbody></table><p>If you see data here, the publisher dashboard should also show data. If not, check the date filter on the dashboard.</p>";
             } else {
                echo "<p class='text-warning'><strong>INFO: No stats found for this publisher's zones. This could be because no traffic has been sent using their specific Zone IDs yet. Make sure your RTB partner is using one of these Zone IDs: " . implode(', ', $zone_ids) . "</strong></p>";
             }
        }

        echo "<hr><h4>Step 4: Checking for 'Orphan' Stats</h4>";
        echo "<p>These are stats in your database that are NOT linked to this publisher. This helps identify if traffic is coming with the wrong Zone ID.</p>";
        $orphan_sql = "SELECT zone_id, SUM(impressions) as total_imp, SUM(cost) as total_cst FROM campaign_stats ";
        if(!empty($zone_ids)) {
            $orphan_sql .= "WHERE zone_id NOT IN (" . implode(',', $zone_ids) . ")";
        }
        $orphan_sql .= " GROUP BY zone_id ORDER BY total_imp DESC LIMIT 10";
        $orphan_result = run_query($conn, $orphan_sql);
        if($orphan_result->num_rows > 0){
             echo "<table class='table table-bordered'><thead><tr><th>Zone ID</th><th>Total Impressions</th><th>Total Cost</th></tr></thead><tbody>";
             while($stat = $orphan_result->fetch_assoc()){
                 echo "<tr><td>".$stat['zone_id']."</td><td>".$stat['total_imp']."</td><td>".number_format($stat['total_cst'], 8)."</td></tr>";
             }
             echo "</tbody></table><p class='text-info'>If you see impressions here for a Zone ID that *should* belong to this publisher (e.g., Zone ID 5), it means that zone is not correctly assigned to a site owned by this publisher in your database.</p>";
        } else {
             echo "<p class='text-success'>No orphan stats found.</p>";
        }

    ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>