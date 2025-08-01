<?php
// File: /advertiser/templates/footer.php
?>
        </div>
    </main>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle
            const toggleSidebar = document.querySelector('.toggle-sidebar');
            const toggleSidebarMobile = document.getElementById('toggleSidebarMobile');
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            
            toggleSidebar.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-collapsed');
                
                // Store sidebar state in cookie
                document.cookie = 'sidebar_collapsed=' + (document.body.classList.contains('sidebar-collapsed') ? 'true' : 'false') + '; path=/; max-age=31536000';
                
                // Rotate icon
                const icon = this.querySelector('i');
                if (document.body.classList.contains('sidebar-collapsed')) {
                    icon.classList.remove('bi-chevron-left');
                    icon.classList.add('bi-chevron-right');
                } else {
                    icon.classList.remove('bi-chevron-right');
                    icon.classList.add('bi-chevron-left');
                }
            });
            
            // Initialize icon direction
            const toggleIcon = toggleSidebar.querySelector('i');
            if (document.body.classList.contains('sidebar-collapsed')) {
                toggleIcon.classList.remove('bi-chevron-left');
                toggleIcon.classList.add('bi-chevron-right');
            }
            
            // Mobile sidebar toggle
            if (toggleSidebarMobile) {
                toggleSidebarMobile.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                });
                
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }
            
            // Add system info to footer
            const footerInfo = document.createElement('div');
            footerInfo.className = 'text-center mt-4 pt-4 border-top small text-muted';
            footerInfo.innerHTML = `
                <div>Clicterra Advertiser Panel v1.0</div>
                <div>Last updated: 2025-07-24 01:53:34 UTC</div>
                <div>User: simoncode12lanjutkan</div>
            `;
            document.querySelector('.main-content > .container-fluid').appendChild(footerInfo);
        });
    </script>
</body>
</html>