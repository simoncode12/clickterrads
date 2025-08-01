<?php
// File: /vast.php (UNIVERSAL FINAL VERSION - NO STAT COUNT IN THIS FILE)

// Buffer output
ob_start();

require_once __DIR__ . '/config/database.php';

// Helper
if (file_exists(__DIR__ . '/includes/settings.php')) require_once __DIR__ . '/includes/settings.php';
if (file_exists(__DIR__ . '/includes/visitor_detector.php')) require_once __DIR__ . '/includes/visitor_detector.php';
if (!function_exists('get_visitor_details')) {
    function get_visitor_details() { return ['country' => 'XX', 'os' => 'unknown', 'browser' => 'unknown', 'device' => 'unknown']; }
}
if (!function_exists('get_setting')) {
    function get_setting($key, $conn) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'] ?? 'userpanel.clicterra.com';
        return "{$protocol}://{$host}";
    }
}
if (file_exists(__DIR__ . '/includes/fraud_detector.php')) {
    require_once __DIR__ . '/includes/fraud_detector.php';
    if (is_fraudulent_request($conn)) {
        http_response_code(204);
        exit();
    }
}

// Output empty VAST helper
function exitWithEmptyVast($conn) {
    ob_end_clean();
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/xml; charset=utf-8");
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<VAST version="2.0"></VAST>';
    if ($conn) $conn->close();
    exit();
}

// Main logic
$zone_id = filter_input(INPUT_GET, 'zone_id', FILTER_VALIDATE_INT);
$creative_id_direct = filter_input(INPUT_GET, 'creative_id', FILTER_VALIDATE_INT);
$winning_creative = null;

// Pilih creative jika pakai creative_id langsung
if ($creative_id_direct) {
    $stmt = $conn->prepare("SELECT v.*, c.id as campaign_id FROM video_creatives v JOIN campaigns c ON v.campaign_id = c.id WHERE v.id = ? AND v.status = 'active' AND c.status = 'active'");
    $stmt->bind_param("i", $creative_id_direct);
    $stmt->execute();
    $winning_creative = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$winning_creative) exitWithEmptyVast($conn);
}

// LELANG RON: Jika zone_id (atau tidak ada creative_id)
if (!$winning_creative) {
    $sql_internal = "SELECT v.*, c.id as campaign_id FROM video_creatives v JOIN campaigns c ON v.campaign_id = c.id WHERE v.status = 'active' AND c.status = 'active' AND c.serve_on_internal = 1 ORDER BY RAND() LIMIT 1";
    $res = $conn->query($sql_internal);
    if ($res && $res->num_rows > 0) {
        $winning_creative = $res->fetch_assoc();
    }
    // (Optional: tambahkan logic lelang eksternal di sini jika butuh)
}

if (!$winning_creative) exitWithEmptyVast($conn);

// ==== NO stat tracking here ====
// ==== Impression/stat hanya dicatat di track.php dari <Impression> pixel ====

// Output VAST
$ad_server_domain = get_setting('ad_server_domain', $conn);
ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if ($winning_creative['vast_type'] === 'third_party') {
    // Wrapper ke VAST eksternal
    ?>
<VAST version="2.0">
  <Ad id="<?= htmlspecialchars($winning_creative['id']) ?>">
    <Wrapper>
      <AdSystem>Clicterra</AdSystem>
      <VASTAdTagURI><![CDATA[<?= htmlspecialchars($winning_creative['video_url']) ?>]]></VASTAdTagURI>
      <Error><![CDATA[https://syndicate.svradv.com/track.php?event=error&cid=<?= $winning_creative['id'] ?>&code=[ERRORCODE]]]></Error>
      <Impression><![CDATA[<?= $ad_server_domain ?>/track.php?event=impression&cid=<?= $winning_creative['id'] ?>&creative_id=<?= $winning_creative['id'] ?>&zone_id=<?= $zone_id ?: 0 ?>]]></Impression>
      <Creatives></Creatives>
    </Wrapper>
  </Ad>
</VAST>
    <?php
} else {
    // Inline VAST
    $video_url = $winning_creative['video_url'];
    if ($winning_creative['vast_type'] === 'upload' && !filter_var($video_url, FILTER_VALIDATE_URL)) {
        $video_url = $ad_server_domain . '/admin/' . ltrim($video_url, '/');
    }
    ?>
<VAST version="2.0">
  <Ad id="<?= htmlspecialchars($winning_creative['id']) ?>">
    <InLine>
      <AdSystem>Clicterra</AdSystem>
      <AdTitle><![CDATA[<?= htmlspecialchars($winning_creative['name']) ?>]]></AdTitle>
      <Impression><![CDATA[<?= htmlspecialchars($winning_creative['impression_tracker'] ?? "{$ad_server_domain}/track.php?event=impression&cid={$winning_creative['id']}&creative_id={$winning_creative['id']}&zone_id=" . ($zone_id ?: 0)) ?>]]></Impression>
      <Creatives>
        <Creative>
          <Linear>
            <Duration><?= gmdate("H:i:s", $winning_creative['duration']) ?></Duration>
            <VideoClicks>
              <ClickThrough><![CDATA[<?= htmlspecialchars($winning_creative['landing_url']) ?>]]></ClickThrough>
              <ClickTracking><![CDATA[https://syndicate.svradv.com/click.php?cid=<?= $winning_creative['id'] ?>&zone_id=<?= $zone_id ?: 0 ?>&type=pixel]]></ClickTracking>
            </VideoClicks>
            <MediaFiles>
              <MediaFile delivery="progressive" type="video/mp4" width="640" height="360" scalable="true" maintainAspectRatio="true">
                <![CDATA[<?= htmlspecialchars($video_url) ?>]]>
              </MediaFile>
            </MediaFiles>
          </Linear>
        </Creative>
      </Creatives>
    </InLine>
  </Ad>
</VAST>
    <?php
}

$final_output = ob_get_clean();
$conn->close();
ob_end_clean();
header("Access-Control-Allow-Origin: *");
header("Content-type: application/xml; charset=utf-8");
header("Content-Length: " . strlen($final_output));
echo $final_output;
exit();
?>

