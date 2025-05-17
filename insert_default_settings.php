<?php
/**
 * Insert Default Settings
 * 
 * This script manually inserts default settings into the database
 * without relying on the settings class
 */

// Set content type to plain text for better readability in browser
header('Content-Type: text/plain');

// Include database connection
require_once "templates/includes/db.php";

// Check if the system_settings table exists
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'system_settings'
    ");
    $stmt->execute();
    $tableExists = $stmt->fetchColumn() > 0;
    
    if (!$tableExists) {
        // Create the system_settings table
        $sql = "CREATE TABLE system_settings (
            id INT NOT NULL AUTO_INCREMENT,
            category VARCHAR(50) NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT NULL,
            data_type ENUM('string', 'integer', 'float', 'boolean', 'array', 'json') NOT NULL DEFAULT 'string',
            is_public BOOLEAN NOT NULL DEFAULT 1,
            description TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (category, setting_key)
        )";
        
        $conn->exec($sql);
        echo "System settings table created successfully!\n";
    } else {
        echo "System settings table already exists.\n";
    }
    
    // Default settings
    $defaultSettings = [
        // General settings
        ['general', 'site_name', 'Brew & Bake', 'string', 1, 'The name of the website'],
        ['general', 'site_description', 'Artisanal coffee and baked goods', 'string', 1, 'Short description of the website'],
        ['general', 'contact_email', 'info@brewandbake.com', 'string', 1, 'Primary contact email address'],
        ['general', 'contact_phone', '+63 912 345 6789', 'string', 1, 'Primary contact phone number'],
        ['general', 'address', '123 Main Street, Manila, Philippines', 'string', 1, 'Physical address of the business'],
        ['general', 'business_hours', 'Monday - Sunday: 7:00 AM - 10:00 PM', 'string', 1, 'Business operating hours'],
        ['general', 'currency', 'PHP', 'string', 1, 'Default currency code'],
        ['general', 'currency_symbol', '₱', 'string', 1, 'Currency symbol'],
        ['general', 'tax_rate', '12', 'float', 1, 'Default tax rate percentage'],
        ['general', 'timezone', 'Asia/Manila', 'string', 1, 'Default timezone'],
        ['general', 'date_format', 'F j, Y', 'string', 1, 'Default date format (PHP date format)'],
        ['general', 'time_format', 'g:i A', 'string', 1, 'Default time format (PHP date format)'],
        
        // Email settings
        ['email', 'smtp_host', 'smtp.example.com', 'string', 0, 'SMTP server hostname'],
        ['email', 'smtp_port', '587', 'integer', 0, 'SMTP server port'],
        ['email', 'smtp_username', 'notifications@brewandbake.com', 'string', 0, 'SMTP username'],
        ['email', 'smtp_password', 'password123', 'string', 0, 'SMTP password'],
        ['email', 'smtp_encryption', 'tls', 'string', 0, 'SMTP encryption type (tls/ssl)'],
        ['email', 'from_email', 'no-reply@brewandbake.com', 'string', 0, 'Default from email address'],
        ['email', 'from_name', 'Brew & Bake', 'string', 0, 'Default from name'],
        
        // Payment settings
        ['payment', 'payment_methods', '["Cash","GCash","Maya","Bank Transfer"]', 'json', 1, 'Available payment methods'],
        ['payment', 'default_payment', 'Cash', 'string', 1, 'Default payment method'],
        ['payment', 'min_order_amount', '100', 'float', 1, 'Minimum order amount'],
        ['payment', 'delivery_fee', '50', 'float', 1, 'Standard delivery fee'],
        ['payment', 'free_delivery_threshold', '500', 'float', 1, 'Order amount for free delivery'],
        
        // Order settings
        ['order', 'order_prefix', 'BB-', 'string', 1, 'Prefix for order numbers'],
        ['order', 'enable_guest_checkout', '0', 'boolean', 1, 'Allow checkout without account'],
        ['order', 'default_order_status', 'pending', 'string', 1, 'Default status for new orders'],
        
        // Notification settings
        ['notification', 'order_notifications', '1', 'boolean', 0, 'Send notifications for new orders'],
        ['notification', 'inventory_alerts', '1', 'boolean', 0, 'Send alerts for low inventory'],
        ['notification', 'customer_feedback', '1', 'boolean', 0, 'Send notifications for customer feedback'],
        ['notification', 'marketing_updates', '0', 'boolean', 0, 'Send marketing updates'],
        ['notification', 'system_alerts', '1', 'boolean', 0, 'Send system alerts'],
        
        // Social media settings
        ['social', 'facebook_url', 'https://facebook.com/brewandbake', 'string', 1, 'Facebook page URL'],
        ['social', 'instagram_url', 'https://instagram.com/brewandbake', 'string', 1, 'Instagram profile URL'],
        ['social', 'twitter_url', '', 'string', 1, 'Twitter profile URL'],
        
        // System settings
        ['system', 'maintenance_mode', '0', 'boolean', 1, 'Enable maintenance mode'],
        ['system', 'cache_enabled', '1', 'boolean', 0, 'Enable settings cache'],
        ['system', 'cache_duration', '3600', 'integer', 0, 'Cache duration in seconds'],
        ['system', 'debug_mode', '0', 'boolean', 0, 'Enable debug mode'],
        ['system', 'pagination_limit', '10', 'integer', 1, 'Default items per page'],
        ['system', 'scheduled_backups', '0', 'boolean', 0, 'Enable scheduled backups'],
        ['system', 'backup_frequency', 'daily', 'string', 0, 'Backup frequency'],
        ['system', 'backup_time', '00:00', 'string', 0, 'Backup time']
    ];
    
    // Insert or update settings
    $insertStmt = $conn->prepare("
        INSERT INTO system_settings 
        (category, setting_key, setting_value, data_type, is_public, description) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        setting_value = VALUES(setting_value),
        data_type = VALUES(data_type),
        is_public = VALUES(is_public),
        description = VALUES(description)
    ");
    
    $insertCount = 0;
    $updateCount = 0;
    
    foreach ($defaultSettings as $setting) {
        // Check if setting already exists
        $checkStmt = $conn->prepare("
            SELECT id FROM system_settings
            WHERE category = ? AND setting_key = ?
        ");
        $checkStmt->execute([$setting[0], $setting[1]]);
        $exists = $checkStmt->fetchColumn();
        
        // Insert or update
        $insertStmt->execute($setting);
        
        if ($exists) {
            $updateCount++;
        } else {
            $insertCount++;
        }
    }
    
    echo "\nSettings inserted: $insertCount";
    echo "\nSettings updated: $updateCount";
    echo "\n\nDefault settings have been successfully inserted/updated!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
