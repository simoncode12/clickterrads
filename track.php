<?php
// File: /track.php (SAFE PATCH - Anti-Duplikat Impression)

// --- DB Connection ---
require_once __DIR__ . '/config/database.php';

// --- Security: Allow CORS for pixel
header('Access-Control-Allow-Origin: *');
header('Content-Type: image/gif');

// --- Disable caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// --- GET Params
$event = $_GET['event'] ?? 'impression';
$cid   = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
$creative_id = isset($_GET['creative_id']) ? intval($_GET['creative_id']) : 0;
$zone_id = isset($_GET['zone_id']) ? intval($_GET['zone_id']) : 0;
$ssp_partner_id = isset($_GET['ssp_partner_id']) ? intval($_GET['ssp_partner_id']) : null;

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$date = date('Y-m-d');

// --- Client/Geo Info (opsional) ---
$country = 'XX'; $os = 'Unknown'; $browser = 'Unknown'; $device = 'Unknown';
if (file_exists(__DIR__ . '/includes/visitor_detector.php')) {
    require_once __DIR__ . '/includes/visitor_detector.php';
    $vd = get_visitor_details();
    $country = $vd['country'] ?? 'XX';
    $os      = $vd['os'] ?? 'Unknown';
    $browser = $vd['browser'] ?? 'Unknown';
    $device  = $vd['device'] ?? 'Unknown';
}

// --- PATCH ANTI-DUPLIKAT IMPRESSION ---
if ($event === 'impression') {
    if ($cid > 0) {
        // Cek duplikat: ip+ua+zone+creative+date
        $check = $conn->prepare("SELECT 1 FROM campaign_stats WHERE campaign_id=? AND creative_id=? AND zone_id=? AND stat_date=? AND ip=? AND user_agent=? LIMIT 1");
        $check->bind_param("iiisss", $cid, $creative_id, $zone_id, $date, $ip, $ua);
        $check->execute();
        $already = $check->get_result()->fetch_row();
        $check->close();
        if (!$already) {
            // Insert only if not duplicate
            $sql = "INSERT INTO campaign_stats (campaign_id, creative_id, zone_id, country, os, browser, device, stat_date, impressions, ip, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                    ON DUPLICATE KEY UPDATE impressions = impressions + 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissssss", $cid, $creative_id, $zone_id, $country, $os, $browser, $device, $date, $ip, $ua);
            $stmt->execute();
            $stmt->close();
        }
    }
    if ($ssp_partner_id) {
        $sql = "INSERT INTO ssp_partner_stats (ssp_partner_id, stat_date, impressions) VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE impressions = impressions + 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $ssp_partner_id, $date);
        $stmt->execute();
        $stmt->close();
    }
}

// --- Output 1x1 GIF (universal pixel) ---
echo base64_decode(
    'R0lGODlhAQABAPAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='
);

$conn->close();
exit();
?>

