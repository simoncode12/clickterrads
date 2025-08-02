<?php
// File: /admin/settings-action.php (FINAL - Multi Anti-Fraud Toggle)

require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_settings'])) {
    header('Location: settings.php');
    exit();
}

// Helper function
function save_setting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
    $stmt->close();
}

// 1. Simpan pengaturan teks
$text_settings = ['min_withdrawal', 'payment_methods', 'ad_server_domain', 'rtb_handler_domain', 'minimum_bid_floor'];
foreach ($text_settings as $setting_key) {
    if (isset($_POST[$setting_key])) {
        save_setting($conn, $setting_key, trim($_POST[$setting_key]));
    }
}

// 2. Proses upload file
function handle_file_upload($file_key, $setting_key, $conn) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/branding/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        
        $file_name = time() . '_' . basename($_FILES[$file_key]['name']);
        $target_file = $upload_dir . $file_name;
        $db_path = 'uploads/branding/' . $file_name;

        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file)) {
            save_setting($conn, $setting_key, $db_path);
        } else {
            $_SESSION['error_message'] = "Sorry, there was an error uploading your {$file_key}.";
        }
    }
}
handle_file_upload('site_logo', 'site_logo', $conn);
handle_file_upload('site_favicon', 'site_favicon', $conn);

// 3. Simpan setting anti-fraud
if (isset($_POST['block_iframe'])) {
    save_setting($conn, 'block_iframe', '1');
} else {
    save_setting($conn, 'block_iframe', '0');
}
if (isset($_POST['block_bot'])) {
    save_setting($conn, 'block_bot', '1');
} else {
    save_setting($conn, 'block_bot', '0');
}
if (isset($_POST['block_direct_referer'])) {
    save_setting($conn, 'block_direct_referer', '1');
} else {
    save_setting($conn, 'block_direct_referer', '0');
}

// Redirect kembali dengan pesan sukses
$_SESSION['success_message'] = "Settings saved successfully.";
header('Location: settings.php');
exit();
?>