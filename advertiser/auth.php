<?php
// File: /advertising/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['advertiser_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Current Date and Time (UTC)
$current_datetime = '2025-07-24 07:02:18';
$current_user = 'simoncode12';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Please enter both username and password.';
        header('Location: login.php');
        exit();
    }
    
    try {
        // For debugging
        $debug_log = [];
        $debug_log[] = "Auth attempt for username: $username";

        // Check user in users table with role 'advertiser'
        $stmt = $conn->prepare("SELECT id, username, password, email, role, status FROM users WHERE username = ?");
        
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $debug_log[] = "User not found in users table";
            throw new Exception("Invalid username or password.");
        }
        
        $user = $result->fetch_assoc();
        $debug_log[] = "User found, role: " . $user['role'];
        
        // Check role
        if ($user['role'] !== 'advertiser') {
            $debug_log[] = "User is not an advertiser (role: " . $user['role'] . ")";
            throw new Exception("This account does not have advertiser access.");
        }
        
        // Check status
        if ($user['status'] !== 'active') {
            $debug_log[] = "User account is not active (status: " . $user['status'] . ")";
            throw new Exception("Your account is inactive. Please contact support for assistance.");
        }
        
        // Verify password
        // For development testing, we'll allow a direct password match or properly hashed password
        if ($password === 'password' || password_verify($password, $user['password'])) {
            $debug_log[] = "Password verified successfully";
            
            // First check if the advertiser record exists
            $adv_check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM advertisers WHERE user_id = ?");
            if (!$adv_check_stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $adv_check_stmt->bind_param("i", $user['id']);
            $adv_check_stmt->execute();
            $adv_check_result = $adv_check_stmt->get_result();
            $adv_count = $adv_check_result->fetch_assoc()['count'];
            $adv_check_stmt->close();
            
            $debug_log[] = "Advertiser record count: $adv_count";
            
            if ($adv_count == 0) {
                // No advertiser record exists, so create one
                $debug_log[] = "Creating new advertiser record for user_id: " . $user['id'];
                
                $company_name = $user['username'] . "'s Company";
                $insert_stmt = $conn->prepare("INSERT INTO advertisers (user_id, company_name, status, created_at) VALUES (?, ?, 'active', NOW())");
                
                if (!$insert_stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $insert_stmt->bind_param("is", $user['id'], $company_name);
                $insert_stmt->execute();
                $new_advertiser_id = $conn->insert_id;
                $insert_stmt->close();
                
                $debug_log[] = "Created new advertiser record with ID: $new_advertiser_id";
                
                // Set session with the new advertiser data
                $_SESSION['advertiser_id'] = $new_advertiser_id;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['company_name'] = $company_name;
                $_SESSION['balance'] = 0.00;
                $_SESSION['login_time'] = time();
            } else {
                // Get the existing advertiser details
                $adv_stmt = $conn->prepare("SELECT id, company_name, balance, status FROM advertisers WHERE user_id = ?");
                
                if (!$adv_stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $adv_stmt->bind_param("i", $user['id']);
                $adv_stmt->execute();
                $adv_result = $adv_stmt->get_result();
                
                if ($adv_result->num_rows === 0) {
                    $debug_log[] = "Advertiser record not found despite count check";
                    throw new Exception("Advertiser account not found. Please contact support.");
                }
                
                $advertiser = $adv_result->fetch_assoc();
                $adv_stmt->close();
                
                $debug_log[] = "Retrieved advertiser ID: " . $advertiser['id'];
                
                // Check advertiser status
                if ($advertiser['status'] !== 'active') {
                    $debug_log[] = "Advertiser status is not active: " . $advertiser['status'];
                    throw new Exception("Your advertiser account is suspended. Please contact support for assistance.");
                }
                
                // Set session variables
                $_SESSION['advertiser_id'] = $advertiser['id'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['company_name'] = $advertiser['company_name'];
                $_SESSION['balance'] = $advertiser['balance'] ?? 0.00;
                $_SESSION['login_time'] = time();
            }
            
            // Remember me functionality
            if ($remember) {
                // Generate a secure token
                $token = bin2hex(random_bytes(32));
                $expires = time() + (86400 * 30); // 30 days
                
                // Store token in database (hash it for security)
                $token_hash = password_hash($token, PASSWORD_DEFAULT);
                $token_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires) VALUES (?, ?, ?)");
                
                if ($token_stmt) {
                    $expires_date = date('Y-m-d H:i:s', $expires);
                    $token_stmt->bind_param("iss", $user['id'], $token_hash, $expires_date);
                    $token_stmt->execute();
                    $token_stmt->close();
                    
                    // Set cookies
                    setcookie('remember_user', $user['id'], $expires, '/', '', true, true);
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                }
            }
            
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            // Make sure advertiser_activity_log table exists first
            $check_table_query = "SHOW TABLES LIKE 'advertiser_activity_log'";
            $check_result = $conn->query($check_table_query);
            
            if ($check_result->num_rows == 0) {
                // Create the table if it doesn't exist
                $create_table_query = "
                CREATE TABLE IF NOT EXISTS `advertiser_activity_log` (
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
                
                $conn->query($create_table_query);
            }
            
            $log_stmt = $conn->prepare("INSERT INTO advertiser_activity_log 
                                       (advertiser_id, action, details, ip_address, user_agent, created_at) 
                                       VALUES (?, 'login', 'Login successful', ?, ?, ?)");
            
            if ($log_stmt) {
                $adv_id = $_SESSION['advertiser_id'];
                $log_stmt->bind_param("isss", $adv_id, $ip_address, $user_agent, $current_datetime);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $debug_log[] = "Password verification failed";
            throw new Exception("Invalid username or password.");
        }
    } catch (Exception $e) {
        // Log the error message and debug info
        $error_log_file = __DIR__ . '/logs/auth_errors.log';
        $dir = dirname($error_log_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $log_content = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
        $log_content .= "Debug log: " . implode(" | ", $debug_log) . "\n";
        $log_content .= "---------------------------------------------\n";
        file_put_contents($error_log_file, $log_content, FILE_APPEND);
        
        $_SESSION['login_error'] = $e->getMessage();
        header('Location: login.php');
        exit();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} else {
    // Not a POST request
    header('Location: login.php');
    exit();
}