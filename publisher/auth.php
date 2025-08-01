<?php
// File: /publisher/auth.php (NEW)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verifikasi user ada, password benar, dan rolenya adalah 'publisher'
    if ($user && password_verify($password, $user['password']) && $user['role'] === 'publisher') {
        // Sukses, atur session khusus publisher
        $_SESSION['publisher_id'] = $user['id'];
        $_SESSION['publisher_username'] = $username;
        
        header('Location: dashboard.php');
        exit();
    } else {
        // Gagal, kembali ke halaman login dengan pesan error
        $_SESSION['login_error'] = "Invalid username, password, or access role.";
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>