<?php
// File: /publisher/templates/sidebar.php (REDESIGNED FOR MODERN LAYOUT)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
             <?php
                $logo_path = get_setting('site_logo', $conn);
                // Kita perlu path relatif dari folder publisher ke root
                $logo_display_path = '../' . $logo_path;
                if ($logo_path && file_exists(__DIR__ . '/../../' . $logo_path)) {
                    echo '<img src="' . htmlspecialchars($logo_display_path) . '" alt="Site Logo">';
                } else {
                    echo '<span>Clicterra</span>';
                }
            ?>
        </a>
        <button class="sidebar-toggle-btn d-lg-none" id="close-sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="sidebar-body">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Main</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="dashboard.php" class="sidebar-link <?php if($current_page == 'dashboard.php') echo 'active'; ?>">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="statistics.php" class="sidebar-link <?php if($current_page == 'statistics.php') echo 'active'; ?>">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Statistics</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-title">Management</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="sites.php" class="sidebar-link <?php if($current_page == 'sites.php') echo 'active'; ?>">
                        <i class="bi bi-globe2"></i>
                        <span>Sites & Zones</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="withdraw.php" class="sidebar-link <?php if($current_page == 'withdraw.php') echo 'active'; ?>">
                        <i class="bi bi-wallet2"></i>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="referrals.php" class="sidebar-link <?php if($current_page == 'referrals.php') echo 'active'; ?>">
                        <i class="bi bi-people"></i>
                        <span>Referrals</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-title">Account</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="account.php" class="sidebar-link <?php if($current_page == 'account.php') echo 'active'; ?>">
                        <i class="bi bi-person"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="settings.php" class="sidebar-link <?php if($current_page == 'settings.php') echo 'active'; ?>">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="support.php" class="sidebar-link <?php if($current_page == 'support.php') echo 'active'; ?>">
                        <i class="bi bi-question-circle"></i>
                        <span>Help Center</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <small class="text-muted">Clicterra Publisher v1.2</small>
            <a href="logout.php" class="text-danger" title="Logout">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </div>
</aside>