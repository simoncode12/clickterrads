<?php
// File: /admin/zone-action.php (FINAL - Handles ad_format_id and conditional size)

require_once __DIR__ . '/init.php';

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: zone.php');
    exit();
}

// Helper function untuk redirect dengan pesan
function redirect_with_message($type, $message) {
    $_SESSION[$type . '_message'] = $message;
    header('Location: zone.php');
    exit();
}

// Aksi: Tambah Zona Baru
if (isset($_POST['add_zone'])) {
    $site_id = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);
    $ad_format_id = filter_input(INPUT_POST, 'ad_format_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $size = trim($_POST['size']);

    // Ambil nama format dari database untuk menerapkan logika kondisional
    $format_name = '';
    if ($ad_format_id) {
        $format_stmt = $conn->prepare("SELECT name FROM ad_formats WHERE id = ?");
        $format_stmt->bind_param("i", $ad_format_id);
        $format_stmt->execute();
        $format_result = $format_stmt->get_result()->fetch_assoc();
        $format_name = strtolower($format_result['name'] ?? '');
        $format_stmt->close();
    }
    
    // Jika formatnya video atau popunder, 'size' tidak wajib dan kita beri nilai default 'responsive'
    if ($format_name === 'video' || $format_name === 'popunder') {
        if (empty($size)) {
            $size = 'responsive'; // Memberikan nilai default
        }
    }

    // Validasi akhir setelah menerapkan logika kondisional
    if (!$site_id || !$ad_format_id || empty($name) || empty($size)) {
        redirect_with_message('error', 'Please fill all required fields correctly.');
    }

    $stmt = $conn->prepare("INSERT INTO zones (site_id, ad_format_id, name, size) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $site_id, $ad_format_id, $name, $size);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Zone "' . htmlspecialchars($name) . '" added successfully.');
    } else {
        redirect_with_message('error', 'Failed to add zone: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Update Zona (Kerangka untuk pengembangan di masa depan)
if (isset($_POST['update_zone'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $ad_format_id = filter_input(INPUT_POST, 'ad_format_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $size = trim($_POST['size']);
    
    // Anda perlu menambahkan logika validasi kondisional yang sama di sini jika membuat fitur edit
    if (!$id || !$ad_format_id || empty($name) || empty($size)) {
        redirect_with_message('error', 'Invalid data provided for update.');
    }

    $stmt = $conn->prepare("UPDATE zones SET ad_format_id = ?, name = ?, size = ? WHERE id = ?");
    $stmt->bind_param("issi", $ad_format_id, $name, $size, $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Zone "' . htmlspecialchars($name) . '" updated successfully.');
    } else {
        redirect_with_message('error', 'Failed to update zone: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Hapus Zona
if (isset($_POST['delete_zone'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        redirect_with_message('error', 'Invalid zone ID.');
    }

    $stmt = $conn->prepare("DELETE FROM zones WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Zone deleted successfully.');
    } else {
        redirect_with_message('error', 'Failed to delete zone: ' . $stmt->error);
    }
    $stmt->close();
}

// Fallback redirect jika tidak ada aksi yang cocok
header('Location: zone.php');
exit();
?>