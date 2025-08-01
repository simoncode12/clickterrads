<?php
// File: /publisher/signup-action.php (NEW - Securely handles publisher registration)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Fungsi untuk redirect dengan pesan
function redirect_with_message($type, $message, $location = 'signup.php') {
    $_SESSION[$type . '_message'] = $message;
    header("Location: $location");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    
    // Ambil dan sanitasi input
    $username = trim($_POST['username']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi dasar
    if (empty($username) || !$email || empty($password)) {
        redirect_with_message('error', 'Please fill all fields correctly.');
    }
    if ($password !== $confirm_password) {
        redirect_with_message('error', 'Passwords do not match.');
    }
    if (strlen($password) < 6) {
        redirect_with_message('error', 'Password must be at least 6 characters long.');
    }

    // Cek duplikasi username atau email
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'Username or email is already registered.');
    }
    $stmt_check->close();
    
    // Hash password untuk keamanan
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Atur role dan revenue share default
    $role = 'publisher';
    $default_revenue_share = 70; // Anda bisa mengubah nilai default ini
    $status = 'active'; // Atau 'inactive' jika Anda ingin review manual

    // Masukkan user baru ke database
    $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role, revenue_share, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("ssssis", $username, $email, $password_hash, $role, $default_revenue_share, $status);
    
    if ($stmt_insert->execute()) {
        redirect_with_message('success', 'Registration successful! You can now log in.', 'login.php');
    } else {
        redirect_with_message('error', 'An error occurred during registration. Please try again.');
    }
    $stmt_insert->close();

} else {
    // Jika bukan request POST, kembali ke halaman signup
    header('Location: signup.php');
    exit();
}
?>