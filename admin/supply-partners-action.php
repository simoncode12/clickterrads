<?php
// File: /admin/supply-partners-action.php (FINAL & CORRECTED)

require_once __DIR__ . '/init.php';

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: supply-partners.php');
    exit();
}

// Helper function untuk redirect dengan pesan
function redirect_with_message($type, $message) {
    $_SESSION[$type . '_message'] = $message;
    header('Location: supply-partners.php');
    exit();
}

// Aksi untuk mengaktifkan seorang Publisher menjadi Supply Partner
if (isset($_POST['activate_supply_partner'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (!$user_id) {
        redirect_with_message('error', 'Invalid User ID.');
    }
    
    // 1. Ambil nama username dari publisher
    $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if (!$user_result) {
        redirect_with_message('error', 'Publisher user not found.');
    }
    $partner_name = $user_result['username'];

    // 2. Generate kunci unik
    $supply_key = bin2hex(random_bytes(16));

    // 3. Masukkan data yang diperlukan
    // PERBAIKAN: Menghapus kolom 'ad_format_id' dari query INSERT
    $stmt = $conn->prepare("INSERT INTO rtb_supply_sources (user_id, name, supply_key, status) VALUES (?, ?, ?, 'active')");
    
    // Tipe data diubah menjadi "iss"
    $stmt->bind_param("iss", $user_id, $partner_name, $supply_key);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Publisher has been activated as an RTB Supply Partner.');
    } else {
        redirect_with_message('error', 'Failed to activate partner. They might already be active.');
    }
    $stmt->close();
}

// Aksi untuk mengubah status Supply Partner
if (isset($_POST['update_supply_status'])) {
    $source_id = filter_input(INPUT_POST, 'source_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['new_status'];

    if (!$source_id || !in_array($new_status, ['active', 'paused'])) {
        redirect_with_message('error', 'Invalid data.');
    }

    $stmt = $conn->prepare("UPDATE rtb_supply_sources SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $source_id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Supply Partner status has been updated.');
    } else {
        redirect_with_message('error', 'Failed to update status: ' . $stmt->error);
    }
    $stmt->close();
}

// AKSI: Mengatur Zona Default
if (isset($_POST['set_default_zone'])) {
    $source_id = filter_input(INPUT_POST, 'source_id', FILTER_VALIDATE_INT);
    $zone_id = filter_input(INPUT_POST, 'zone_id', FILTER_VALIDATE_INT);

    if (!$source_id || !$zone_id) {
        redirect_with_message('error', 'Invalid source or zone ID provided.');
    }

    $stmt = $conn->prepare("UPDATE rtb_supply_sources SET default_zone_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $zone_id, $source_id);
    if ($stmt->execute()) {
        redirect_with_message('success', 'Default zone has been set successfully.');
    } else {
        redirect_with_message('error', 'Failed to set default zone: ' . $stmt->error);
    }
    $stmt->close();
}

// Jika tidak ada aksi yang cocok, kembali ke halaman utama
header('Location: supply-partners.php');
exit();
?>