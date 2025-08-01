<?php
// File: /advertiser/templates/header.php

// Ensure this file isn't accessed directly
if (!defined('IN_APP')) {
    // Check if advertiser_id is set in session to determine if init.php was loaded
    if (!isset($_SESSION['advertiser_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$active_directory = dirname($_SERVER['PHP_SELF']);
$active_directory = ltrim(str_replace('/advertiser', '', $active_directory), '/');
if (!$active_directory) $active_directory = 'dashboard';

// Check for unread notifications
$unread_notifications = 0; // This would be fetched from a database in a real application

// Advertiser username (from session)
$advertiser_username = $_SESSION['username'] ?? 'Advertiser';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Clicterra Advertiser Portal</title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Custom CSS -->
    <style>
        /* Base styles */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: rgba(67, 97, 238, 0.1);
            --secondary: #3f37c9;
            --success: #10b981;
            --danger: #f43f5e;
            --warning: #fbbf24;
            --info: #38bdf8;
            --light: #f8fafc;
            --dark: #0f172a;
            --gray: #64748b;
            --bg-light: #f5f7fa;
            --border-color: #e2e8f0;
            --text-color: #334155;
            --text-muted: #64748b;
            --header-height: 70px;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --transition: all 0.3s ease;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0.05);
            --shadow-lg: 0 15px 30px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.05);
        }

        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-color);
            background-color: var(--bg-light);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: #fff;
            border-right: 1px solid var(--border-color);
            z-index: 1030;
            overflow-y: auto;
            transition: var(--transition);
        }

        .sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--dark);
            text-decoration: none;
        }

        .sidebar-brand .brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 8px;
            margin-right: 1rem;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .sidebar-collapsed .brand-text {
            display: none;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .sidebar-header {
            padding: 0.75rem 1.5rem;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-collapsed .sidebar-header {
            text-align: center;
            padding: 0.75rem 0;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-color);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: var(--transition);
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: var(--primary-light);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .sidebar-link i {
            font-size: 1.25rem;
            margin-right: 1rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-collapsed .sidebar-link {
            padding: 0.75rem;
            justify-content: center;
        }

        .sidebar-collapsed .sidebar-link i {
            margin-right: 0;
        }

        .sidebar-collapsed .link-text {
            display: none;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            position: sticky;
            bottom: 0;
            background-color: #fff;
        }

        .sidebar-collapsed .sidebar-footer {
            padding: 1rem 0;
            text-align: center;
        }

        .sidebar-collapsed .sidebar-footer-text {
            display: none;
        }

        .toggle-sidebar {
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            background-color: var(--bg-light);
        }

        .toggle-sidebar i {
            font-size: 1.25rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: calc(var(--header-height) + 1.5rem) 1.5rem 1.5rem;
            transition: var(--transition);
            min-height: 100vh;
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Header */
        .header {
            height: var(--header-height);
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            z-index: 1020;
            transition: var(--transition);
        }

        .sidebar-collapsed .header {
            left: var(--sidebar-collapsed-width);
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            .sidebar.show {
                transform: translateX(0);
                box-shadow: var(--shadow-md);
            }

            .main-content, .header {
                margin-left: 0 !important;
                left: 0 !important;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1025;
                opacity: 0;
                visibility: hidden;
                transition: var(--transition);
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }
        }

        /* Utilities */
        .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--bg-light);
            color: var(--text-color);
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-icon:hover {
            background-color: var(--border-color);
            color: var(--primary);
        }

        .btn-icon i {
            font-size: 1.25rem;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.65rem;
            font-weight: 700;
        }

        /* User Profile Dropdown */
        .user-dropdown .dropdown-menu {
            width: 300px;
            border: none;
            box-shadow: var(--shadow-md);
            border-radius: 12px;
            padding: 0;
        }

        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        .user-dropdown .user-info {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .user-dropdown .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .user-dropdown .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
        }

        .user-dropdown .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .user-dropdown .dropdown-item i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true' ? 'sidebar-collapsed' : ''; ?>">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a class="sidebar-brand" href="/advertiser/dashboard.php">
            <div class="brand-icon">C</div>
            <span class="brand-text fw-bold">Clicterra</span>
        </a>
        <div class="sidebar-nav">
            <div class="sidebar-header">MAIN MENU</div>
            <a href="/advertiser/dashboard.php" class="sidebar-link <?php echo $active_directory === 'dashboard' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span class="link-text">Dashboard</span>
            </a>
            <a href="/advertiser/campaigns/list.php" class="sidebar-link <?php echo $active_directory === 'campaigns' ? 'active' : ''; ?>">
                <i class="bi bi-collection"></i>
                <span class="link-text">Campaigns</span>
            </a>
            <a href="/advertiser/creatives/library.php" class="sidebar-link <?php echo $active_directory === 'creatives' ? 'active' : ''; ?>">
                <i class="bi bi-images"></i>
                <span class="link-text">Creatives</span>
            </a>
            <a href="/advertiser/reports/index.php" class="sidebar-link <?php echo $active_directory === 'reports' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="link-text">Reports</span>
            </a>
            
            <div class="sidebar-header">ACCOUNT</div>
            <a href="/advertiser/billing/index.php" class="sidebar-link <?php echo $active_directory === 'billing' ? 'active' : ''; ?>">
                <i class="bi bi-credit-card"></i>
                <span class="link-text">Billing</span>
            </a>
            <a href="/advertiser/settings/index.php" class="sidebar-link <?php echo $active_directory === 'settings' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span class="link-text">Settings</span>
            </a>
            <a href="/advertiser/support.php" class="sidebar-link <?php echo $active_directory === 'support' ? 'active' : ''; ?>">
                <i class="bi bi-question-circle"></i>
                <span class="link-text">Support</span>
            </a>
        </div>
        <div class="sidebar-footer">
            <div class="d-flex align-items-center justify-content-between">
                <div class="toggle-sidebar">
                    <i class="bi bi-chevron-left"></i>
                </div>
                <div class="sidebar-footer-text">
                    <div class="small text-muted">© <?php echo date('Y'); ?> Clicterra</div>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay"></div>
    
    <!-- Header -->
    <header class="header">
        <div class="d-flex align-items-center">
            <button class="btn-icon me-2 d-lg-none" id="toggleSidebarMobile">
                <i class="bi bi-list"></i>
            </button>
        </div>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="position-relative me-3">
                <button class="btn-icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 320px;">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <h6 class="mb-0">Notifications</h6>
                        <a href="#" class="text-muted small">Mark all as read</a>
                    </div>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <div class="p-3 border-bottom">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-graph-up-arrow text-success bg-success bg-opacity-10 p-2 rounded"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1 fw-medium">Campaign performance update</h6>
                                    <p class="text-muted small mb-1">Your campaign "Summer Promotion" has achieved 120% of its target CTR.</p>
                                    <span class="text-muted smaller">2 hours ago</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-bottom">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-wallet2 text-warning bg-warning bg-opacity-10 p-2 rounded"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1 fw-medium">Payment processed</h6>
                                    <p class="text-muted small mb-1">Your payment of $500.00 has been processed successfully.</p>
                                    <span class="text-muted smaller">Yesterday</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 text-center border-top">
                        <a href="#" class="text-primary small">View all notifications</a>
                    </div>
                </div>
            </div>
            
            <div class="user-dropdown dropdown">
                <button class="d-flex align-items-center bg-transparent border-0" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="me-2 d-none d-md-block">
                        <div class="fw-medium"><?php echo htmlspecialchars($advertiser_username); ?></div>
                        <div class="small text-muted">Advertiser</div>
                    </div>
                    <div class="user-avatar" style="width: 40px; height: 40px; font-size: 1.2rem;">
                        <?php echo substr($advertiser_username, 0, 1); ?>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <div class="user-info">
                            <div class="d-flex align-items-center mb-3">
                                <div class="user-avatar me-3">
                                    <?php echo substr($advertiser_username, 0, 1); ?>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($advertiser_username); ?></h6>
                                    <div class="small text-muted">Advertiser Account</div>
                                </div>
                            </div>
                            <div class="d-grid">
                                <a href="/advertiser/settings/profile.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil me-1"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/advertiser/settings/index.php"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="/advertiser/billing/history.php"><i class="bi bi-credit-card"></i> Billing</a></li>
                    <li><a class="dropdown-item" href="/advertiser/support.php"><i class="bi bi-question-circle"></i> Help & Support</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/advertiser/logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid px-0">