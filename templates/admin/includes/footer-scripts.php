<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/global-dropdowns.js"></script>
<script src="../../assets/js/admin.js"></script>
<script src="../../assets/js/admin-dropdowns.js"></script>
<script src="../../assets/js/sidebar-menu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const sidebarClose = document.querySelector('.sidebar-close');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.add('show');
            });
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', function() {
                sidebar.classList.remove('show');
            });
        }

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });

        // Update time
        function updateTime() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const timeElement = document.getElementById('currentTime');

            if (timeElement) {
                timeElement.style.opacity = 0;

                setTimeout(() => {
                    timeElement.textContent = now.toLocaleTimeString([], options);
                    timeElement.style.opacity = 1;
                }, 200);
            }
        }

        updateTime();
        setInterval(updateTime, 60000);

        // Dropdown functionality is now handled by admin-dropdowns.js
    });
</script>
