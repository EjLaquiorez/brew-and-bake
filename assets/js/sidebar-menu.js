/**
 * Sidebar Menu - Functionality for the admin sidebar
 * This file handles the sidebar functionality including mobile responsiveness,
 * dropdown menus, and active state management
 */
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const sidebarClose = document.querySelector('.sidebar-close');
    const adminMain = document.querySelector('.admin-main');

    // Toggle sidebar on mobile
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
            document.body.classList.add('sidebar-open');
        });
    }

    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 992 &&
            sidebar &&
            sidebar.classList.contains('show') &&
            !sidebar.contains(e.target) &&
            e.target !== menuToggle &&
            !menuToggle.contains(e.target)) {
            sidebar.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });

    // Get the sidebar user menu elements
    const userMenuToggle = document.getElementById('sidebarUserMenuToggle');
    const userMenu = document.getElementById('userMenu');
    const userMenuIcon = document.querySelector('.user-menu-toggle');

    if (userMenuToggle && userMenu) {
        // Function to show the user menu
        function showUserMenu() {
            userMenu.style.display = 'block';
            if (userMenuIcon) {
                userMenuIcon.style.transform = 'rotate(180deg)';
            }
        }

        // Function to hide the user menu
        function hideUserMenu() {
            userMenu.style.display = 'none';
            if (userMenuIcon) {
                userMenuIcon.style.transform = 'rotate(0deg)';
            }
        }

        // Toggle the user menu when clicking on the user menu toggle
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();

            if (userMenu.style.display === 'block') {
                hideUserMenu();
            } else {
                showUserMenu();
            }
        });

        // Hide the user menu when clicking outside
        document.addEventListener('click', function() {
            hideUserMenu();
        });

        // Prevent clicks inside the user menu from closing it
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Add hover effect to nav items
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            if (!this.querySelector('.nav-link.active')) {
                this.querySelector('.nav-link').classList.add('hover');
            }
        });

        item.addEventListener('mouseleave', function() {
            this.querySelector('.nav-link').classList.remove('hover');
        });
    });
});
