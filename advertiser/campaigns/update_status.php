<?php
// File: /advertising/campaigns/update_status.php
// Ensure no whitespace or output before this opening PHP tag

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define IN_APP constant to prevent direct access to included files
define('IN_APP', true);

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../init.php';

// Current Date and Time (UTC)
$current_datetime = '2025-07-24 10:14:57';
$current_user = 'simoncode12';

// Check if user is logged in
if (!isset($_SESSION['advertiser_id'])) {
    $_SESSION['login_error'] = "Please log in to access the advertiser portal.";
    header('Location: ../login.php');
    exit();
}

// Get advertiser info
$advertiser_id = $_SESSION['advertiser_id'];

// For testing purposes, we'll use advertiser_id = 2
$query_advertiser_id = 2;

try {
    // Accept both POST and GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get parameters from either POST or GET
        $campaign_id = isset($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : 
                      (isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0);
                      
        $new_status = isset($_POST['status']) ? $_POST['status'] : 
                     (isset($_GET['status']) ? $_GET['status'] : '');
        
        // Check for required parameters
        if ($campaign_id === 0 || empty($new_status)) {
            throw new Exception("Missing required parameters");
        }

        // Validate the status parameter
        $valid_statuses = ['active', 'paused', 'completed'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception("Invalid status value: " . $new_status);
        }

        // Check if the campaign exists
        $check_stmt = $conn->prepare("SELECT id, status FROM campaigns WHERE id = ?");
        if (!$check_stmt) {
            throw new Exception("Database error preparing statement: " . $conn->error);
        }
        
        $check_stmt->bind_param('i', $campaign_id);
        if (!$check_stmt->execute()) {
            throw new Exception("Database error executing statement: " . $check_stmt->error);
        }
        
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Campaign not found with ID: " . $campaign_id);
        }
        
        // Get the current status
        $campaign_data = $result->fetch_assoc();
        $old_status = $campaign_data['status'];
        $check_stmt->close();
        
        // If the status hasn't changed, no need to update
        if ($old_status === $new_status) {
            $_SESSION['success'] = "Campaign status is already " . ucfirst($new_status) . ".";
            header('Location: list.php');
            exit;
        }

        // Update the campaign status
        $update_stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Database error preparing update statement: " . $conn->error);
        }
        
        $update_stmt->bind_param('si', $new_status, $campaign_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Database error executing update: " . $update_stmt->error);
        }
        $update_stmt->close();

        // Success message and redirect
        $status_action = $new_status === 'active' ? 'activated' : ($new_status === 'paused' ? 'paused' : 'completed');
        $_SESSION['success'] = "Campaign has been successfully " . $status_action . ".";
        header('Location: list.php');
        exit;
    } else {
        throw new Exception("Invalid request method. Please use POST or GET.");
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: list.php');
    exit;
}
?>