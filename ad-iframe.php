<?php
// File: /ad-iframe.php (Banner for iframe tag, secure with anti-fraud, blacklist, settings)

require_once __DIR__ . '/config/database.php';

// --- SETTINGS & HELPER ---
if (!function_exists('get_setting')) {
    function get_setting($key, $conn) {
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $stmt->bind_result($value);
        $stmt->fetch();
        $stmt->close();
        return $value;
    }
}
$block_bot = get_setting('block_bot', $conn) === '1';
$block_direct_referer = get_setting('block_direct_referer', $conn) === '1';

// --- ANTI-FRAUD LOGIKA ---
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$bot_patterns = '/bot|crawl|spider|slurp|facebook|telegram|curl|python|wget|httpclient|ahrefs|mj12|semrush|siteaudit|uptime|monitor|dataprovider|checker|zgrab|masscan|scan|search|scrapy|phantom|node|go-http/i';

if ($block_bot && preg_match($bot_patterns, $user_agent)) {
    http_response_code(204); exit();
}
if ($block_direct_referer && (empty($referer) || preg_match('/(localhost|127\.0\.0\.1|file:\/\/|about:blank|chrome-extension|sandbox)/i', $referer))) {
    http_response_code(204); exit();
}

if (file_exists(__DIR__ . '/includes/fraud_detector.php')) {
    require_once __DIR__ . '/includes/fraud_detector.php';
    if (is_fraudulent_request($conn)) {
        http_response_code(204);
        exit();
    }
}

header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

function exit_silently($message = "Ad serving failed") {
    error_log("ad-iframe.php Exit: " . $message);
    echo "<!-- " . htmlspecialchars($message) . " -->";
    exit();
}

$zone_id = filter_input(INPUT_GET, 'zone_id', FILTER_VALIDATE_INT);
if (!$zone_id) {
    exit_silently("No 'zone_id' parameter provided.");
}

$stmt_zone = $conn->prepare(
    "SELECT z.size, s.user_id, s.url as site_url, rs.supply_key 
     FROM zones z 
     JOIN sites s ON z.site_id = s.id 
     JOIN rtb_supply_sources rs ON s.user_id = rs.user_id 
     WHERE z.id = ? AND rs.status = 'active' LIMIT 1"
);
if ($stmt_zone) {
    $stmt_zone->bind_param("i", $zone_id);
    $stmt_zone->execute();
    $zone_info = $stmt_zone->get_result()->fetch_assoc();
    $stmt_zone->close();
} else {
    $zone_info = null;
}

if (!$zone_info || empty($zone_info['supply_key'])) {
    exit_silently("Zone or active supply key not found for Zone ID: {$zone_id}");
}

$size = explode('x', $zone_info['size']);
$width = $size[0] ?? 300;
$height = $size[1] ?? 250;

$mock_bid_request = [
    'id' => 'ron-iframe-' . uniqid(),
    'imp' => [
        [
            'id' => '1',
            'banner' => [
                'w' => (int)$width,
                'h' => (int)$height,
            ],
            'tagid' => (string)$zone_id
        ]
    ],
    'site' => [
        'id' => (string)$zone_id,
        'page' => $_SERVER['HTTP_REFERER'] ?? $zone_info['site_url'],
        'domain' => parse_url($_SERVER['HTTP_REFERER'] ?? $zone_info['site_url'], PHP_URL_HOST),
        'publisher' => [
            'id' => (string)$zone_info['user_id']
        ]
    ],
    'device' => [
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ],
    'user' => [
        'id' => md5(($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''))
    ],
    'at' => 1,
    'tmax' => 500
];
$request_body_json = json_encode($mock_bid_request);

$rtb_handler_domain = get_setting('ad_server_domain', $conn);
$rtb_handler_url = "{$rtb_handler_domain}/rtb-handler.php?key={$zone_info['supply_key']}&internal_call=1";

$ch = curl_init($rtb_handler_url);
curl_setopt_array($ch, [
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => $request_body_json,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 1
]);
$response_json = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($response_json)) {
    $bid_response = json_decode($response_json, true);
    $ad_markup = $bid_response['seatbid'][0]['bid'][0]['adm'] ?? '';
    if (!empty($ad_markup)) {
        echo $ad_markup;
    } else {
        exit_silently("Auction won but Ad Markup was empty.");
    }
} else {
    exit_silently("No ad available from auction (HTTP: {$http_code}).");
}

$conn->close();
exit();
?>