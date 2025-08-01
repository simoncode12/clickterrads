<?php
// File: /advertiser/campaigns/delete.php

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define IN_APP constant to avoid direct access checks in included files
define('IN_APP', true);

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../init.php';

// Current Date and Time (UTC)
$current_datetime = '2025-07-24 10:09:06';
$current_user = 'simoncode12';

// Check if user is logged in
if (!isset($_SESSION['advertiser_id'])) {
    $_SESSION['login_error'] = "Please log in to access the advertiser portal.";
    header('Location: ../login.php');
    exit();
}

// Get advertiser info
$advertiser_id = $_SESSION['advertiser_id'];
$username = $_SESSION['username'];

// For testing purposes, we'll use advertiser_id = 2, which matches the campaigns in the SQL dump
// In a production environment, you would use $advertiser_id from the session
$query_advertiser_id = 2;

// Process the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for required parameters
    if (!isset($_POST['campaign_id'])) {
        $_SESSION['error'] = "Missing campaign_id parameter";
        header('Location: list.php');
        exit();
    }

    // Get and validate parameters
    $campaign_id = (int)$_POST['campaign_id'];

    try {
        // First, check if the campaign exists
        $check_stmt = $conn->prepare("SELECT id, name FROM campaigns WHERE id = ?");
        if (!$check_stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $check_stmt->bind_param('i', $campaign_id);
        if (!$check_stmt->execute()) {
            throw new Exception("Database execute error: " . $check_stmt->error);
        }
        
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Campaign not found with ID: " . $campaign_id);
        }
        
        // Get the campaign name for the success message
        $campaign_data = $result->fetch_assoc();
        $campaign_name = $campaign_data['name'];

        // Delete the campaign
        $delete_stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
        if (!$delete_stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $delete_stmt->bind_param('i', $campaign_id);
        if (!$delete_stmt->execute()) {
            throw new Exception("Database execute error: " . $delete_stmt->error);
        }
        
        // Set success message and redirect
        $_SESSION['success'] = "Campaign \"" . htmlspecialchars($campaign_name) . "\" has been successfully deleted.";
        header('Location: list.php');
        exit();
        
    } catch (Exception $e) {
        // Set error message and redirect
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: list.php');
        exit();
    }
} else {
    // This is not a POST request
    $_SESSION['error'] = "Invalid request method. Please try again.";
    header('Location: list.php');
    exit();
}