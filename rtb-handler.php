<?php
// rtb-handler.php FINAL PATCH 2025-07-27

require_once __DIR__ . '/config/database.php';

if (file_exists(__DIR__ . '/includes/settings.php')) require_once __DIR__ . '/includes/settings.php';
if (file_exists(__DIR__ . '/includes/visitor_detector.php')) require_once __DIR__ . '/includes/visitor_detector.php';

if (!isset($_GET['internal_call']) && file_exists(__DIR__ . '/includes/fraud_detector.php')) {
    require_once __DIR__ . '/includes/fraud_detector.php';
    if (is_fraudulent_request($conn)) { http_response_code(204); $conn->close(); exit(); }
}
if (!function_exists('get_visitor_details')) {
    function get_visitor_details() { return ['ip'=>'127.0.0.1','country'=>'XX','os'=>'unknown','browser'=>'unknown','device'=>'unknown','geo_source'=>'default']; }
}
if (!function_exists('get_setting')) {
    function get_setting($key, $conn) { return 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'); }
}

$minimum_bid_floor = (float)get_setting('minimum_bid_floor', $conn);
if ($minimum_bid_floor <= 0) $minimum_bid_floor = 0.01;

define('EXTERNAL_CAMPAIGN_ID', -1);
define('EXTERNAL_CREATIVE_ID', -1);

$supply_source_id_for_log = 0; $zone_id_for_log = 0; $is_bid_sent_for_log = 0; $price_for_log = null;
$visitor_details_for_log = get_visitor_details();
$country_for_log = $visitor_details_for_log['country'];

header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['id' => uniqid(), 'error' => 'Method Not Allowed'])); }
$request_body = file_get_contents('php://input');
$bid_request = json_decode($request_body, true);
$request_id = $bid_request['id'] ?? uniqid();
$site = $bid_request['site'] ?? [];
$domain_for_log = $site['domain'] ?? 'unknown.com';
if (json_last_error() !== JSON_ERROR_NONE) { http_response_code(400); exit(json_encode(['id' => $request_id, 'error' => 'Invalid JSON'])); }

$supply_key = $_GET['key'] ?? '';
$stmt_source = $conn->prepare("SELECT rs.id, rs.user_id, rs.default_zone_id, u.revenue_share FROM rtb_supply_sources rs JOIN users u ON rs.user_id = u.id WHERE rs.supply_key = ? AND rs.status = 'active'");
$stmt_source->bind_param("s", $supply_key); $stmt_source->execute();
$supply_source = $stmt_source->get_result()->fetch_assoc(); $stmt_source->close();
if (!$supply_source) {
    http_response_code(403);
    exit(json_encode(['id' => $request_id, 'error' => 'Invalid or Inactive Supply Key']));
}
$publisher_revenue_share = (float)($supply_source['revenue_share'] ?? 0);
$supply_source_id_for_log = $supply_source['id'];
$zone_id_for_log = $supply_source['default_zone_id'];
if (empty($zone_id_for_log)) { http_response_code(500); exit(json_encode(['id' => $request_id, 'error' => 'Supply source is not configured with a default zone.'])); }

$imp = $bid_request['imp'][0] ?? null; $impid = $imp['id'] ?? '1';
$is_video_request = isset($imp['video']);
$ad_format = 'banner';
if (isset($_GET['format'])) $ad_format = strtolower($_GET['format']);
if (!empty($bid_request['ad_format'])) $ad_format = strtolower($bid_request['ad_format']);
if (!empty($imp['ext']['ad_format'])) $ad_format = strtolower($imp['ext']['ad_format']);

if ($is_video_request) { $w = $imp['video']['w'] ?? 640; $h = $imp['video']['h'] ?? 480; }
else { $w = $imp['banner']['w'] ?? 0; $h = $imp['banner']['h'] ?? 0; }
$req_size = "{$w}x{$h}";

$best_bid_price = 0; $winning_creative = null; $winning_source = 'none'; $winning_ssp_id = null;
$current_floor_price = max($best_bid_price, $minimum_bid_floor);

if ($ad_format === 'popunder') {
    $sql_internal = "SELECT cr.*, c.id as campaign_id FROM creatives cr
        JOIN campaigns c ON cr.campaign_id = c.id
        WHERE c.status = 'active'
        AND cr.status = 'active'
        AND cr.creative_type = 'popunder'
        AND c.ad_format_id = 4
        AND cr.bid_amount >= ?
        ORDER BY cr.bid_amount DESC, RAND() LIMIT 1";
    $stmt_internal = $conn->prepare($sql_internal);
    $stmt_internal->bind_param("d", $current_floor_price);
} else if ($is_video_request) {
    $sql_internal = "SELECT v.*, c.id as campaign_id, v.bid_model, v.bid_amount, v.vast_type, v.video_url, v.duration, v.landing_url, v.name FROM video_creatives v
        JOIN campaigns c ON v.campaign_id = c.id
        WHERE c.status = 'active'
        AND v.status = 'active'
        AND v.bid_amount >= ?
        ORDER BY v.bid_amount DESC, RAND() LIMIT 1";
    $stmt_internal = $conn->prepare($sql_internal);
    $stmt_internal->bind_param("d", $current_floor_price);
} else {
    $sql_internal = "SELECT cr.*, c.id as campaign_id FROM creatives cr
        JOIN campaigns c ON cr.campaign_id = c.id
        WHERE c.status = 'active'
        AND cr.status = 'active'
        AND cr.creative_type != 'popunder'
        AND (cr.sizes = ? OR cr.sizes = 'all')
        AND cr.bid_amount >= ?
        ORDER BY cr.bid_amount DESC, RAND() LIMIT 1";
    $stmt_internal = $conn->prepare($sql_internal);
    $stmt_internal->bind_param("sd", $req_size, $current_floor_price);
}
$stmt_internal->execute();
$internal_candidate = $stmt_internal->get_result()->fetch_assoc();
$stmt_internal->close();

if ($internal_candidate) {
    $best_bid_price = (float)($internal_candidate['bid_amount'] ?? 0);
    $winning_creative = $internal_candidate;
    $winning_source = 'internal';
    $winning_ssp_id = null;
}

if ($winning_source !== 'none') {
    $publisher_price = $best_bid_price * ($publisher_revenue_share / 100.0);
    $is_bid_sent_for_log = 1; $price_for_log = $best_bid_price;
    $adm = ''; $cid = ''; $crid = ''; $adomain = []; $today = date('Y-m-d');

    if ($winning_source === 'internal') {
        $cid = (string)$winning_creative['campaign_id']; $crid = (string)$winning_creative['id'];
        $adomain = !empty($winning_creative['landing_url']) ? [parse_url($winning_creative['landing_url'], PHP_URL_HOST)] : [];
        $cost_for_impression = ($winning_creative['bid_model'] ?? 'cpm') === 'cpm'
            ? ($best_bid_price / 1000.0) : 0.0;
        $ad_server_domain = get_setting('ad_server_domain', $conn);

        if ($ad_format === 'popunder') {
            // Popunder = ADM is direct URL (NOT JS)
            $adm = $winning_creative['landing_url'];
            $w = 0; $h = 0;
        } else if ($is_video_request) {
            $vast_type = $winning_creative['vast_type'] ?? '';
            $video_url = $winning_creative['video_url'] ?? '';
            $duration = $winning_creative['duration'] ?? 30;
            $adtitle = $winning_creative['name'] ?? '';
            $landing_url = $winning_creative['landing_url'] ?? '';

            if ($vast_type === 'third_party') {
                $adm = '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<VAST version="3.0">'
                    . '<Ad id="' . htmlspecialchars($crid) . '">'
                    . '<Wrapper>'
                    . '<AdSystem>Clicterra</AdSystem>'
                    . '<VASTAdTagURI><![CDATA[' . htmlspecialchars($video_url) . ']]></VASTAdTagURI>'
                    . '<Impression><![CDATA[' . $ad_server_domain . '/track.php?event=impression&cid=' . $crid . ']]></Impression>'
                    . '<Creatives><Creative><Linear>'
                    . '<TrackingEvents>'
                    . '<Tracking event="progress" offset="00:00:05"><![CDATA[' . $ad_server_domain . '/track.php?event=progress&cid=' . $crid . ']]></Tracking>'
                    . '</TrackingEvents>'
                    . '<VideoClicks>'
                    . '<ClickTracking><![CDATA[' . $ad_server_domain . '/click.php?cid=' . $crid . '&zone_id=' . $zone_id_for_log . ']]></ClickTracking>'
                    . '</VideoClicks>'
                    . '</Linear></Creative></Creatives>'
                    . '</Wrapper></Ad></VAST>';
            } else {
                if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
                    $video_url = $ad_server_domain . '/admin/' . ltrim($video_url, '/');
                }
                $adm = '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<VAST version="3.0">'
                    . '<Ad id="' . htmlspecialchars($crid) . '">'
                    . '<InLine>'
                    . '<AdSystem>Clicterra</AdSystem>'
                    . '<AdTitle><![CDATA[' . htmlspecialchars($adtitle) . ']]></AdTitle>'
                    . '<Impression><![CDATA[' . $ad_server_domain . '/track.php?event=impression&cid=' . $crid . ']]></Impression>'
                    . '<Creatives><Creative><Linear>'
                    . '<Duration>' . gmdate("H:i:s", $duration) . '</Duration>'
                    . '<TrackingEvents>'
                    . '<Tracking event="progress" offset="00:00:05"><![CDATA[' . $ad_server_domain . '/track.php?event=progress&cid=' . $crid . ']]></Tracking>'
                    . '</TrackingEvents>'
                    . '<VideoClicks>'
                    . '<ClickThrough><![CDATA[' . htmlspecialchars($landing_url) . ']]></ClickThrough>'
                    . '<ClickTracking><![CDATA[' . $ad_server_domain . '/click.php?cid=' . $crid . '&zone_id=' . $zone_id_for_log . ']]></ClickTracking>'
                    . '</VideoClicks>'
                    . '<MediaFiles>'
                    . '<MediaFile delivery="progressive" type="video/mp4" width="' . $w . '" height="' . $h . '"><![CDATA[' . htmlspecialchars($video_url) . ']]></MediaFile>'
                    . '</MediaFiles>'
                    . '</Linear></Creative></Creatives>'
                    . '</InLine></Ad></VAST>';
            }
        } else {
            $click_url = $ad_server_domain . "/click.php?cid=" . $crid . "&zone_id=" . $zone_id_for_log;
            if ($winning_creative['creative_type'] === 'image' && !empty($winning_creative['landing_url'])) {
                $image_source = htmlspecialchars($winning_creative['image_url']);
                if (strpos($image_source, 'uploads/') === 0) $image_source = $ad_server_domain . "/admin/" . $image_source;
                $adm = '<a href="' . $click_url . '" target="_blank" rel="noopener"><img src="' . $image_source . '" alt="Ad" border="0" style="width:100%;height:auto;display:block;"></a>';
            } else {
                $adm = $winning_creative['script_content'];
            }
        }

        // --- PATCH ANTI DUPLIKAT IMPRESSION (PER IP+UA+ZONE+CREATIVE+TANGGAL) ---
        $ip = $visitor_details_for_log['ip'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $check_stmt = $conn->prepare("SELECT 1 FROM campaign_stats WHERE campaign_id=? AND creative_id=? AND zone_id=? AND stat_date=? AND ip=? AND user_agent=? LIMIT 1");
        $check_stmt->bind_param("iiisss", $cid, $crid, $zone_id_for_log, $today, $ip, $ua);
        $check_stmt->execute(); $already_exists = $check_stmt->get_result()->fetch_row(); $check_stmt->close();

        if (!$already_exists) {
            // Catat impresi unik saja
            $insert_stmt = $conn->prepare("INSERT INTO campaign_stats (campaign_id, creative_id, zone_id, country, os, browser, device, stat_date, impressions, cost, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?) ON DUPLICATE KEY UPDATE impressions = impressions + 1, cost = cost + VALUES(cost)");
            $insert_stmt->bind_param(
                "iiissssssds",
                $cid, $crid, $zone_id_for_log,
                $visitor_details_for_log['country'],
                $visitor_details_for_log['os'],
                $visitor_details_for_log['browser'],
                $visitor_details_for_log['device'],
                $today,
                $cost_for_impression,
                $ip,
                $ua
            );
            $insert_stmt->execute(); $insert_stmt->close();
        }
    }

    http_response_code(200);
    echo json_encode([
        'id' => $request_id,
        'seatbid' => [[
            'bid' => [[
                'id' => uniqid('bid_'),
                'impid' => $impid,
                'price' => (float)$publisher_price,
                'adm' => $adm,
                'adomain' => $adomain,
                'cid' => $cid,
                'crid' => $crid,
                'w' => (int)$w,
                'h' => (int)$h
            ]],
            'seat' => 'clicterra_dps'
        ]]
    ]);
} else {
    $is_bid_sent_for_log = 0;
    http_response_code(204);
}

$stmt_log = $conn->prepare("INSERT INTO rtb_requests (supply_source_id, zone_id, is_bid_sent, winning_price_cpm, country, source_domain) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_log->bind_param("iiidss", $supply_source_id_for_log, $zone_id_for_log, $is_bid_sent_for_log, $price_for_log, $country_for_log, $domain_for_log);
$stmt_log->execute(); $stmt_log->close();

$conn->close();
exit();
?>

