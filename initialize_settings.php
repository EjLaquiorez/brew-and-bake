<?php
/**
 * Initialize Settings
 *
 * This script creates the system_settings table and inserts default values
 */

// Set content type to plain text for better readability in browser
header('Content-Type: text/plain');

// Include database connection
require_once "templates/includes/db.php";

// Check if the system_settings table exists
try {
    // Try to create the system_settings table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
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

    // Execute the query
    if (is_object($conn) && get_class($conn) === 'PDO') {
        $conn->exec($sql);
    } else {
        $conn->query($sql);
    }

    echo "System settings table created/verified successfully!\n";

    // Include the default settings
    require_once "templates/includes/settings_fallback.php";

    // Insert default settings into the database
    $insertCount = 0;
    $updateCount = 0;

    foreach ($default_settings as $category => $settings) {
        foreach ($settings as $key => $value) {
            // Determine data type
            $type = 'string';
            if (is_numeric($value)) {
                $type = is_int($value) ? 'integer' : 'float';
            } elseif (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_array($value)) {
                $type = 'json';
                $value = json_encode($value);
            }

            // Format boolean values
            if ($type === 'boolean') {
                $value = $value ? '1' : '0';
            }

            // Escape values for SQL
            if (is_object($conn) && get_class($conn) === 'PDO') {
                $escapedCategory = $conn->quote($category);
                $escapedKey = $conn->quote($key);
                $escapedValue = $conn->quote($value);
                $escapedType = $conn->quote($type);
                $escapedDescription = $conn->quote(ucfirst(str_replace('_', ' ', $key)));
            } else {
                // Simple escaping for mysqli
                $escapedCategory = "'" . addslashes($category) . "'";
                $escapedKey = "'" . addslashes($key) . "'";
                $escapedValue = "'" . addslashes($value) . "'";
                $escapedType = "'" . addslashes($type) . "'";
                $escapedDescription = "'" . addslashes(ucfirst(str_replace('_', ' ', $key))) . "'";
            }

            // Check if setting exists
            $checkSql = "SELECT id FROM system_settings WHERE category = $escapedCategory AND setting_key = $escapedKey";

            // Execute the query and check if we got results
            $checkResult = $conn->query($checkSql);
            $exists = (bool)$checkResult && $checkResult !== false;

            if ($exists) {
                // Update existing setting
                $updateSql = "UPDATE system_settings
                              SET setting_value = $escapedValue,
                                  data_type = $escapedType
                              WHERE category = $escapedCategory
                              AND setting_key = $escapedKey";

                if (is_object($conn) && get_class($conn) === 'PDO') {
                    $conn->exec($updateSql);
                } else {
                    $conn->query($updateSql);
                }

                $updateCount++;
            } else {
                // Insert new setting
                $insertSql = "INSERT INTO system_settings
                              (category, setting_key, setting_value, data_type, is_public, description)
                              VALUES ($escapedCategory, $escapedKey, $escapedValue, $escapedType, 1, $escapedDescription)";

                if (is_object($conn) && get_class($conn) === 'PDO') {
                    $conn->exec($insertSql);
                } else {
                    $conn->query($insertSql);
                }

                $insertCount++;
            }
        }
    }

    echo "\nSettings inserted: $insertCount";
    echo "\nSettings updated: $updateCount";
    echo "\n\nDefault settings have been successfully initialized!";
    echo "\n\nYou can now access the settings page at: templates/admin/settings.php";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
