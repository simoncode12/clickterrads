<?php
// File: /admin/site-action.php (NEW)

require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: site.php');
    exit();
}

function redirect_with_message($type, $message) {
    $_SESSION[$type . '_message'] = $message;
    header('Location: site.php');
    exit();
}

// Aksi: Tambah Situs Baru
if (isset($_POST['add_site'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $url = filter_input(INPUT_POST, 'url', FILTER_VALIDATE_URL);

    if (!$user_id || !$category_id || !$url) {
        redirect_with_message('error', 'Please fill all fields correctly. The URL must be valid.');
    }

    // Cek duplikasi URL
    $stmt_check = $conn->prepare("SELECT id FROM sites WHERE url = ?");
    $stmt_check->bind_param("s", $url);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'A site with this URL already exists.');
    }
    $stmt_check->close();

    $stmt = $conn->prepare("INSERT INTO sites (user_id, category_id, url, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iis", $user_id, $category_id, $url);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Site ' . htmlspecialchars($url) . ' added successfully and is pending approval.');
    } else {
        redirect_with_message('error', 'Failed to add site: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Update Situs (termasuk status)
if (isset($_POST['update_site'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $url = filter_input(INPUT_POST, 'url', FILTER_VALIDATE_URL);
    $status = $_POST['status'];
    
    if (!$id || !$url || !in_array($status, ['pending', 'approved', 'rejected'])) {
        redirect_with_message('error', 'Invalid data provided for update.');
    }

    // Cek duplikasi URL untuk situs lain
    $stmt_check = $conn->prepare("SELECT id FROM sites WHERE url = ? AND id != ?");
    $stmt_check->bind_param("si", $url, $id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'Another site with this URL already exists.');
    }
    $stmt_check->close();

    $stmt = $conn->prepare("UPDATE sites SET url = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $url, $status, $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Site ' . htmlspecialchars($url) . ' updated successfully.');
    } else {
        redirect_with_message('error', 'Failed to update site: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Hapus Situs
if (isset($_POST['delete_site'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        redirect_with_message('error', 'Invalid site ID.');
    }

    // Constraint ON DELETE CASCADE di database akan menghapus zona terkait
    $stmt = $conn->prepare("DELETE FROM sites WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'Site and all its associated zones have been deleted.');
    } else {
        redirect_with_message('error', 'Failed to delete site: ' . $stmt->error);
    }
    $stmt->close();
}

header('Location: site.php');
exit();
?>