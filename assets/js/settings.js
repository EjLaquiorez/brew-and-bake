/**
 * Settings Page JavaScript
 * 
 * This file contains JavaScript functionality for the settings page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Settings Navigation
    const settingsNavItems = document.querySelectorAll('.settings-nav-item');
    settingsNavItems.forEach(item => {
        item.addEventListener('click', function() {
            settingsNavItems.forEach(navItem => {
                navItem.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Test Email Modal
    const testEmailBtn = document.getElementById('testEmailBtn');
    if (testEmailBtn) {
        testEmailBtn.addEventListener('click', function() {
            // Show a modal or send a test email
            alert('This feature will send a test email using the current SMTP settings.');
        });
    }

    // Clear Cache Button
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', function() {
            // Send AJAX request to clear cache
            fetch('../includes/settings_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'clear_cache'
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Cache cleared successfully.');
                } else {
                    showAlert('danger', 'Failed to clear cache: ' + data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Error: ' + error.message);
            });
        });
    }

    // Optimize Database Button
    const optimizeDatabaseBtn = document.getElementById('optimizeDatabaseBtn');
    if (optimizeDatabaseBtn) {
        optimizeDatabaseBtn.addEventListener('click', function() {
            // Show a confirmation dialog
            if (confirm('Are you sure you want to optimize the database? This may take a few moments.')) {
                // Show loading indicator
                this.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i> Optimizing...';
                this.disabled = true;
                
                // Simulate database optimization (replace with actual AJAX call)
                setTimeout(() => {
                    this.innerHTML = '<i class="bi bi-database-check me-2"></i> Optimize';
                    this.disabled = false;
                    showAlert('success', 'Database optimized successfully.');
                }, 2000);
            }
        });
    }

    // Maintenance Mode Toggle
    const maintenanceModeToggle = document.getElementById('maintenance_mode');
    if (maintenanceModeToggle) {
        maintenanceModeToggle.addEventListener('change', function() {
            // Send AJAX request to update maintenance mode
            fetch('../includes/settings_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    category: 'system',
                    key: 'maintenance_mode',
                    value: this.checked ? '1' : '0'
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Maintenance mode ' + (this.checked ? 'enabled' : 'disabled') + ' successfully.');
                } else {
                    showAlert('danger', 'Failed to update maintenance mode: ' + data.message);
                    // Revert the toggle if the update failed
                    this.checked = !this.checked;
                }
            })
            .catch(error => {
                showAlert('danger', 'Error: ' + error.message);
                // Revert the toggle if there was an error
                this.checked = !this.checked;
            });
        });
    }

    // Backup Buttons
    const backupButtons = document.querySelectorAll('.backup-btn');
    backupButtons.forEach(button => {
        button.addEventListener('click', function() {
            const backupType = this.getAttribute('data-backup-type');
            
            // Show loading indicator
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i> Processing...';
            this.disabled = true;
            
            // Simulate backup process (replace with actual AJAX call)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
                
                // Show success message
                showAlert('success', backupType + ' backup created successfully.');
                
                // Simulate download
                const a = document.createElement('a');
                a.href = '#';
                a.download = 'brew_and_bake_' + backupType.toLowerCase() + '_backup_' + getFormattedDate() + '.zip';
                a.click();
            }, 2000);
        });
    });

    // Restore Backup Button
    const restoreBackupBtn = document.getElementById('restoreBackupBtn');
    if (restoreBackupBtn) {
        restoreBackupBtn.addEventListener('click', function() {
            const backupFile = document.getElementById('backupFile');
            
            if (!backupFile.files.length) {
                showAlert('warning', 'Please select a backup file to restore.');
                return;
            }
            
            // Show a confirmation dialog
            if (confirm('Are you sure you want to restore from this backup? This will overwrite your current data.')) {
                // Show loading indicator
                this.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i> Restoring...';
                this.disabled = true;
                
                // Simulate restore process (replace with actual AJAX call)
                setTimeout(() => {
                    this.innerHTML = '<i class="bi bi-cloud-arrow-down me-2"></i> Restore Backup';
                    this.disabled = false;
                    showAlert('success', 'Backup restored successfully.');
                }, 3000);
            }
        });
    }

    // Save Schedule Button
    const saveScheduleBtn = document.getElementById('saveScheduleBtn');
    if (saveScheduleBtn) {
        saveScheduleBtn.addEventListener('click', function() {
            const scheduledBackups = document.getElementById('scheduled_backups').checked;
            const backupFrequency = document.getElementById('backup_frequency').value;
            const backupTime = document.getElementById('backup_time').value;
            
            // Show loading indicator
            this.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i> Saving...';
            this.disabled = true;
            
            // Simulate saving schedule (replace with actual AJAX call)
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-save me-2"></i> Save Schedule';
                this.disabled = false;
                showAlert('success', 'Backup schedule saved successfully.');
            }, 1000);
        });
    }

    /**
     * Show an alert message
     * 
     * @param {string} type The alert type (success, danger, warning, info)
     * @param {string} message The alert message
     */
    function showAlert(type, message) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        
        // Add alert content
        alertDiv.innerHTML = `
            <div class="alert-icon">
                <div class="alert-icon-symbol">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
                </div>
                <div class="alert-content">
                    <h6 class="alert-title">${type.charAt(0).toUpperCase() + type.slice(1)}</h6>
                    <p class="alert-text">${message}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert alert at the top of the content area
        const contentArea = document.querySelector('.admin-content');
        contentArea.insertBefore(alertDiv, contentArea.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                alertDiv.remove();
            }, 150);
        }, 5000);
    }

    /**
     * Get formatted date for backup filename
     * 
     * @returns {string} Formatted date (YYYY-MM-DD_HH-MM-SS)
     */
    function getFormattedDate() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
    }
});
