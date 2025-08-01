<?php
// File: /ad.php (Show creative sesuai zona, fallback ke 'all', fallback RTB/blank)

require_once __DIR__ . '/config/database.php';

// --- ANTI-FRAUD CHECK ---
if (file_exists(__DIR__ . '/includes/fraud_detector.php')) {
    require_once __DIR__ . '/includes/fraud_detector.php';
    if (is_fraudulent_request($conn)) {
        http_response_code(204); exit();
    }
}

header('Content-Type: application/javascript');
header('Access-Control-Allow-Origin: *');

function exit_silently($message = "Ad serving failed") {
    error_log("Ad.php Exit: " . $message);
    echo "/* " . htmlspecialchars($message) . " */";
    exit();
}

$zone_id = filter_input(INPUT_GET, 'zone_id', FILTER_VALIDATE_INT);
if (!$zone_id) {
    exit_silently("No 'zone_id' parameter provided.");
}

// --- Ambil info zona ---
$stmt_zone = $conn->prepare(
    "SELECT z.size, z.ad_format_id, s.user_id, s.url as site_url, rs.supply_key
     FROM zones z
     JOIN sites s ON z.site_id = s.id
     JOIN rtb_supply_sources rs ON s.user_id = rs.user_id
     WHERE z.id = ? AND rs.status = 'active' LIMIT 1"
);
$stmt_zone->bind_param("i", $zone_id);
$stmt_zone->execute();
$zone_info = $stmt_zone->get_result()->fetch_assoc();
$stmt_zone->close();

if (!$zone_info || empty($zone_info['ad_format_id'])) {
    exit_silently("Zone not found or inactive supply key.");
}

$size = $zone_info['size'];
$ad_format_id = $zone_info['ad_format_id'];

// --- Ambil semua campaign aktif untuk format ini ---
$stmt_campaigns = $conn->prepare(
    "SELECT id FROM campaigns WHERE ad_format_id = ? AND status = 'active'"
);
$stmt_campaigns->bind_param("i", $ad_format_id);
$stmt_campaigns->execute();
$result_campaigns = $stmt_campaigns->get_result();

$campaign_ids = [];
while ($row = $result_campaigns->fetch_assoc()) {
    $campaign_ids[] = $row['id'];
}
$stmt_campaigns->close();

if (empty($campaign_ids)) {
    exit_silently("No active campaign for this zone.");
}

$in_campaigns = implode(',', array_map('intval', $campaign_ids));

// --- 1. Cari creative ukuran PERSIS sesuai zona ---
$sql_creative = "
    SELECT script_content 
    FROM creatives
    WHERE campaign_id IN ($in_campaigns) 
      AND creative_type = 'script' 
      AND status = 'active'
      AND sizes = ?
      AND script_content IS NOT NULL 
      AND script_content != ''
    ORDER BY RAND()
    LIMIT 1
";
$stmt_creative = $conn->prepare($sql_creative);
$stmt_creative->bind_param("s", $size);
$stmt_creative->execute();
$stmt_creative->bind_result($script_content);
$found_creative = $stmt_creative->fetch();
$stmt_creative->close();

// --- 2. Kalau tidak ada, cari creative 'all' (universal fallback) ---
if (!$found_creative || empty($script_content)) {
    $sql_creative_all = "
        SELECT script_content 
        FROM creatives
        WHERE campaign_id IN ($in_campaigns) 
          AND creative_type = 'script' 
          AND status = 'active'
          AND sizes = 'all'
          AND script_content IS NOT NULL 
          AND script_content != ''
        ORDER BY RAND()
        LIMIT 1
    ";
    $stmt_creative_all = $conn->prepare($sql_creative_all);
    $stmt_creative_all->execute();
    $stmt_creative_all->bind_result($script_content);
    $found_creative = $stmt_creative_all->fetch();
    $stmt_creative_all->close();
}

// --- OUTPUT jika ada creative internal ---
if ($found_creative && !empty($script_content)) {
    // Output creative: pastikan dalam bentuk document.write
    if (preg_match('/^\s*document\.write\s*\(/i', $script_content)) {
        echo $script_content;
    } else {
        $content = trim($script_content);
        $js = json_encode($content);
        echo "document.write($js);";
    }
    $conn->close();
    exit();
}

// --- Fallback RTB/Blank Banner ---
$size_arr = explode('x', $size);
$width = $size_arr[0] ?? 300;
$height = $size_arr[1] ?? 250;

if (!function_exists('get_setting')) {
    function get_setting($key, $conn) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'] ?? 'userpanel.clicterra.com';
        return "{$protocol}://{$host}";
    }
}

$mock_bid_request = [
    'id' => 'ron-wrapper-' . uniqid(),
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

$rtb_handler_domain = get_setting('rtb_handler_domain', $conn);
$rtb_handler_url = "{$rtb_handler_domain}/rtb-handler.php?key={$zone_info['supply_key']}";

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
        echo "document.write(" . json_encode($ad_markup) . ");";
    } else {
        echo "document.write('<div style=\"width:{$width}px;height:{$height}px;\"></div>');";
    }
} else {
    exit_silently("No ad available from auction (HTTP: {$http_code}).");
}

$conn->close();
exit();
?>

