<?php
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
$block_iframe = get_setting('block_iframe', $conn) === '1';
$block_bot = get_setting('block_bot', $conn) === '1';
$block_direct_referer = get_setting('block_direct_referer', $conn) === '1';

header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// --- ANTI-FRAUD ---
if ($block_iframe) {
    if (
        isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe'
        || (isset($_SERVER['HTTP_REFERER']) && preg_match('/iframe/i', $_SERVER['HTTP_REFERER']))
    ) {
        error_log("Fraud detected: Attempt to load popunder.php in iframe. IP: ".$_SERVER['REMOTE_ADDR']);
        http_response_code(204);
        exit();
    }
}
if ($block_bot) {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bot_patterns = '/bot|crawl|spider|slurp|facebook|telegram|curl|python|wget|httpclient|ahrefs|mj12|semrush|siteaudit|uptime|monitor|dataprovider|checker|zgrab|masscan|scan|search|scrapy|phantom|node|go-http/i';
    if (preg_match($bot_patterns, $user_agent)) {
        error_log("Fraud detected: Bot user-agent on popunder.php. UA: $user_agent, IP: ".$_SERVER['REMOTE_ADDR']);
        http_response_code(204);
        exit();
    }
}
if ($block_direct_referer) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (empty($referer) || preg_match('/(localhost|127\.0\.0\.1|file:\/\/|about:blank|chrome-extension|sandbox|\.ru\/|\.cn\/)/i', $referer)) {
        error_log("Fraud detected: Suspicious/no referer on popunder.php. Referer: $referer, IP: ".$_SERVER['REMOTE_ADDR']);
        http_response_code(204);
        exit();
    }
}

// --- Helper: Exit silently ---
function exit_silently($message = "Popunder serving failed") {
    error_log("Popunder.php Exit: " . $message);
    echo "<!DOCTYPE html><html><head><title></title></head><body><script>window.close();</script></body></html>";
    exit();
}

// --- Validasi input zone_id ---
$zone_id = filter_input(INPUT_GET, 'zone_id', FILTER_VALIDATE_INT);
if (!$zone_id) {
    exit_silently("No 'zone_id' parameter provided.");
}

// --- Dapatkan info zona dan campaign terkait ---
$stmt_zone = $conn->prepare(
    "SELECT z.size, z.ad_format_id, z.site_id, s.user_id, s.url as site_url, rs.supply_key, c.id as campaign_id 
     FROM zones z 
     JOIN sites s ON z.site_id = s.id 
     JOIN rtb_supply_sources rs ON s.user_id = rs.user_id 
     JOIN campaigns c ON c.ad_format_id = z.ad_format_id 
     WHERE z.id = ? AND rs.status = 'active' AND c.status = 'active' LIMIT 1"
);
if ($stmt_zone) {
    $stmt_zone->bind_param("i", $zone_id);
    $stmt_zone->execute();
    $zone_info = $stmt_zone->get_result()->fetch_assoc();
    $stmt_zone->close();
} else {
    $zone_info = null;
}

if (!$zone_info || empty($zone_info['supply_key']) || empty($zone_info['campaign_id'])) {
    exit_silently("Zone/campaign/supply key not found for Zone ID: {$zone_id}");
}

// --- Cari creative popunder aktif untuk campaign ini ---
$stmt_creative = $conn->prepare(
    "SELECT landing_url FROM creatives WHERE campaign_id = ? AND creative_type = 'popunder' AND status = 'active' AND landing_url IS NOT NULL AND landing_url != '' ORDER BY id DESC LIMIT 1"
);
$stmt_creative->bind_param("i", $zone_info['campaign_id']);
$stmt_creative->execute();
$stmt_creative->bind_result($landing_url);
$stmt_creative->fetch();
$stmt_creative->close();

// Jika ada landing_url, langsung redirect
if (!empty($landing_url)) {
    header("Location: $landing_url");
    $conn->close();
    exit();
}

// --- Fallback ke RTB handler (support RTB popunder) ---
$size = explode('x', $zone_info['size']);
$width = $size[0] ?? 1;
$height = $size[1] ?? 1;

$mock_bid_request = [
    'id' => 'popunder-' . uniqid(),
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
    $adm = $bid_response['seatbid'][0]['bid'][0]['adm'] ?? '';
    $rtb_landing_url = $bid_response['seatbid'][0]['bid'][0]['landing_url'] ?? '';

    if (!empty($rtb_landing_url)) {
        header("Location: $rtb_landing_url");
        $conn->close();
        exit();
    }
    if (!empty($adm) && preg_match('/window\.location\s*=\s*[\'"](.+)[\'"]/', $adm, $match)) {
        $url = $match[1];
        echo "<script>window.location='$url';</script>";
        $conn->close();
        exit();
    }
    if (!empty($adm)) {
        echo $adm;
        $conn->close();
        exit();
    }
    // Fallback: buka Google
    echo "<!DOCTYPE html><html><head><title>Popunder</title></head><body><script>
        window.location.href = 'https://google.com';
    </script></body></html>";
    $conn->close();
    exit();
} else {
    // Tidak ada iklan, tutup window
    echo "<!DOCTYPE html><html><head><title>No Ad</title></head><body><script>window.close();</script></body></html>";
    $conn->close();
    exit();
}
?>