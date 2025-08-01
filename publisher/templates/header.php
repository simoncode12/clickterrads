<?php
// File: /publisher/templates/header.php (REDESIGNED FOR MODERN LAYOUT)
require_once __DIR__ . '/../init.php';

// Get first letter of username for avatar
$userInitial = strtoupper(substr($_SESSION['publisher_username'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Portal - Clicterra</title>
    <?php $favicon_path = get_setting('site_favicon', $conn); ?>
    <?php if ($favicon_path && file_exists(__DIR__ . '/../../' . $favicon_path)): ?>
        <link rel="icon" href="../<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        <div class="main-content" id="main-content">
            <header class="top-header shadow-sm">
                <button class="header-toggle d-lg-none" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="header-actions">
                    <div class="header-nav-item">
                        <button class="nav-link" title="Help & Support">
                            <i class="bi bi-question-circle"></i>
                        </button>
                    </div>
                    
                    <div class="header-nav-item">
                        <button class="nav-link" title="Notifications">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                    </div>
                    
                    <div class="dropdown">
                        <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php echo $userInitial; ?>
                            </div>
                            <div class="user-info d-none d-sm-flex">
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['publisher_username']); ?></span>
                                <span class="user-role">Publisher</span>
                            </div>
                            <i class="bi bi-chevron-down ms-2 text-muted"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text fw-bold">Hi, <?php echo htmlspecialchars($_SESSION['publisher_username']); ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="account.php"><i class="bi bi-person"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="withdraw.php"><i class="bi bi-wallet2"></i> My Wallet</a></li>
                            <li><a class="dropdown-item" href="support.php"><i class="bi bi-question-circle"></i> Help Center</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            <main class="content">