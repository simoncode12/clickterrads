<?php
// File: /admin/campaigns-action.php (FINAL - With Conditional Redirect after creation)

require_once __DIR__ . '/init.php';

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: campaigns.php');
    exit();
}

// FUNGSI DIPERBARUI: Sekarang menerima parameter $location untuk tujuan redirect yang dinamis
function redirect_with_message($type, $message, $location = 'campaigns.php') {
    $_SESSION[$type . '_message'] = $message;
    header("Location: " . $location);
    exit();
}

// Aksi: Membuat Kampanye Baru
if (isset($_POST['create_campaign'])) {
    $ad_format_id = filter_input(INPUT_POST, 'ad_format_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $advertiser_id = filter_input(INPUT_POST, 'advertiser_id', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $serve_on_internal = isset($_POST['serve_on_internal']) ? 1 : 0;
    $allow_external_rtb = isset($_POST['allow_external_rtb']) ? 1 : 0;

    if (empty($name) || !$advertiser_id || !$category_id || !$ad_format_id) {
        redirect_with_message('error', 'Please fill all required campaign details.');
    }

    $conn->begin_transaction();
    try {
        // Masukkan data kampanye utama
        $stmt = $conn->prepare("INSERT INTO campaigns (name, advertiser_id, category_id, ad_format_id, serve_on_internal, allow_external_rtb, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("siiiii", $name, $advertiser_id, $category_id, $ad_format_id, $serve_on_internal, $allow_external_rtb);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute campaign insert: " . $stmt->error);
        }
        $campaign_id = $conn->insert_id;
        $stmt->close();

        // Masukkan detail penargetan
        $countries = isset($_POST['countries']) && is_array($_POST['countries']) ? implode(',', $_POST['countries']) : '';
        $browsers = isset($_POST['browsers']) && is_array($_POST['browsers']) ? implode(',', $_POST['browsers']) : '';
        $devices = isset($_POST['devices']) && is_array($_POST['devices']) ? implode(',', $_POST['devices']) : '';
        $os = isset($_POST['os']) && is_array($_POST['os']) ? implode(',', $_POST['os']) : '';
        $connection_types = isset($_POST['connection_types']) && is_array($_POST['connection_types']) ? implode(',', $_POST['connection_types']) : '';

        $stmt_targeting = $conn->prepare("INSERT INTO campaign_targeting (campaign_id, countries, browsers, devices, os, connection_types) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_targeting->bind_param("isssss", $campaign_id, $countries, $browsers, $devices, $os, $connection_types);
        if (!$stmt_targeting->execute()) {
            throw new Exception("Failed to execute targeting insert: " . $stmt_targeting->error);
        }
        $stmt_targeting->close();

        // --- LOGIKA BARU: Tentukan URL Redirect Berdasarkan Format Iklan ---
        $format_name = '';
        if ($ad_format_id) {
            $stmt_format = $conn->prepare("SELECT name FROM ad_formats WHERE id = ?");
            $stmt_format->bind_param("i", $ad_format_id);
            $stmt_format->execute();
            $result = $stmt_format->get_result()->fetch_assoc();
            $format_name = strtolower($result['name'] ?? '');
            $stmt_format->close();
        }

        $redirect_url = 'campaigns.php'; // Lokasi redirect default
        if ($format_name === 'video') {
            $redirect_url = 'video-creatives.php?campaign_id=' . $campaign_id;
        } elseif ($format_name === 'banner') {
            $redirect_url = 'ron-creative.php?campaign_id=' . $campaign_id;
        }
        // Untuk format lain akan diarahkan ke halaman default 'campaigns.php'

        $conn->commit();
        $success_message = 'Campaign "' . htmlspecialchars($name) . '" created successfully. Now, add your creatives.';
        // Panggil fungsi redirect dengan URL yang sudah ditentukan
        redirect_with_message('success', $success_message, $redirect_url);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Campaign creation failed: " . $e->getMessage());
        redirect_with_message('error', 'Failed to create campaign: ' . $e->getMessage());
    }
}

// Aksi: Update Kampanye
if (isset($_POST['update_campaign'])) {
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $advertiser_id = filter_input(INPUT_POST, 'advertiser_id', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $serve_on_internal = isset($_POST['serve_on_internal']) ? 1 : 0;
    $allow_external_rtb = isset($_POST['allow_external_rtb']) ? 1 : 0;

    if (!$campaign_id || empty($name) || !$advertiser_id || !$category_id) {
        redirect_with_message('error', 'Invalid data provided for campaign update.');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE campaigns SET name = ?, advertiser_id = ?, category_id = ?, serve_on_internal = ?, allow_external_rtb = ? WHERE id = ?");
        $stmt->bind_param("siiiii", $name, $advertiser_id, $category_id, $serve_on_internal, $allow_external_rtb, $campaign_id);
        if (!$stmt->execute()) { throw new Exception("Failed to execute campaign update: " . $stmt->error); }
        $stmt->close();

        $countries = isset($_POST['countries']) && is_array($_POST['countries']) ? implode(',', $_POST['countries']) : '';
        $browsers = isset($_POST['browsers']) && is_array($_POST['browsers']) ? implode(',', $_POST['browsers']) : '';
        $devices = isset($_POST['devices']) && is_array($_POST['devices']) ? implode(',', $_POST['devices']) : '';
        $os = isset($_POST['os']) && is_array($_POST['os']) ? implode(',', $_POST['os']) : '';
        $connection_types = isset($_POST['connection_types']) && is_array($_POST['connection_types']) ? implode(',', $_POST['connection_types']) : '';

        $stmt_targeting = $conn->prepare("INSERT INTO campaign_targeting (campaign_id, countries, browsers, devices, os, connection_types) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE countries = VALUES(countries), browsers = VALUES(browsers), devices = VALUES(devices), os = VALUES(os), connection_types = VALUES(connection_types)");
        $stmt_targeting->bind_param("isssss", $campaign_id, $countries, $browsers, $devices, $os, $connection_types);
        if (!$stmt_targeting->execute()) { throw new Exception("Failed to execute targeting upsert: " . $stmt_targeting->error); }
        $stmt_targeting->close();

        $conn->commit();
        redirect_with_message('success', 'Campaign "' . htmlspecialchars($name) . '" updated successfully.');

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Campaign update failed: " . $e->getMessage());
        redirect_with_message('error', 'Failed to update campaign: ' . $e->getMessage());
    }
}

// Aksi: Mengubah Status Kampanye (Aktif/Paused)
if (isset($_POST['update_campaign_status'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $current_status = $_POST['current_status'];

    if (!$id || !in_array($current_status, ['active', 'paused'])) {
        redirect_with_message('error', 'Invalid campaign ID or status.');
    }

    $new_status = ($current_status === 'active') ? 'paused' : 'active';
    $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Campaign status changed to ' . ucfirst($new_status) . '.');
    } else {
        redirect_with_message('error', 'Failed to update campaign status: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Menghapus Kampanye
if (isset($_POST['delete_campaign'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        redirect_with_message('error', 'Invalid campaign ID.');
    }

    $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Campaign and all associated data have been deleted.');
    } else {
        redirect_with_message('error', 'Failed to delete campaign: ' . $stmt->error);
    }
    $stmt->close();
}

// Fallback redirect jika tidak ada aksi yang cocok
header('Location: campaigns.php');
exit();
?>
