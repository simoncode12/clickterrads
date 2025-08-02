<?php
// File: /admin/templates/sidebar.php (UPDATED to display dynamic logo)
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <?php
            // Mengambil path logo dari pengaturan
            $logo_path = get_setting('site_logo', $conn);
            // Cek apakah file logo benar-benar ada di server
            if ($logo_path && file_exists(__DIR__ . '/../' . $logo_path)) {
                // Tampilkan gambar logo
                echo '<img src="' . htmlspecialchars($logo_path) . '" alt="Site Logo" style="max-height: 40px; width: auto;">';
            } else {
                // Tampilkan teks default jika logo tidak ada
                echo 'AdServer';
            }
            ?>
        </a>
    </div>
    <div class="sidebar-body">
        <ul class="sidebar-nav">
            <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a></li>
            <li class="sidebar-item-title">MANAGEMENT</li>
            <li class="sidebar-item"><a href="user.php" class="sidebar-link"><i class="bi bi-people-fill"></i><span>Users</span></a></li>
            <li class="sidebar-item">
                <a href="#campaigns-menu" data-bs-toggle="collapse" class="sidebar-link collapsed"><i class="bi bi-megaphone-fill"></i><span>Campaigns</span><i class="bi bi-chevron-down dropdown-icon"></i></a>
                <ul id="campaigns-menu" class="sidebar-dropdown list-unstyled collapse">
                <li class="sidebar-item"><a href="campaigns.php" class="sidebar-link">Manage Campaigns</a></li>
                <li class="sidebar-item"><a href="ron-creative.php" class="sidebar-link">Display Creatives</a></li>
                <li class="sidebar-item"><a href="video-creatives.php" class="sidebar-link">Video Creatives</a></li>
               <li class="sidebar-item"><a href="campaigns-create.php" class="sidebar-link">Create New</a></li>
             </ul>
            </li>
            <li class="sidebar-item-title">PUBLISHER & SUPPLY</li>
            <li class="sidebar-item"><a href="site.php" class="sidebar-link"><i class="bi bi-globe"></i><span>Sites</span></a></li>
            <li class="sidebar-item"><a href="zone.php" class="sidebar-link"><i class="bi bi-globe"></i><span>Zones</span></a></li>
            <li class="sidebar-item"><a href="supply-partners.php" class="sidebar-link"><i class="bi bi-broadcast"></i><span>RTB Supply Partners</span></a></li>
            
            <li class="sidebar-item-title">PARTNERS & REPORTS</li>
            <li class="sidebar-item"><a href="ssp.php" class="sidebar-link"><i class="bi bi-person-badge-fill"></i><span>Demand Partners</span></a></li>
            <li class="sidebar-item">
                <a href="#reports-menu" data-bs-toggle="collapse" class="sidebar-link collapsed"><i class="bi bi-bar-chart-line-fill"></i><span>Reports</span><i class="bi bi-chevron-down dropdown-icon"></i></a>
                <ul id="reports-menu" class="sidebar-dropdown list-unstyled collapse">
                    <li class="sidebar-item"><a href="campaigns-report.php" class="sidebar-link">Campaign Analytics</a></li>
                     <li class="sidebar-item"><a href="ron-report.php" class="sidebar-link">RON Analytics</a></li>
                    <li class="sidebar-item"><a href="supply-report.php" class="sidebar-link">Supply Analytics</a></li>
                    <li class="sidebar-item"><a href="demand-report.php" class="sidebar-link">Demand Analytics</a></li>
                </ul>
            </li>
            <li class="sidebar-item-title">SETTINGS</li>
            <li class="sidebar-item"><a href="category.php" class="sidebar-link"><i class="bi bi-tags-fill"></i><span>Categories</span></a></li>
            <li class="sidebar-item"><a href="settings.php" class="sidebar-link"><i class="bi bi-gear-fill"></i><span>Platform Settings</span></a></li>
            <li class="sidebar-item"><a href="fraud-settings.php" class="sidebar-link"><i class="bi bi-shield-slash-fill"></i><span>Fraud Protection</span></a></li>
        </ul>
    </div>
</aside>
