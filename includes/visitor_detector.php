<?php
// File: /includes/visitor_detector.php (FINAL PATCH - PARSE UA DARI RTB JSON ATAU SERVER)

// Composer autoload GeoIP2
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
use GeoIp2\Database\Reader;

function get_real_ip_address() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    foreach ($headers as $header) {
        if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
            $ip_list = explode(',', $_SERVER[$header]);
            $ip = trim($ip_list[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function get_visitor_details() {
    $ip_address = get_real_ip_address();

    // --- PATCH: Ambil User-Agent & Country dari RTB JSON (OpenRTB) ---
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $country = 'XX'; $geo_source = 'default';

    $request_body = file_get_contents('php://input');
    $bid_request = null;
    if (!empty($request_body)) {
        $bid_request = json_decode($request_body, true);
        // User-Agent dari RTB JSON
        if (json_last_error() === JSON_ERROR_NONE && isset($bid_request['device']['ua']) && $bid_request['device']['ua']) {
            $user_agent = $bid_request['device']['ua'];
        }
        // Country dari RTB JSON
        if (json_last_error() === JSON_ERROR_NONE && isset($bid_request['device']['geo']['country'])) {
            $rtb_country = $bid_request['device']['geo']['country'];
            $country = strlen($rtb_country) === 3
                ? (['IDN' => 'ID', 'USA' => 'US', 'DEU' => 'DE'][$rtb_country] ?? substr($rtb_country, 0, 2))
                : $rtb_country;
            $geo_source = 'rtb-request';
            goto skip_geoip;
        }
    }

    // 2. Local GeoIP2
    $geoip_db_path = __DIR__ . '/../geoip/GeoLite2-City.mmdb';
    $alt = [
        __DIR__ . '/../geoip/GeoLite2-Country.mmdb',
        __DIR__ . '/geoip/GeoLite2-City.mmdb',
        __DIR__ . '/GeoLite2-City.mmdb'
    ];
    if (!file_exists($geoip_db_path)) {
        foreach ($alt as $path) { if (file_exists($path)) { $geoip_db_path = $path; break; } }
    }
    if (file_exists($geoip_db_path) && class_exists('\GeoIp2\Database\Reader')) {
        try {
            $reader = new Reader($geoip_db_path);
            $record = $reader->city($ip_address);
            $country = $record->country->isoCode ?? 'XX';
            $geo_source = 'geoip-db';
        } catch (\Exception $e) {
            $api_country = get_country_from_api($ip_address);
            if ($api_country !== 'XX') { $country = $api_country; $geo_source = 'api-fallback'; }
        }
    } else {
        $api_country = get_country_from_api($ip_address);
        if ($api_country !== 'XX') { $country = $api_country; $geo_source = 'api-only'; }
    }
    skip_geoip:

  

    // --- OS Detection ---
    $os = 'Unknown';
    if (preg_match('/windows nt/i', $user_agent)) $os = 'Windows';
    elseif (preg_match('/android/i', $user_agent)) $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) $os = 'iOS';
    elseif (preg_match('/macintosh|mac os x/i', $user_agent)) $os = 'macOS';
    elseif (preg_match('/linux/i', $user_agent)) $os = 'Linux';

    // --- Browser Detection ---
    $browser = 'Unknown';
    if (preg_match('/edg/i', $user_agent)) $browser = 'Edge';
    elseif (preg_match('/opr|opera/i', $user_agent)) $browser = 'Opera';
    elseif (preg_match('/chrome/i', $user_agent) && !preg_match('/edg|opr|opera/i', $user_agent)) $browser = 'Chrome';
    elseif (preg_match('/firefox/i', $user_agent)) $browser = 'Firefox';
    elseif (preg_match('/safari/i', $user_agent) && !preg_match('/chrome|edg|opr|opera/i', $user_agent)) $browser = 'Safari';
    elseif (preg_match('/msie|trident/i', $user_agent)) $browser = 'IE';

    // --- Device Detection ---
    $device = 'Desktop';
    if (preg_match('/tablet|ipad/i', $user_agent)) $device = 'Tablet';
    elseif (preg_match('/mobi|android|iphone|ipod/i', $user_agent)) $device = 'Mobile';

    return [
        'ip'        => $ip_address,
        'country'   => $country,
        'geo_source'=> $geo_source,
        'os'        => $os,
        'browser'   => $browser,
        'device'    => $device
    ];
}

function get_country_from_api($ip) {
    $api_url = "http://ip-api.com/json/{$ip}?fields=countryCode";
    $context = stream_context_create(['http' => ['timeout' => 2]]);
    try {
        $response = @file_get_contents($api_url, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['countryCode']) && !empty($data['countryCode'])) {
                return $data['countryCode'];
            }
        }
    } catch (\Exception $e) {}
    return 'XX';
}
?>

