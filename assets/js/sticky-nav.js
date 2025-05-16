/**
 * Sticky Navigation JavaScript
 * This file contains the JavaScript for the sticky navigation functionality
 */

// Function to initialize sticky navigation
function initStickyNav() {
    // Get the page navigation container
    const pageNavContainer = document.querySelector('.page-nav');

    if (pageNavContainer) {
        // Get the scroll progress bar
        const scrollProgressBar = document.querySelector('.scroll-progress-bar');

        // Get the header height to determine when to make the nav sticky
        const headerHeight = document.querySelector('.site-header').offsetHeight;

        // Function to update scroll progress bar
        function updateScrollProgress() {
            if (!scrollProgressBar) return;

            const windowScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (windowScroll / height) * 100;
            scrollProgressBar.style.width = scrolled + '%';
        }

        // Function to check if page nav is sticky and update appearance
        function checkNavSticky() {
            // Get the current height of the page nav
            const pageNavHeight = pageNavContainer.offsetHeight;

            if (window.scrollY > headerHeight) {
                // Make the page nav sticky
                pageNavContainer.classList.add('is-sticky');

                // Add padding to body to prevent content jump
                if (!document.body.style.paddingTop || document.body.style.paddingTop === '0px') {
                    document.body.style.paddingTop = pageNavHeight + 'px';
                }

                // Force the page nav to be fixed position
                pageNavContainer.style.position = 'fixed';
                pageNavContainer.style.top = '0';
                pageNavContainer.style.left = '0';
                pageNavContainer.style.right = '0';
                pageNavContainer.style.width = '100%';
                pageNavContainer.style.zIndex = '1000';
            } else {
                // Remove sticky class
                pageNavContainer.classList.remove('is-sticky');

                // Remove padding when not sticky
                document.body.style.paddingTop = '0';

                // Reset inline styles
                pageNavContainer.style.position = '';
                pageNavContainer.style.top = '';
                pageNavContainer.style.left = '';
                pageNavContainer.style.right = '';
                pageNavContainer.style.width = '';
            }

            // Update scroll progress
            updateScrollProgress();
        }

        // Add scroll event listener
        window.addEventListener('scroll', checkNavSticky);

        // Add resize event listener to handle window size changes
        window.addEventListener('resize', checkNavSticky);

        // Initialize on page load
        checkNavSticky();
    }
}

// Run immediately
initStickyNav();

// Also run when DOM is fully loaded to ensure all elements are available
document.addEventListener('DOMContentLoaded', initStickyNav);

// Run when window is fully loaded with all resources
window.addEventListener('load', initStickyNav);
