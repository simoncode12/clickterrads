<?php
// File: /admin/ssp-action.php (FINAL - Includes VAST Endpoint Logic)

require_once __DIR__ . '/init.php';

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ssp.php');
    exit();
}

// Helper function untuk redirect dengan pesan
function redirect_with_message($type, $message) {
    $_SESSION[$type . '_message'] = $message;
    header('Location: ssp.php');
    exit();
}

// Aksi: Tambah Partner Baru
if (isset($_POST['add_partner'])) {
    $name = trim($_POST['name']);
    // Filter URL, jika kosong, simpan sebagai string kosong
    $endpoint_url = filter_input(INPUT_POST, 'endpoint_url', FILTER_VALIDATE_URL) ?: '';
    $vast_endpoint_url = filter_input(INPUT_POST, 'vast_endpoint_url', FILTER_VALIDATE_URL) ?: '';
    
    // Generate kunci unik untuk partner baru
    $partner_key = bin2hex(random_bytes(16));

    // Validasi: Harus ada nama dan minimal satu endpoint
    if (empty($name) || (empty($endpoint_url) && empty($vast_endpoint_url))) {
        redirect_with_message('error', 'Please provide a valid partner name and at least one endpoint URL.');
    }

    $stmt = $conn->prepare("INSERT INTO ssp_partners (name, endpoint_url, vast_endpoint_url, partner_key) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $endpoint_url, $vast_endpoint_url, $partner_key);

    if ($stmt->execute()) {
        redirect_with_message('success', 'SSP Partner added successfully.');
    } else {
        redirect_with_message('error', 'Failed to add partner: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Update Partner
if (isset($_POST['update_partner'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $endpoint_url = filter_input(INPUT_POST, 'endpoint_url', FILTER_VALIDATE_URL) ?: '';
    $vast_endpoint_url = filter_input(INPUT_POST, 'vast_endpoint_url', FILTER_VALIDATE_URL) ?: '';

    // Validasi data update
    if (!$id || empty($name) || (empty($endpoint_url) && empty($vast_endpoint_url))) {
        redirect_with_message('error', 'Invalid data provided for update.');
    }

    $stmt = $conn->prepare("UPDATE ssp_partners SET name = ?, endpoint_url = ?, vast_endpoint_url = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $endpoint_url, $vast_endpoint_url, $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'SSP Partner updated successfully.');
    } else {
        redirect_with_message('error', 'Failed to update partner: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Hapus Partner
if (isset($_POST['delete_partner'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { 
        redirect_with_message('error', 'Invalid ID.'); 
    }
    $stmt = $conn->prepare("DELETE FROM ssp_partners WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { 
        redirect_with_message('success', 'SSP Partner deleted successfully.'); 
    } else { 
        redirect_with_message('error', 'Failed to delete partner.'); 
    }
    $stmt->close();
}

// Fallback redirect jika tidak ada aksi yang cocok
header('Location: ssp.php');
exit();
?>