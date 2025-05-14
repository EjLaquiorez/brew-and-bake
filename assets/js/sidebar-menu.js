/**
 * Sidebar User Menu - Functionality for the sidebar user menu dropdown
 * This file handles the dropdown functionality for the user menu in the sidebar
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get the sidebar user menu elements
    const userMenuToggle = document.getElementById('sidebarUserMenuToggle');
    const userMenu = document.getElementById('userMenu');
    const userMenuIcon = document.querySelector('.user-menu-toggle');

    if (userMenuToggle && userMenu) {
        // Function to show the user menu
        function showUserMenu() {
            userMenu.style.display = 'block';
            userMenuIcon.style.transform = 'rotate(180deg)';
        }

        // Function to hide the user menu
        function hideUserMenu() {
            userMenu.style.display = 'none';
            userMenuIcon.style.transform = 'rotate(0deg)';
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
});
