<?php
// File: /admin/ron-creative-action.php (UPDATED - Full Patch)

require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: campaigns.php');
    exit();
}

$campaign_id_redirect = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
$redirect_url = 'ron-creative.php' . ($campaign_id_redirect ? '?campaign_id=' . $campaign_id_redirect : '');

function redirect_with_message($type, $message, $location) {
    $_SESSION[$type . '_message'] = $message;
    header("Location: $location");
    exit();
}

// --- AKSI MASSAL (Bulk Action) ---
if (isset($_POST['apply_bulk_action'])) {
    $action = $_POST['bulk_action'];
    $creative_ids = $_POST['creative_ids'] ?? [];

    if (empty($action) || empty($creative_ids)) {
        redirect_with_message('error', 'No action or no creatives selected.', $redirect_url);
    }

    $sanitized_ids = array_map('intval', $creative_ids);
    if (empty($sanitized_ids)) {
        redirect_with_message('error', 'Invalid creative IDs provided.', $redirect_url);
    }
    
    $ids_placeholder = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $types = str_repeat('i', count($sanitized_ids));
    $sql = '';

    switch ($action) {
        case 'delete':
            // Hapus file image jika perlu
            $select_stmt = $conn->prepare("SELECT image_url FROM creatives WHERE creative_type = 'image' AND id IN ({$ids_placeholder})");
            $select_stmt->bind_param($types, ...$sanitized_ids);
            $select_stmt->execute();
            $results = $select_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($results as $row) {
                if (!empty($row['image_url']) && strpos($row['image_url'], 'uploads/') === 0 && file_exists(__DIR__ . '/' . $row['image_url'])) {
                    unlink(__DIR__ . '/' . $row['image_url']);
                }
            }
            $select_stmt->close();
            
            $sql = "DELETE FROM creatives WHERE id IN ({$ids_placeholder})";
            break;
        case 'activate':
            $sql = "UPDATE creatives SET status = 'active' WHERE id IN ({$ids_placeholder})";
            break;
        case 'pause':
            $sql = "UPDATE creatives SET status = 'paused' WHERE id IN ({$ids_placeholder})";
            break;
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$sanitized_ids);
        if ($stmt->execute()) {
            redirect_with_message('success', 'Bulk action completed successfully.', $redirect_url);
        }
    }
    redirect_with_message('error', 'Failed to perform bulk action.', $redirect_url);
}

// --- AKSI UPDATE BID MASSAL ---
if (isset($_POST['update_bulk_bids'])) {
    $new_bid = filter_input(INPUT_POST, 'new_bid_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $creative_ids = $_POST['creative_ids'] ?? [];

    if ($new_bid === false || empty($creative_ids)) {
        redirect_with_message('error', 'Invalid bid amount or no creatives selected.', $redirect_url);
    }

    $sanitized_ids = array_map('intval', $creative_ids);
    $ids_placeholder = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $types = 'd' . str_repeat('i', count($sanitized_ids));
    $params = array_merge([$new_bid], $sanitized_ids);

    $sql = "UPDATE creatives SET bid_amount = ? WHERE id IN ({$ids_placeholder})";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Bulk bid update completed successfully.', $redirect_url);
    } else {
        redirect_with_message('error', 'Failed to perform bulk bid update: ' . $stmt->error, $redirect_url);
    }
}

// --- AKSI TAMBAH CREATIVE BARU ---
if (isset($_POST['add_creative'])) {
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $bid_model = $_POST['bid_model'];
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $creative_type = $_POST['creative_type'];

    if (empty($name) || !isset($bid_amount) || !$campaign_id) { 
        redirect_with_message('error', 'Please fill all creative details correctly.', $redirect_url); 
    }
    
    $image_url_db = null; $landing_url_db = null; $sizes_db = null; $script_content_db = null;

    // Periksa apakah ini adalah kampanye popunder
    $is_popunder = false;
    $stmt_format = $conn->prepare("SELECT af.name FROM campaigns c JOIN ad_formats af ON c.ad_format_id = af.id WHERE c.id = ?");
    $stmt_format->bind_param("i", $campaign_id);
    $stmt_format->execute();
    $format_result = $stmt_format->get_result()->fetch_assoc();
    $is_popunder = (strtolower($format_result['name'] ?? '') === 'popunder');
    $stmt_format->close();

    if ($is_popunder) {
        $creative_type = 'popunder';
        $landing_url_db = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
        $sizes_db = 'all';

        if (!$landing_url_db) {
            redirect_with_message('error', 'A valid landing page URL is required for popunder creatives.', $redirect_url);
        }

    } elseif ($creative_type === 'image') {
        $landing_url_db = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
        $sizes_db = $_POST['sizes'];
        if (!$landing_url_db) {
            redirect_with_message('error', 'A valid landing page URL is required for image creatives.', $redirect_url);
        }

        if (isset($_FILES['creative_file']) && $_FILES['creative_file']['error'] == 0) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $file_name = "creative_" . $campaign_id . "_" . time() . '_' . basename($_FILES['creative_file']['name']);
            if (move_uploaded_file($_FILES['creative_file']['tmp_name'], $upload_dir . $file_name)) { 
                $image_url_db = 'uploads/' . $file_name; 
            } else { 
                redirect_with_message('error', 'Failed to upload file.', $redirect_url); 
            }
        } else { 
            $image_url_db = filter_input(INPUT_POST, 'image_url', FILTER_VALIDATE_URL); 
        }
        if (empty($image_url_db)) {
            redirect_with_message('error', 'Please provide an image by uploading or using a hotlink.', $redirect_url);
        }

    } elseif ($creative_type === 'script' || $creative_type === 'html') {
        $script_content_db = $_POST['script_content'];
        $sizes_db = $_POST['sizes'];
        $landing_url_db = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
        if (empty($script_content_db)) {
            redirect_with_message('error', 'Script content cannot be empty.', $redirect_url);
        }
    }

    $stmt = $conn->prepare("INSERT INTO creatives (campaign_id, name, creative_type, bid_model, bid_amount, image_url, landing_url, script_content, sizes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdssss", $campaign_id, $name, $creative_type, $bid_model, $bid_amount, $image_url_db, $landing_url_db, $script_content_db, $sizes_db);
    
    if ($stmt->execute()) { 
        redirect_with_message('success', 'Creative "' . htmlspecialchars($name) . '" was created.', $redirect_url); 
    } else { 
        redirect_with_message('error', 'Database error: ' . $stmt->error, $redirect_url); 
    }
    $stmt->close();
}

// --- AKSI UPDATE SATU CREATIVE DARI HALAMAN EDIT ---
if (isset($_POST['update_creative'])) {
    $creative_id = filter_input(INPUT_POST, 'creative_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $bid_model = $_POST['bid_model'];
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $creative_type = $_POST['creative_type'];
    $edit_redirect_url = 'ron-creative-edit.php?id=' . $creative_id;
    
    if (!$creative_id || empty($name) || !isset($bid_amount)) { 
        redirect_with_message('error', 'Invalid data for update.', $edit_redirect_url); 
    }

    // Periksa apakah ini creative untuk kampanye popunder
    $is_popunder = false;
    $stmt_format = $conn->prepare("
        SELECT af.name 
        FROM creatives cr 
        JOIN campaigns c ON cr.campaign_id = c.id 
        JOIN ad_formats af ON c.ad_format_id = af.id 
        WHERE cr.id = ?
    ");
    $stmt_format->bind_param("i", $creative_id);
    $stmt_format->execute();
    $format_result = $stmt_format->get_result()->fetch_assoc();
    $is_popunder = (strtolower($format_result['name'] ?? '') === 'popunder');
    $stmt_format->close();
    
    if ($is_popunder || $creative_type === 'popunder') {
        $landing_url = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
        if (!$landing_url) {
            redirect_with_message('error', 'A valid landing page URL is required for popunder creatives.', $edit_redirect_url);
        }

        $stmt = $conn->prepare("UPDATE creatives SET name=?, bid_model=?, bid_amount=?, landing_url=? WHERE id=?");
        $stmt->bind_param("ssdsi", $name, $bid_model, $bid_amount, $landing_url, $creative_id);

    } elseif ($creative_type === 'image') {
        $landing_url = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
        $sizes = $_POST['sizes'];
        $image_url = filter_input(INPUT_POST, 'image_url', FILTER_VALIDATE_URL) ?: '';

        if (isset($_FILES['creative_file']) && $_FILES['creative_file']['error'] == 0) {
            $stmt_get = $conn->prepare("SELECT image_url FROM creatives WHERE id = ?");
            $stmt_get->bind_param("i", $creative_id); $stmt_get->execute();
            $old_creative = $stmt_get->get_result()->fetch_assoc();
            if ($old_creative && strpos($old_creative['image_url'], 'uploads/') === 0 && file_exists(__DIR__ . '/' . $old_creative['image_url'])) {
                unlink(__DIR__ . '/' . $old_creative['image_url']);
            }
            $stmt_get->close();

            $upload_dir = __DIR__ . '/uploads/';
            $file_name = "creative_" . $creative_id . "_" . time() . '_' . basename($_FILES['creative_file']['name']);
            if (move_uploaded_file($_FILES['creative_file']['tmp_name'], $upload_dir . $file_name)) {
                $image_url = 'uploads/' . $file_name;
            } else {
                redirect_with_message('error', 'Failed to upload new file.', $edit_redirect_url);
            }
        }
        
        if (empty($image_url)) redirect_with_message('error', 'Image source cannot be empty.', $edit_redirect_url);

        $stmt = $conn->prepare("UPDATE creatives SET name=?, bid_model=?, bid_amount=?, image_url=?, landing_url=?, sizes=? WHERE id=?");
        $stmt->bind_param("ssdsssi", $name, $bid_model, $bid_amount, $image_url, $landing_url, $sizes, $creative_id);

    } elseif ($creative_type === 'script' || $creative_type === 'html') {
        $script_content = $_POST['script_content'];
        $sizes = $_POST['sizes'];
        $landing_url = filter_input(INPUT_POST, 'landing_url', FILTER_VALIDATE_URL);
        $stmt = $conn->prepare("UPDATE creatives SET name=?, bid_model=?, bid_amount=?, script_content=?, sizes=?, landing_url=? WHERE id=?");
        $stmt->bind_param("ssdsssi", $name, $bid_model, $bid_amount, $script_content, $sizes, $landing_url, $creative_id);

    } else {
        redirect_with_message('error', 'Unknown creative type.', $edit_redirect_url);
    }
    
    if ($stmt->execute()) { 
        redirect_with_message('success', 'Creative updated successfully.', $redirect_url); 
    } else { 
        redirect_with_message('error', 'Failed to update creative: ' . $stmt->error, $edit_redirect_url); 
    }
    $stmt->close();
}

// Fallback redirect
header('Location: ' . $redirect_url);
exit();
?>

