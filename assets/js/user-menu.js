/**
 * User Menu JavaScript
 * This file contains the JavaScript for the user menu dropdown functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // User dropdown with hover and interaction functionality
    const userMenu = document.querySelector('.user-menu');
    const userIcon = document.querySelector('.user-icon');
    const userDropdown = document.querySelector('.user-dropdown');

    if (userMenu && userIcon && userDropdown) {
        let userDropdownTimeout;

        // Show dropdown on hover
        userMenu.addEventListener('mouseenter', function() {
            clearTimeout(userDropdownTimeout);
            userDropdown.classList.add('show');
        });

        // Allow hovering over the dropdown itself
        userDropdown.addEventListener('mouseenter', function() {
            clearTimeout(userDropdownTimeout);
        });

        // Handle mouse leaving the user menu
        userMenu.addEventListener('mouseleave', function(e) {
            // Check if the mouse is moving toward the dropdown
            const rect = userDropdown.getBoundingClientRect();

            // Only start the timeout if not moving toward the dropdown
            if (!(e.clientY >= rect.top && e.clientY <= rect.bottom &&
                  e.clientX >= rect.left && e.clientX <= rect.right)) {
                userDropdownTimeout = setTimeout(() => {
                    // Only close if mouse is not over the dropdown
                    if (!userDropdown.matches(':hover')) {
                        userDropdown.classList.remove('show');
                    }
                }, 300); // Small delay to allow movement to dropdown
            }
        });

        // Handle mouse leaving the dropdown
        userDropdown.addEventListener('mouseleave', function() {
            // Only close if mouse is not over the user menu
            if (!userMenu.matches(':hover')) {
                userDropdownTimeout = setTimeout(() => {
                    userDropdown.classList.remove('show');
                }, 300); // Small delay before closing
            }
        });

        // Toggle dropdown on click (for mobile)
        userIcon.addEventListener('click', function(e) {
            e.preventDefault();
            userDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });

        // Prevent dropdown from closing when clicking inside it
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Cart dropdown with hover and interaction functionality
    const cartMenu = document.querySelector('.cart-menu');
    const cartIcon = document.querySelector('.cart-icon');
    const cartDropdown = document.querySelector('.cart-dropdown');

    if (cartMenu && cartIcon && cartDropdown) {
        let dropdownTimeout;

        // Show dropdown on hover
        cartMenu.addEventListener('mouseenter', function() {
            clearTimeout(dropdownTimeout);
            cartDropdown.classList.add('show');

            // Load cart items if the function exists
            if (typeof loadCartItems === 'function') {
                loadCartItems();
            }
        });

        // Allow hovering over the dropdown itself
        cartDropdown.addEventListener('mouseenter', function() {
            clearTimeout(dropdownTimeout);
        });

        // Handle mouse leaving the cart menu
        cartMenu.addEventListener('mouseleave', function(e) {
            // Check if the mouse is moving toward the dropdown
            const rect = cartDropdown.getBoundingClientRect();

            // Only start the timeout if not moving toward the dropdown
            if (!(e.clientY >= rect.top && e.clientY <= rect.bottom &&
                  e.clientX >= rect.left && e.clientX <= rect.right)) {
                dropdownTimeout = setTimeout(() => {
                    // Only close if mouse is not over the dropdown
                    if (!cartDropdown.matches(':hover')) {
                        cartDropdown.classList.remove('show');
                    }
                }, 300); // Small delay to allow movement to dropdown
            }
        });

        // Handle mouse leaving the dropdown
        cartDropdown.addEventListener('mouseleave', function() {
            // Only close if mouse is not over the cart menu
            if (!cartMenu.matches(':hover')) {
                dropdownTimeout = setTimeout(() => {
                    cartDropdown.classList.remove('show');
                }, 300); // Small delay before closing
            }
        });

        // Also handle click for mobile devices
        cartIcon.addEventListener('click', function(e) {
            // Only prevent default if it's not a direct link to orders.php
            if (cartIcon.getAttribute('id') === 'loginCartLink' ||
                cartIcon.getAttribute('href') === '#') {
                e.preventDefault();
                cartDropdown.classList.toggle('show');

                if (cartDropdown.classList.contains('show')) {
                    // Load cart items if the function exists
                    if (typeof loadCartItems === 'function') {
                        loadCartItems();
                    }
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!cartMenu.contains(e.target) && !cartDropdown.contains(e.target)) {
                cartDropdown.classList.remove('show');
            }
        });

        // Prevent dropdown from closing when clicking inside it
        cartDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Function to load cart items
    window.loadCartItems = function() {
        const cartDropdownItems = document.querySelector('.cart-dropdown-items');
        if (!cartDropdownItems) return;

        // Show loading indicator
        cartDropdownItems.innerHTML = `
            <div class="cart-dropdown-loading">
                <div class="spinner-border spinner-border-sm text-secondary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Loading cart items...</span>
            </div>
        `;

        // Fetch cart items using AJAX - use a relative path
        // Determine the path to get_cart_items.php based on the current page
        let cartItemsPath = 'templates/client/get_cart_items.php';
        let imagesPath = 'assets/images/products/';
        let clientPath = 'templates/client/client.php';

        // If we're already in a subdirectory, adjust the paths
        if (window.location.pathname.includes('/templates/client/')) {
            cartItemsPath = 'get_cart_items.php';
            imagesPath = '../../assets/images/products/';
            clientPath = 'client.php';
        } else if (window.location.pathname.includes('/templates/')) {
            cartItemsPath = 'client/get_cart_items.php';
            imagesPath = '../../assets/images/products/';
            clientPath = 'client/client.php';
        } else if (window.location.pathname.includes('/admin/') ||
                   window.location.pathname.includes('/client/') ||
                   window.location.pathname.includes('/staff/')) {
            cartItemsPath = '../client/get_cart_items.php';
            imagesPath = '../../../assets/images/products/';
            clientPath = '../client/client.php';
        }

        fetch(cartItemsPath)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.items.length > 0) {
                        let html = '';
                        data.items.forEach(item => {
                            html += `
                                <div class="cart-dropdown-item">
                                    <img src="${imagesPath}${item.image || 'placeholder.jpg'}" alt="${item.name}" class="cart-item-image">
                                    <div class="cart-item-details">
                                        <div class="cart-item-name">${item.name}</div>
                                        <div class="cart-item-price">
                                            <span>₱${parseFloat(item.price).toFixed(2)}</span>
                                            <span class="cart-item-quantity">x${item.quantity}</span>
                                        </div>
                                        <div class="cart-item-subtotal text-end">
                                            <small class="text-muted">Subtotal: ₱${parseFloat(item.subtotal).toFixed(2)}</small>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        cartDropdownItems.innerHTML = html;

                        // Update cart count and total
                        const cartTotalCount = document.querySelector('.cart-total-count');
                        if (cartTotalCount) {
                            cartTotalCount.textContent = `${data.total_items} items in cart - Total: ₱${parseFloat(data.total_amount).toFixed(2)}`;
                        }
                    } else {
                        cartDropdownItems.innerHTML = `
                            <div class="cart-empty">
                                <i class="bi bi-cart-x"></i>
                                <p>Your cart is empty</p>
                                <a href="${clientPath}" class="btn btn-sm btn-primary">Browse Menu</a>
                            </div>
                        `;
                    }
                } else {
                    cartDropdownItems.innerHTML = `
                        <div class="cart-empty">
                            <i class="bi bi-exclamation-triangle"></i>
                            <p>Error loading cart items</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching cart items:', error);
                cartDropdownItems.innerHTML = `
                    <div class="cart-empty">
                        <i class="bi bi-exclamation-triangle"></i>
                        <p>Error loading cart items</p>
                    </div>
                `;
            });
    };
});
