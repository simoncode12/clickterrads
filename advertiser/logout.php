<?php
// File: /advertising/logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';

// Current Date and Time (UTC)
$current_datetime = '2025-07-24 07:05:47';
$current_user = 'simoncode12';

// Log the logout action if user is authenticated
if (isset($_SESSION['advertiser_id'])) {
    $advertiser_id = $_SESSION['advertiser_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Record logout activity
    $log_stmt = $conn->prepare("INSERT INTO advertiser_activity_log 
                               (advertiser_id, action, details, ip_address, user_agent, created_at) 
                               VALUES (?, 'logout', 'User logged out', ?, ?, ?)");
    
    if ($log_stmt) {
        $log_stmt->bind_param("isss", $advertiser_id, $ip_address, $user_agent, $current_datetime);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    // Delete any remember-me tokens if they exist
    if (isset($_COOKIE['remember_user']) && isset($_COOKIE['remember_token'])) {
        $user_id = $_SESSION['user_id'] ?? $_COOKIE['remember_user'];
        
        // Delete token from database
        $token_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        if ($token_stmt) {
            $token_stmt->bind_param("i", $user_id);
            $token_stmt->execute();
            $token_stmt->close();
        }
        
        // Delete cookies by setting expiration in the past
        setcookie('remember_user', '', time() - 3600, '/', '', true, true);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set a success message for the login page
session_start();
$_SESSION['success_message'] = 'You have been successfully logged out.';

// Redirect to login page
header('Location: login.php');
exit();
?>