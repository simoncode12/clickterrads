<?php
// File: /advertising/init.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration and settings
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';

// Current date and time for logging
$current_datetime = '2025-07-24 07:30:41'; // Updated timestamp
$current_user = 'simoncode12'; // Updated username

// Check if user is logged in
if (!isset($_SESSION['advertiser_id'])) {
    if (basename($_SERVER['PHP_SELF']) != 'login.php' && 
        basename($_SERVER['PHP_SELF']) != 'auth.php' && 
        basename($_SERVER['PHP_SELF']) != 'reset-password.php') {
        $_SESSION['login_error'] = "Please log in to access the advertiser portal.";
        header('Location: ../advertiser/login.php');
        exit();
    }
}

// Define constant to prevent direct access to templates
define('IN_APP', true);

// Log user activity
function log_activity($action, $details = '') {
    global $conn, $current_datetime;
    
    if (isset($_SESSION['advertiser_id'])) {
        $advertiser_id = $_SESSION['advertiser_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Check if table exists, create if not
        $table_check = $conn->query("SHOW TABLES LIKE 'advertiser_activity_log'");
        if ($table_check->num_rows == 0) {
            $create_table_sql = "
            CREATE TABLE `advertiser_activity_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `advertiser_id` int(11) NOT NULL,
              `action` varchar(100) NOT NULL,
              `details` text DEFAULT NULL,
              `ip_address` varchar(45) DEFAULT NULL,
              `user_agent` varchar(255) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `advertiser_id` (`advertiser_id`),
              KEY `action` (`action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
            ";
            
            $conn->query($create_table_sql);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO advertiser_activity_log 
            (advertiser_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("isssss", 
            $advertiser_id, 
            $action, 
            $details, 
            $ip_address, 
            $user_agent, 
            $current_datetime
        );
        
        $stmt->execute();
        $stmt->close();
    }
}

// Helper function to format money values
function format_money($amount, $decimals = 2) {
    return '$' . number_format($amount, $decimals);
}

// REMOVED: get_query_results() function since it's already defined in database.php

// Ensure advertisers table exists
function ensure_advertisers_table_exists($conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'advertisers'");
    if ($table_check->num_rows == 0) {
        $create_table_sql = "
        CREATE TABLE `advertisers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `company_name` varchar(255) NOT NULL,
          `contact_name` varchar(255) DEFAULT NULL,
          `phone` varchar(50) DEFAULT NULL,
          `address` text DEFAULT NULL,
          `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
          `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
        ";
        
        $conn->query($create_table_sql);
    }
}

// Ensure advertisers table exists
ensure_advertisers_table_exists($conn);
?>