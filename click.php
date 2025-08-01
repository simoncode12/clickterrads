<?php
// File: /click.php (UNIVERSAL VAST & BANNER CLICK TRACKER - AMAN, TIDAK NGARUH KE KAMPANYE LAIN)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/visitor_detector.php';

$creative_id = filter_input(INPUT_GET, 'cid', FILTER_VALIDATE_INT);
$zone_id = filter_input(INPUT_GET, 'zone_id', FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? ''; // type=pixel untuk VAST ClickTracking

if (!$creative_id || !$zone_id) { exit("Invalid tracking parameters."); }

// Ambil info creative
$stmt_creative = $conn->prepare("SELECT landing_url, campaign_id, bid_model, bid_amount FROM creatives WHERE id = ?");
$stmt_creative->bind_param("i", $creative_id);
$stmt_creative->execute();
$creative = $stmt_creative->get_result()->fetch_assoc();
$stmt_creative->close();

if (!$creative || empty($creative['landing_url'])) { exit("Redirect URL not found."); }

$visitor = get_visitor_details();
$cost_of_click = ($creative['bid_model'] === 'cpc') ? (float)$creative['bid_amount'] : 0.0;
$today = date('Y-m-d');
$campaign_id = $creative['campaign_id'];

// --- TRACK CLICK STAT (HANYA untuk creative/campaign/zone yang diminta) ---
$stmt_stats = $conn->prepare("INSERT INTO campaign_stats (campaign_id, creative_id, zone_id, country, os, browser, device, stat_date, clicks, cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?) ON DUPLICATE KEY UPDATE clicks = clicks + 1, cost = cost + VALUES(cost)");
$stmt_stats->bind_param("iiisssssd", $campaign_id, $creative_id, $zone_id, $visitor['country'], $visitor['os'], $visitor['browser'], $visitor['device'], $today, $cost_of_click);
$stmt_stats->execute();
$stmt_stats->close();
$conn->close();

// --- IF VAST/PLAYER CLICK: RETURN 1x1 GIF (pixel tracking) ---
if ($type === 'pixel') {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
    exit();
}

// --- IF BANNER/HTML CLICK: REDIRECT USER TO LANDING PAGE ---
header("Location: " . $creative['landing_url'], true, 302);
exit();
?>

