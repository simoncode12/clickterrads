<?php
// File: /publisher/templates/footer.php (REDESIGNED FOR MODERN LAYOUT)
?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const closeSidebar = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        }
        
        if (closeSidebar && sidebar) {
            closeSidebar.addEventListener('click', function() {
                sidebar.classList.remove('collapsed');
            });
        }
        
        // Handle responsive behavior
        function handleResize() {
            if (window.innerWidth < 992) {
                mainContent.classList.remove('collapsed');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 500, hide: 100 }
            });
        });
        
        // Animate stats on page load
        const statValues = document.querySelectorAll('.stat-value');
        statValues.forEach(stat => {
            stat.style.opacity = 0;
            setTimeout(() => {
                stat.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                stat.style.opacity = 1;
                stat.style.transform = 'translateY(0)';
            }, 300);
        });
    });
    </script>
</body>
</html>