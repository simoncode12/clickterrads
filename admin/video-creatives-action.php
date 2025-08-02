<?php
// File: /admin/video-creatives-action.php (FINAL & COMPLETE - All functions included)

require_once __DIR__ . '/init.php';

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: campaigns.php');
    exit();
}

// Helper function untuk redirect dengan pesan
function redirect_with_message($type, $message, $location) {
    $_SESSION[$type . '_message'] = $message;
    header("Location: $location");
    exit();
}

// --- AKSI: TAMBAH VIDEO CREATIVE BARU ---
if (isset($_POST['add_video_creative'])) {
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    if (!$campaign_id) { redirect_with_message('error', 'Campaign ID is missing.', 'campaigns.php'); }

    $name = trim($_POST['name']);
    $bid_model = $_POST['bid_model'];
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $landing_url = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
    $impression_tracker = filter_input(INPUT_POST, 'impression_tracker', FILTER_VALIDATE_URL);
    $vast_type = $_POST['vast_type'];
    $video_url = '';
    
    $redirect_location = "video-creatives.php?campaign_id={$campaign_id}";

    if (empty($name) || !$bid_amount || !$duration) {
        redirect_with_message('error', 'Please fill all required fields correctly.', $redirect_location);
    }

    // Validasi sumber video berdasarkan tipe
    switch ($vast_type) {
        case 'third_party':
            $video_url = filter_input(INPUT_POST, 'vast_url', FILTER_VALIDATE_URL);
            if (!$video_url) { redirect_with_message('error', 'A valid VAST Tag URL is required.', $redirect_location); }
            $landing_url = null; // Landing URL tidak relevan untuk VAST pihak ketiga
            break;
        case 'hotlink':
            $video_url = filter_input(INPUT_POST, 'video_url_hotlink', FILTER_VALIDATE_URL);
            if (!$video_url) { redirect_with_message('error', 'A valid video file URL is required.', $redirect_location); }
            break;
        case 'upload':
            if (isset($_FILES['video_file_upload']) && $_FILES['video_file_upload']['error'] == 0) {
                $upload_dir = __DIR__ . '/uploads/videos/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                $file_name = "vid_" . $campaign_id . "_" . time() . '_' . basename($_FILES['video_file_upload']['name']);
                if (move_uploaded_file($_FILES['video_file_upload']['tmp_name'], $upload_dir . $file_name)) {
                    $video_url = 'uploads/videos/' . $file_name;
                } else {
                    redirect_with_message('error', 'Failed to upload video file.', $redirect_location);
                }
            } else {
                redirect_with_message('error', 'Please select a video file to upload.', $redirect_location);
            }
            break;
        default:
            redirect_with_message('error', 'Invalid creative source type.', $redirect_location);
            break;
    }
    
    if (empty($landing_url) && $vast_type !== 'third_party') {
        redirect_with_message('error', 'Landing page URL is required for this creative type.', $redirect_location);
    }

    $stmt = $conn->prepare("INSERT INTO video_creatives (campaign_id, name, bid_model, bid_amount, status, vast_type, video_url, landing_url, impression_tracker, duration) VALUES (?, ?, ?, ?, 'active', ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdssssi", $campaign_id, $name, $bid_model, $bid_amount, $vast_type, $video_url, $landing_url, $impression_tracker, $duration);
    
    if ($stmt->execute()) {
        redirect_with_message('success', 'Video creative "' . htmlspecialchars($name) . '" was created.', $redirect_location);
    } else {
        redirect_with_message('error', 'Database error: ' . $stmt->error, $redirect_location);
    }
    $stmt->close();
}

// --- AKSI UPDATE SATU CREATIVE DARI HALAMAN EDIT ---
if (isset($_POST['update_video_creative'])) {
    $creative_id = filter_input(INPUT_POST, 'creative_id', FILTER_VALIDATE_INT);
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $bid_model = $_POST['bid_model'];
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $landing_url = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL) ?: NULL;
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $status = $_POST['status'];
    $impression_tracker = filter_input(INPUT_POST, 'impression_tracker', FILTER_VALIDATE_URL) ?: NULL;
    $redirect_location = "video-creatives.php?campaign_id={$campaign_id}";
    $edit_redirect_url = 'video-creatives-edit.php?id=' . $creative_id;

    if (!$creative_id || empty($name) || !$duration || !isset($bid_amount) || !in_array($status, ['active', 'paused'])) {
        redirect_with_message('error', 'Invalid data provided for update.', $edit_redirect_url);
    }
    
    $stmt = $conn->prepare("UPDATE video_creatives SET name = ?, bid_model = ?, bid_amount = ?, landing_url = ?, duration = ?, status = ?, impression_tracker = ? WHERE id = ?");
    $stmt->bind_param("ssdsissi", $name, $bid_model, $bid_amount, $landing_url, $duration, $status, $impression_tracker, $creative_id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Video creative updated successfully.', $redirect_location);
    } else {
        redirect_with_message('error', 'Failed to update video creative: ' . $stmt->error, $redirect_location);
    }
    $stmt->close();
}

// --- AKSI DELETE SATU CREATIVE ---
if (isset($_POST['delete_video_creative'])) {
    $creative_id = filter_input(INPUT_POST, 'creative_id', FILTER_VALIDATE_INT);
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $redirect_location = "video-creatives.php?campaign_id={$campaign_id}";
    
    if (!$creative_id) { redirect_with_message('error', 'Invalid creative ID.', $redirect_location); }
    
    $stmt_get = $conn->prepare("SELECT vast_type, video_url FROM video_creatives WHERE id = ?");
    $stmt_get->bind_param("i", $creative_id);
    $stmt_get->execute();
    $creative = $stmt_get->get_result()->fetch_assoc();
    if ($creative && $creative['vast_type'] === 'upload' && !empty($creative['video_url']) && file_exists(__DIR__ . '/' . $creative['video_url'])) {
        unlink(__DIR__ . '/' . $creative['video_url']);
    }
    $stmt_get->close();

    $stmt_delete = $conn->prepare("DELETE FROM video_creatives WHERE id = ?");
    $stmt_delete->bind_param("i", $creative_id);
    if ($stmt_delete->execute()) {
        redirect_with_message('success', 'Video creative has been deleted.', $redirect_location);
    } else {
        redirect_with_message('error', 'Failed to delete video creative.', $redirect_location);
    }
    $stmt_delete->close();
}

// --- AKSI MASSAL ---
if (isset($_POST['apply_bulk_action'])) {
    $action = $_POST['bulk_action'];
    $creative_ids = $_POST['creative_ids'] ?? [];
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $redirect_location = "video-creatives.php?campaign_id={$campaign_id}";

    if (empty($action) || empty($creative_ids)) {
        redirect_with_message('error', 'No action or no creatives selected.', $redirect_location);
    }

    $sanitized_ids = array_map('intval', $creative_ids);
    $ids_placeholder = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $types = str_repeat('i', count($sanitized_ids));
    $sql = '';

    switch ($action) {
        case 'delete':
            $sql = "DELETE FROM video_creatives WHERE id IN ({$ids_placeholder})";
            break;
        case 'activate':
            $sql = "UPDATE video_creatives SET status = 'active' WHERE id IN ({$ids_placeholder})";
            break;
        case 'pause':
            $sql = "UPDATE video_creatives SET status = 'paused' WHERE id IN ({$ids_placeholder})";
            break;
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$sanitized_ids);
        if ($stmt->execute()) {
            redirect_with_message('success', 'Bulk action completed successfully.', $redirect_location);
        }
    }
    redirect_with_message('error', 'Failed to perform bulk action.', $redirect_location);
}


// --- AKSI UPDATE LANDING PAGE MASSAL ---
if (isset($_POST['update_bulk_landing_url'])) {
    $new_landing_url = filter_input(INPUT_POST, 'new_landing_url', FILTER_VALIDATE_URL);
    $creative_ids = $_POST['creative_ids'] ?? [];
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $redirect_location = "video-creatives.php?campaign_id={$campaign_id}";

    if (!$new_landing_url || empty($creative_ids)) {
        redirect_with_message('error', 'Invalid Landing Page URL or no creatives selected.', $redirect_location);
    }
    
    $sanitized_ids = array_map('intval', $creative_ids);
    $ids_placeholder = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $types = 's' . str_repeat('i', count($sanitized_ids));
    $params = array_merge([$new_landing_url], $sanitized_ids);

    $sql = "UPDATE video_creatives SET landing_url = ? WHERE id IN ({$ids_placeholder}) AND vast_type != 'third_party'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Bulk Landing Page URL update completed successfully for applicable creatives.', $redirect_location);
    } else {
        redirect_with_message('error', 'Failed to perform bulk update: ' . $stmt->error, $redirect_location);
    }
}

// Fallback redirect
header('Location: campaigns.php');
exit();
?>
