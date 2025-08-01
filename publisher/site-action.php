<?php
// File: /publisher/site-action.php (NEW)

require_once __DIR__ . '/init.php'; // init.php akan memastikan hanya publisher yang login bisa akses

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sites.php');
    exit();
}

function redirect_with_message($type, $message) {
    $_SESSION[$type . '_message'] = $message;
    header('Location: sites.php');
    exit();
}

// Aksi: Publisher menambah situs baru
if (isset($_POST['add_site'])) {
    $publisher_id = $_SESSION['publisher_id'];
    $url = filter_input(INPUT_POST, 'url', FILTER_VALIDATE_URL);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    if (!$url || !$category_id) {
        redirect_with_message('error', 'Please provide a valid URL and select a category.');
    }
    
    // Cek apakah URL sudah pernah didaftarkan oleh publisher lain
    $stmt_check = $conn->prepare("SELECT id FROM sites WHERE url = ?");
    $stmt_check->bind_param("s", $url);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'This site URL has already been registered in our network.');
    }
    $stmt_check->close();

    // Simpan situs baru dengan status 'pending' untuk direview oleh admin
    $stmt_insert = $conn->prepare("INSERT INTO sites (user_id, category_id, url, status) VALUES (?, ?, ?, 'pending')");
    $stmt_insert->bind_param("iis", $publisher_id, $category_id, $url);

    if ($stmt_insert->execute()) {
        redirect_with_message('success', 'Your site has been submitted successfully and is awaiting approval.');
    } else {
        redirect_with_message('error', 'An error occurred while submitting your site.');
    }
    $stmt_insert->close();
}
?>