<?php
// File: /impression.php (NEW - The Correct Way to Track Impressions)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/visitor_detector.php';

/**
 * Mengirim response gambar transparan 1x1 piksel dan menghentikan skrip.
 */
function exitWithPixel() {
    header('Content-Type: image/gif');
    // 1x1 transparent GIF
    echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICRAEAOw==');
    exit();
}

// Ambil parameter dari URL pixel
$campaign_id = filter_input(INPUT_GET, 'cid', FILTER_VALIDATE_INT);
$creative_id = filter_input(INPUT_GET, 'crid', FILTER_VALIDATE_INT);
$zone_id = filter_input(INPUT_GET, 'zid', FILTER_VALIDATE_INT);
$cost = filter_input(INPUT_GET, 'cost', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

// Jika parameter inti tidak ada, jangan lakukan apa-apa
if ($creative_id === false || $zone_id === false) {
    exitWithPixel();
}

// Dapatkan detail pengunjung yang akurat dari browser asli
$visitor = get_visitor_details();
$today = date('Y-m-d');

// Dapatkan ssp_partner_id jika ini adalah traffic eksternal
$ssp_partner_id = ($campaign_id === -1) ? filter_input(INPUT_GET, 'sspid', FILTER_VALIDATE_INT, ['options' => ['default' => null]]) : null;

// Masukkan data yang sekarang sudah akurat ke database
$stmt_stats = $conn->prepare(
    "INSERT INTO campaign_stats (campaign_id, creative_id, ssp_partner_id, zone_id, country, os, browser, device, stat_date, impressions, cost) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?) 
     ON DUPLICATE KEY UPDATE impressions = impressions + 1, cost = cost + VALUES(cost)"
);

if ($stmt_stats) {
    $stmt_stats->bind_param("iiiisssssd", 
        $campaign_id, 
        $creative_id, 
        $ssp_partner_id, 
        $zone_id,
        $visitor['country'], 
        $visitor['os'], 
        $visitor['browser'], 
        $visitor['device'], 
        $today, 
        $cost
    );
    $stmt_stats->execute();
    $stmt_stats->close();
} else {
    error_log("Failed to prepare statement for impression tracking.");
}

$conn->close();

exitWithPixel();
?>