<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/global-dropdowns.js"></script>
<script src="../../assets/js/admin.js"></script>
<script src="../../assets/js/admin-dropdowns.js"></script>
<script src="../../assets/js/sidebar-menu.js"></script>
<?php
// Include page-specific scripts
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if ($current_page === 'settings') {
    echo '<script src="../../assets/js/settings.js"></script>';
}
?>
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

        // Update time and date
        function updateDateTime() {
            const now = new Date();

            // Update time
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const timeElement = document.getElementById('currentTime');

            // Update date
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateElement = document.getElementById('currentDate');

            if (timeElement) {
                timeElement.style.opacity = 0;
                setTimeout(() => {
                    timeElement.textContent = now.toLocaleTimeString([], timeOptions);
                    timeElement.style.opacity = 1;
                }, 200);
            }

            if (dateElement) {
                dateElement.style.opacity = 0;
                setTimeout(() => {
                    dateElement.textContent = now.toLocaleDateString([], dateOptions);
                    dateElement.style.opacity = 1;
                }, 200);
            }
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Dropdown functionality is now handled by admin-dropdowns.js
    });
</script>
