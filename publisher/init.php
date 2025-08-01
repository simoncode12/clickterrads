<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';
if (!isset($_SESSION['publisher_id'])) {
    if (basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'auth.php') {
        $_SESSION['login_error'] = "Please log in to access the publisher portal.";
        header('Location: login.php');
        exit();
    }
}
?>
