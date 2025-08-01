<?php
// File: /register-action.php (NEW - Securely handles registration and referrals)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

function redirect_with_message($type, $message, $location = 'register.php') {
    $_SESSION[$type . '_message'] = $message;
    header("Location: $location");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    
    // Ambil dan sanitasi input
    $role = in_array($_POST['role'], ['advertiser', 'publisher']) ? $_POST['role'] : 'publisher';
    $username = trim($_POST['username']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $ref_code = trim($_POST['ref_code']);
    
    // Validasi
    if (empty($username) || !$email || empty($password)) {
        redirect_with_message('error', 'Please fill all fields correctly.');
    }
    if ($password !== $confirm_password) {
        redirect_with_message('error', 'Passwords do not match.');
    }
    if (strlen($password) < 6) {
        redirect_with_message('error', 'Password must be at least 6 characters long.');
    }

    // Cek duplikasi
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'Username or email is already registered.');
    }
    $stmt_check->close();
    
    // Proses referral
    $referrer_id = null;
    if (!empty($ref_code)) {
        $stmt_ref = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_ref->bind_param("s", $ref_code);
        $stmt_ref->execute();
        $ref_user = $stmt_ref->get_result()->fetch_assoc();
        if ($ref_user) {
            $referrer_id = $ref_user['id'];
        }
        $stmt_ref->close();
    }
    
    // Hash password & atur default
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $default_revenue_share = 70;
    $status = 'active';

    // Masukkan user baru ke database
    $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role, revenue_share, status, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("ssssisi", $username, $email, $password_hash, $role, $default_revenue_share, $status, $referrer_id);
    
    if ($stmt_insert->execute()) {
        redirect_with_message('success', 'Registration successful! You can now log in.', ($role === 'publisher' ? 'publisher/login.php' : 'admin/login.php'));
    } else {
        redirect_with_message('error', 'An error occurred during registration. Please try again.');
    }
    $stmt_insert->close();

} else {
    header('Location: register.php');
    exit();
}
?>