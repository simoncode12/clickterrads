<?php
// File: /publisher/account-action.php (NEW)
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php');
    exit();
}

$user_id = $_SESSION['publisher_id'];

// Update Profile
if (isset($_POST['update_profile'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (!$email) {
        $_SESSION['message'] = 'Invalid email address.';
        $_SESSION['message_type'] = 'danger';
        header('Location: account.php'); exit();
    }
    
    // Update password jika diisi dan cocok
    if (!empty($password)) {
        if ($password !== $password_confirm) {
            $_SESSION['message'] = 'Passwords do not match.';
            $_SESSION['message_type'] = 'danger';
            header('Location: account.php'); exit();
        }
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $password_hash, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Profile updated successfully.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating profile.';
        $_SESSION['message_type'] = 'danger';
    }
    $stmt->close();
}

// Update Payment Settings
if (isset($_POST['update_payment'])) {
    $payout_method = $_POST['payout_method'];
    $payout_details = trim($_POST['payout_details']);

    $stmt = $conn->prepare("UPDATE users SET payout_method = ?, payout_details = ? WHERE id = ?");
    $stmt->bind_param("ssi", $payout_method, $payout_details, $user_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Payment settings updated successfully.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating payment settings.';
        $_SESSION['message_type'] = 'danger';
    }
    $stmt->close();
}

header('Location: account.php');
exit();
?>