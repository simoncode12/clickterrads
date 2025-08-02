<?php
// File: /admin/user-action.php (NEW & COMPLETE)

require_once __DIR__ . '/init.php';

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user.php');
    exit();
}

// Helper function untuk menampilkan pesan dan redirect
function redirect_with_message($type, $message) {
    $_SESSION[$type . '_message'] = $message;
    header('Location: user.php');
    exit();
}

// Aksi: Tambah Pengguna Baru
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $revenue_share = ($role === 'publisher') ? filter_input(INPUT_POST, 'revenue_share', FILTER_VALIDATE_INT, ["options" => ["default" => 0]]) : 0;
    
    if (empty($username) || !$email || empty($password) || !in_array($role, ['admin', 'advertiser', 'publisher'])) {
        redirect_with_message('error', 'Please fill all required fields correctly.');
    }

    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'Username or email already exists.');
    }
    $stmt_check->close();
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, revenue_share, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssi", $username, $email, $password_hash, $role, $revenue_share);
    
    if ($stmt->execute()) {
        redirect_with_message('success', 'User ' . htmlspecialchars($username) . ' created successfully.');
    } else {
        redirect_with_message('error', 'Failed to create user: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Update Pengguna
if (isset($_POST['update_user'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $username = trim($_POST['username']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password']; // Bisa kosong
    $role = $_POST['role'];
    $status = $_POST['status'];
    $revenue_share = ($role === 'publisher') ? filter_input(INPUT_POST, 'revenue_share', FILTER_VALIDATE_INT, ["options" => ["default" => 0]]) : 0;

    if (!$id || empty($username) || !$email || !in_array($role, ['admin', 'advertiser', 'publisher']) || !in_array($status, ['active', 'inactive'])) {
        redirect_with_message('error', 'Invalid data provided for update.');
    }

    // Cek duplikasi username/email (untuk pengguna lain)
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt_check->bind_param("ssi", $username, $email, $id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        redirect_with_message('error', 'Username or email already in use by another account.');
    }
    $stmt_check->close();

    // Jika password diisi, update password. Jika tidak, jangan ubah password.
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ?, status = ?, revenue_share = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $username, $email, $password_hash, $role, $status, $revenue_share, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ?, revenue_share = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $username, $email, $role, $status, $revenue_share, $id);
    }

    if ($stmt->execute()) {
        redirect_with_message('success', 'User ' . htmlspecialchars($username) . ' updated successfully.');
    } else {
        redirect_with_message('error', 'Failed to update user: ' . $stmt->error);
    }
    $stmt->close();
}

// Aksi: Hapus Pengguna
if (isset($_POST['delete_user'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        redirect_with_message('error', 'Invalid user ID.');
    }

    // Proteksi agar tidak bisa menghapus diri sendiri atau user admin utama (ID 1)
    if ($id == $_SESSION['user_id']) {
        redirect_with_message('error', 'You cannot delete your own account.');
    }
    if ($id == 1) {
        redirect_with_message('error', 'The main administrator account (ID 1) cannot be deleted.');
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        redirect_with_message('success', 'User deleted successfully.');
    } else {
        redirect_with_message('error', 'Failed to delete user. They may have associated campaigns or sites.');
    }
    $stmt->close();
}

// Jika tidak ada aksi yang cocok
header('Location: user.php');
exit();