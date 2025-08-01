<?php
// File: /publisher/zone-action.php (FINAL & VERIFIED)

require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add_zone'])) {
    header('Location: sites.php');
    exit();
}

$publisher_id = $_SESSION['publisher_id'];
$site_id = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);
$ad_format_id = filter_input(INPUT_POST, 'ad_format_id', FILTER_VALIDATE_INT);
$name = trim($_POST['name']);
$size = trim($_POST['size']);

$format_name = '';
if ($ad_format_id) {
    $format_stmt = $conn->prepare("SELECT name FROM ad_formats WHERE id = ?");
    $format_stmt->bind_param("i", $ad_format_id);
    $format_stmt->execute();
    $format_result = $format_stmt->get_result()->fetch_assoc();
    $format_name = strtolower($format_result['name'] ?? '');
    $format_stmt->close();
}

if ($format_name === 'video' || $format_name === 'popunder') {
    $size = 'responsive'; // Memberikan nilai default
}

if (!$site_id || !$ad_format_id || empty($name) || empty($size)) {
    $_SESSION['error_message'] = 'Please fill all required fields correctly.';
    header('Location: sites.php');
    exit();
}

$stmt_check = $conn->prepare("SELECT id FROM sites WHERE id = ? AND user_id = ? AND status = 'approved'");
$stmt_check->bind_param("ii", $site_id, $publisher_id);
$stmt_check->execute();
$site_check_result = $stmt_check->get_result();
$stmt_check->close();

if ($site_check_result->num_rows !== 1) {
    $_SESSION['error_message'] = 'Invalid site or site not approved.';
    header('Location: sites.php');
    exit();
}

$stmt_insert = $conn->prepare("INSERT INTO zones (site_id, ad_format_id, name, size) VALUES (?, ?, ?, ?)");
$stmt_insert->bind_param("iiss", $site_id, $ad_format_id, $name, $size);

if ($stmt_insert->execute()) {
    $_SESSION['success_message'] = 'Zone "' . htmlspecialchars($name) . '" created successfully.';
} else {
    $_SESSION['error_message'] = 'Failed to create zone: ' . $stmt_insert->error;
}
$stmt_insert->close();

header('Location: sites.php');
exit();
?>