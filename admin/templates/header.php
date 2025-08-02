<?php
// File: /admin/templates/header.php (REBUILT)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdServer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="main-content" id="main-content">
            <header class="header shadow-sm">
                <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
                    <div class="container-fluid">
                        <button class="btn btn-light" type="button" id="sidebar-toggle">
                            <i class="bi bi-list"></i>
                        </button>
                        
                        <div class="ms-auto d-flex align-items-center">
                            <span class="navbar-text me-3">
                                Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                            </span>
                            <a href="logout.php" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </div>
                    </div>
                </nav>
            </header>
            
            <main class="content">
                <div class="container-fluid p-4">
