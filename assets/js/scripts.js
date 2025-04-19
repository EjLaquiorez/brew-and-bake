// Toggle sidebar visibility on mobile screens
const sidebarToggle = document.querySelector('[data-bs-toggle="offcanvas"]');
const content = document.querySelector('.content');
const sidebar = document.getElementById('sidebar');

sidebarToggle.addEventListener('click', () => {
    content.classList.toggle('collapsed');
    sidebar.classList.toggle('show');
});

// Automatically close the success alert after a short time
setTimeout(() => {
    const alert = document.getElementById('flash-alert');
    if (alert) {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
    }
}, 2000);

setTimeout(() => {
    const alert = document.getElementById('flash-alert');
    if (alert) {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
    }
}, 2000);
