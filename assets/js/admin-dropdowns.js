/**
 * Admin Dropdowns - Common functionality for all admin pages
 * This file handles the dropdown functionality for notifications, messages, and user profile
 */
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown functionality
    const dropdownToggles = {
        'notificationIcon': 'notificationsDropdown',
        'messageIcon': 'messagesDropdown',
        'userProfileIcon': 'userDropdown'
    };

    // Track active dropdown
    let activeDropdown = null;

    // Function to close all dropdowns
    function closeAllDropdowns() {
        Object.values(dropdownToggles).forEach(dropdownId => {
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                dropdown.classList.remove('show');
            }
        });
        activeDropdown = null;
    }

    // Add click event listeners to toggle dropdowns
    Object.entries(dropdownToggles).forEach(([toggleId, dropdownId]) => {
        const toggle = document.getElementById(toggleId);
        const dropdown = document.getElementById(dropdownId);

        if (toggle && dropdown) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();

                // If this dropdown is already active, close it
                if (activeDropdown === dropdownId) {
                    dropdown.classList.remove('show');
                    activeDropdown = null;
                } else {
                    // Close any open dropdown
                    closeAllDropdowns();

                    // Ensure dropdown is positioned correctly
                    const toggleRect = toggle.getBoundingClientRect();

                    // Set dropdown position to be directly under the toggle
                    dropdown.style.top = (toggle.offsetHeight + 5) + 'px';

                    // Right-align all dropdowns (user profile, notifications, messages)
                    dropdown.style.left = 'auto';
                    dropdown.style.right = '0';

                    // Open this dropdown
                    dropdown.classList.add('show');
                    activeDropdown = dropdownId;
                }
            });

            // Prevent clicks inside dropdown from closing it
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', closeAllDropdowns);

    // Mark all notifications as read
    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const unreadItems = document.querySelectorAll('.notification-item.unread');
            unreadItems.forEach(item => {
                item.classList.remove('unread');
            });

            // Update notification badge
            const notificationBadge = document.querySelector('#notificationIcon .topbar-badge');
            if (notificationBadge) {
                notificationBadge.textContent = '0';
                notificationBadge.style.display = 'none';
            }
        });
    }
});
