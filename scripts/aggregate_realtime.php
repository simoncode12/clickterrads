<?php
// File: /scripts/aggregate_realtime.php (FINAL & COMPLETE)
// This script should be run every 5 minutes via a cron job.
// It only aggregates data for THE CURRENT DAY.

// Pastikan skrip ini hanya bisa dijalankan dari command-line (CLI), bukan dari browser.
if (php_sapi_name() !== 'cli') {
    die("This script is only accessible from the command line (CLI).");
}

require_once __DIR__ . '/../config/database.php';

$today = date('Y-m-d');
echo "Starting real-time aggregation for today ({$today}) at " . date('H:i:s') . "\n";

// Query ini mengambil semua data mentah dari HARI INI,
// meringkasnya, dan memasukkannya ke tabel summary.
// ON DUPLICATE KEY UPDATE akan mengganti data ringkasan yang ada dengan data baru yang lebih lengkap.
$aggregation_sql = "
    INSERT INTO stats_daily_summary 
        (stat_date, campaign_id, creative_id, zone_id, ssp_partner_id, country, os, browser, device, impressions, clicks, cost, publisher_payout)
    SELECT
        s.stat_date, 
        s.campaign_id, 
        s.creative_id, 
        s.zone_id, 
        s.ssp_partner_id, 
        s.country, 
        s.os, 
        s.browser, 
        s.device,
        SUM(s.impressions) as total_impressions,
        SUM(s.clicks) as total_clicks,
        SUM(s.cost) as total_cost,
        SUM(s.cost * COALESCE(u.revenue_share, 0) / 100) as total_payout
    FROM campaign_stats s
    LEFT JOIN zones z ON s.zone_id = z.id
    LEFT JOIN sites si ON z.site_id = si.id
    LEFT JOIN users u ON si.user_id = u.id
    WHERE s.stat_date = ?
    GROUP BY 
        s.stat_date, s.campaign_id, s.creative_id, s.zone_id, s.ssp_partner_id, s.country, s.os, s.browser, s.device
    ON DUPLICATE KEY UPDATE
        impressions = VALUES(impressions),
        clicks = VALUES(clicks),
        cost = VALUES(cost),
        publisher_payout = VALUES(publisher_payout);
";

$stmt_agg = $conn->prepare($aggregation_sql);
if ($stmt_agg === false) {
    die("Error preparing real-time aggregation statement: " . $conn->error . "\n");
}

$stmt_agg->bind_param("s", $today);

if ($stmt_agg->execute()) {
    echo "Successfully aggregated/updated " . $stmt_agg->affected_rows . " real-time rows.\n";
} else {
    echo "Error during real-time aggregation: " . $stmt_agg->error . "\n";
}

$stmt_agg->close();
$conn->close();

echo "Real-time aggregation script finished successfully at: " . date('H:i:s') . "\n";
?>