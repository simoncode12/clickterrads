<?php
// File: /admin/templates/footer.php (REBUILT)
?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                // Untuk desktop, toggle margin. Untuk mobile (di bawah 992px), toggle margin juga akan berfungsi.
                sidebar.classList.toggle('collapsed');
            });
        }
    });
    </script>
</body>
</html>
