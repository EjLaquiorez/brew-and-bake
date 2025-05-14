document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const sidebarClose = document.querySelector('.sidebar-close');
    const mainContent = document.querySelector('.admin-main');

    // Show sidebar when menu toggle is clicked
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar?.classList.add('show');
        });
    }

    // Hide sidebar when close button is clicked
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            sidebar?.classList.remove('show');
        });
    }

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 992;
        const isClickInsideSidebar = sidebar?.contains(event.target);
        const isClickOnToggle = menuToggle?.contains(event.target);

        if (isMobile && !isClickInsideSidebar && !isClickOnToggle && sidebar?.classList.contains('show')) {
            sidebar?.classList.remove('show');
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar?.classList.remove('show');
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            if (!confirm(this.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});