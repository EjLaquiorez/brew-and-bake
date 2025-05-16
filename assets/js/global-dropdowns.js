/**
 * Global Dropdowns JavaScript
 * This file contains the JavaScript for all dropdown functionality across the site
 * It provides consistent hover and interaction behavior for all dropdowns
 */

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Initialize hover and interaction functionality for a dropdown
     * @param {string} menuSelector - CSS selector for the menu container
     * @param {string} toggleSelector - CSS selector for the toggle element
     * @param {string} dropdownSelector - CSS selector for the dropdown element
     */
    function initializeDropdown(menuSelector, toggleSelector, dropdownSelector) {
        const menuElements = document.querySelectorAll(menuSelector);
        
        menuElements.forEach(menuElement => {
            const toggleElement = menuElement.querySelector(toggleSelector);
            const dropdownElement = menuElement.querySelector(dropdownSelector);
            
            if (!menuElement || !toggleElement || !dropdownElement) return;
            
            let dropdownTimeout;
            
            // Show dropdown on hover
            menuElement.addEventListener('mouseenter', function() {
                clearTimeout(dropdownTimeout);
                dropdownElement.classList.add('show');
                
                // If there's a loadItems function specific to this dropdown, call it
                const loadFunction = dropdownElement.dataset.loadFunction;
                if (loadFunction && typeof window[loadFunction] === 'function') {
                    window[loadFunction]();
                }
            });
            
            // Allow hovering over the dropdown itself
            dropdownElement.addEventListener('mouseenter', function() {
                clearTimeout(dropdownTimeout);
            });
            
            // Handle mouse leaving the menu
            menuElement.addEventListener('mouseleave', function(e) {
                // Check if the mouse is moving toward the dropdown
                const rect = dropdownElement.getBoundingClientRect();
                
                // Only start the timeout if not moving toward the dropdown
                if (!(e.clientY >= rect.top && e.clientY <= rect.bottom && 
                      e.clientX >= rect.left && e.clientX <= rect.right)) {
                    dropdownTimeout = setTimeout(() => {
                        // Only close if mouse is not over the dropdown
                        if (!dropdownElement.matches(':hover')) {
                            dropdownElement.classList.remove('show');
                        }
                    }, 300); // Small delay to allow movement to dropdown
                }
            });
            
            // Handle mouse leaving the dropdown
            dropdownElement.addEventListener('mouseleave', function() {
                // Only close if mouse is not over the menu
                if (!menuElement.matches(':hover')) {
                    dropdownTimeout = setTimeout(() => {
                        dropdownElement.classList.remove('show');
                    }, 300); // Small delay before closing
                }
            });
            
            // Toggle dropdown on click (for mobile)
            toggleElement.addEventListener('click', function(e) {
                // Only prevent default if it's not a direct link
                if (toggleElement.getAttribute('href') === '#' || 
                    toggleElement.getAttribute('id') === 'loginCartLink') {
                    e.preventDefault();
                }
                
                dropdownElement.classList.toggle('show');
                
                // If there's a loadItems function specific to this dropdown, call it
                if (dropdownElement.classList.contains('show')) {
                    const loadFunction = dropdownElement.dataset.loadFunction;
                    if (loadFunction && typeof window[loadFunction] === 'function') {
                        window[loadFunction]();
                    }
                }
            });
            
            // Prevent dropdown from closing when clicking inside it
            dropdownElement.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            menuElements.forEach(menuElement => {
                const dropdownElement = menuElement.querySelector(dropdownSelector);
                
                if (dropdownElement && !menuElement.contains(e.target) && !dropdownElement.contains(e.target)) {
                    dropdownElement.classList.remove('show');
                }
            });
        });
    }
    
    // Initialize common dropdowns
    initializeDropdown('.user-menu', '.user-icon', '.user-dropdown');
    initializeDropdown('.cart-menu', '.cart-icon', '.cart-dropdown');
    
    // For admin dropdowns that use different selectors
    const adminDropdowns = [
        { menu: '.dropdown', toggle: '.dropdown-toggle', dropdown: '.dropdown-menu' },
        { menu: '#notificationIcon', toggle: '#notificationIcon', dropdown: '#notificationsDropdown' },
        { menu: '#messageIcon', toggle: '#messageIcon', dropdown: '#messagesDropdown' },
        { menu: '#userProfileIcon', toggle: '#userProfileIcon', dropdown: '#userDropdown' }
    ];
    
    adminDropdowns.forEach(dropdown => {
        initializeDropdown(dropdown.menu, dropdown.toggle, dropdown.dropdown);
    });
    
    // Special case for sidebar user menu
    const sidebarUserMenu = document.getElementById('sidebarUserMenuToggle');
    const sidebarUserDropdown = document.getElementById('userMenu');
    
    if (sidebarUserMenu && sidebarUserDropdown) {
        let sidebarDropdownTimeout;
        
        // Show dropdown on hover
        sidebarUserMenu.addEventListener('mouseenter', function() {
            clearTimeout(sidebarDropdownTimeout);
            sidebarUserDropdown.style.display = 'block';
        });
        
        // Allow hovering over the dropdown itself
        sidebarUserDropdown.addEventListener('mouseenter', function() {
            clearTimeout(sidebarDropdownTimeout);
        });
        
        // Handle mouse leaving the menu
        sidebarUserMenu.addEventListener('mouseleave', function(e) {
            // Check if the mouse is moving toward the dropdown
            const rect = sidebarUserDropdown.getBoundingClientRect();
            
            // Only start the timeout if not moving toward the dropdown
            if (!(e.clientY >= rect.top && e.clientY <= rect.bottom && 
                  e.clientX >= rect.left && e.clientX <= rect.right)) {
                sidebarDropdownTimeout = setTimeout(() => {
                    // Only close if mouse is not over the dropdown
                    if (!sidebarUserDropdown.matches(':hover')) {
                        sidebarUserDropdown.style.display = 'none';
                    }
                }, 300); // Small delay to allow movement to dropdown
            }
        });
        
        // Handle mouse leaving the dropdown
        sidebarUserDropdown.addEventListener('mouseleave', function() {
            // Only close if mouse is not over the menu
            if (!sidebarUserMenu.matches(':hover')) {
                sidebarDropdownTimeout = setTimeout(() => {
                    sidebarUserDropdown.style.display = 'none';
                }, 300); // Small delay before closing
            }
        });
        
        // Toggle dropdown on click (for mobile)
        sidebarUserMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebarUserDropdown.style.display = sidebarUserDropdown.style.display === 'block' ? 'none' : 'block';
        });
        
        // Prevent dropdown from closing when clicking inside it
        sidebarUserDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
