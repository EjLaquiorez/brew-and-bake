<?php
/**
 * Settings API
 *
 * This file provides a centralized API for accessing and managing system settings
 */

// Include database connection if not already included
if (!isset($conn)) {
    require_once __DIR__ . "/db.php";
}

/**
 * Settings class for managing system settings
 */
class Settings {
    private $conn;
    private static $instance = null;
    private $settings = [];
    private $cache_enabled = true;
    private $cache_duration = 3600; // 1 hour in seconds
    private $cache_file;

    /**
     * Constructor
     *
     * @param PDO $conn Database connection
     */
    private function __construct($conn) {
        $this->conn = $conn;
        $this->cache_file = __DIR__ . '/../../cache/settings.cache';
        $this->loadSettings();
    }

    /**
     * Get singleton instance
     *
     * @param PDO $conn Database connection
     * @return Settings
     */
    public static function getInstance($conn) {
        if (self::$instance === null) {
            self::$instance = new self($conn);
        }
        return self::$instance;
    }

    /**
     * Load all settings from database or cache
     */
    private function loadSettings() {
        global $connection_type;

        // Check if cache is enabled and valid
        if ($this->cache_enabled && $this->isCacheValid()) {
            $this->loadFromCache();
            return;
        }

        try {
            if ($connection_type === 'pdo') {
                // Using PDO
                $stmt = $this->conn->query("SELECT * FROM system_settings");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Using mysqli
                $result = $this->conn->query("SELECT * FROM system_settings");
                $rows = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $rows[] = $row;
                    }
                    $result->free();
                }
            }

            foreach ($rows as $row) {
                $value = $this->parseValue($row['setting_value'], $row['data_type']);
                $this->settings[$row['category']][$row['setting_key']] = [
                    'value' => $value,
                    'data_type' => $row['data_type'],
                    'is_public' => (bool)$row['is_public'],
                    'description' => $row['description']
                ];
            }

            // Update cache settings
            if (isset($this->settings['system']['cache_enabled'])) {
                $this->cache_enabled = (bool)$this->settings['system']['cache_enabled']['value'];
            }

            if (isset($this->settings['system']['cache_duration'])) {
                $this->cache_duration = (int)$this->settings['system']['cache_duration']['value'];
            }

            // Save to cache if enabled
            if ($this->cache_enabled) {
                $this->saveToCache();
            }
        } catch (Exception $e) {
            error_log("Settings load error: " . $e->getMessage());
            // If there's an error, we'll return an empty array
            $this->settings = [];
        }
    }

    /**
     * Parse setting value based on data type
     *
     * @param string $value The raw value from database
     * @param string $type The data type
     * @return mixed The parsed value
     */
    private function parseValue($value, $type) {
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                return (bool)$value;
            case 'array':
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }

    /**
     * Format value for database storage
     *
     * @param mixed $value The value to format
     * @param string $type The data type
     * @return string The formatted value
     */
    private function formatValue($value, $type) {
        switch ($type) {
            case 'array':
            case 'json':
                return json_encode($value);
            case 'boolean':
                return $value ? '1' : '0';
            default:
                return (string)$value;
        }
    }

    /**
     * Check if cache file exists and is valid
     *
     * @return bool True if cache is valid
     */
    private function isCacheValid() {
        if (!file_exists($this->cache_file)) {
            return false;
        }

        $cache_time = filemtime($this->cache_file);
        return (time() - $cache_time) < $this->cache_duration;
    }

    /**
     * Load settings from cache file
     */
    private function loadFromCache() {
        $cache_data = file_get_contents($this->cache_file);
        $this->settings = unserialize($cache_data) ?: [];

        // Update cache settings
        if (isset($this->settings['system']['cache_enabled'])) {
            $this->cache_enabled = (bool)$this->settings['system']['cache_enabled']['value'];
        }

        if (isset($this->settings['system']['cache_duration'])) {
            $this->cache_duration = (int)$this->settings['system']['cache_duration']['value'];
        }
    }

    /**
     * Save settings to cache file
     */
    private function saveToCache() {
        // Create cache directory if it doesn't exist
        $cache_dir = dirname($this->cache_file);
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        file_put_contents($this->cache_file, serialize($this->settings));
    }

    /**
     * Clear the settings cache
     */
    public function clearCache() {
        if (file_exists($this->cache_file)) {
            unlink($this->cache_file);
        }
    }

    /**
     * Get a setting value
     *
     * @param string $key The setting key (can be in format 'category.key')
     * @param mixed $default Default value if setting not found
     * @param bool $public_only Whether to only return public settings
     * @return mixed The setting value or default
     */
    public function get($key, $default = null, $public_only = false) {
        global $default_settings;

        // Parse key in format 'category.key'
        $parts = explode('.', $key);

        if (count($parts) === 2) {
            $category = $parts[0];
            $setting_key = $parts[1];

            // Check if setting exists in database
            if (isset($this->settings[$category][$setting_key])) {
                $setting = $this->settings[$category][$setting_key];

                // Check if setting is public if public_only is true
                if ($public_only && !$setting['is_public']) {
                    // Fall back to default value
                    if ($default !== null) {
                        return $default;
                    }
                    // Check if there's a default setting
                    if (isset($default_settings[$category][$setting_key])) {
                        return $default_settings[$category][$setting_key];
                    }
                    return null;
                }

                return $setting['value'];
            }

            // If not in database, check default settings
            if (isset($default_settings[$category][$setting_key])) {
                return $default_settings[$category][$setting_key];
            }
        }

        return $default;
    }

    /**
     * Get all settings in a category
     *
     * @param string $category The category name
     * @param bool $public_only Whether to only return public settings
     * @return array The settings in the category
     */
    public function getCategory($category, $public_only = false) {
        global $default_settings;

        // Start with default values for this category
        $result = isset($default_settings[$category]) ? $default_settings[$category] : [];

        // If we have settings from the database, use those values
        if (isset($this->settings[$category])) {
            foreach ($this->settings[$category] as $key => $setting) {
                if (!$public_only || $setting['is_public']) {
                    $result[$key] = $setting['value'];
                }
            }
        }

        return $result;
    }

    /**
     * Get all settings
     *
     * @param bool $public_only Whether to only return public settings
     * @return array All settings
     */
    public function getAll($public_only = false) {
        if (!$public_only) {
            return $this->settings;
        }

        $result = [];
        foreach ($this->settings as $category => $settings) {
            $result[$category] = [];
            foreach ($settings as $key => $setting) {
                if ($setting['is_public']) {
                    $result[$category][$key] = $setting['value'];
                }
            }
        }

        return $result;
    }

    /**
     * Set a setting value
     *
     * @param string $category The setting category
     * @param string $key The setting key
     * @param mixed $value The new value
     * @param string $type The data type (optional)
     * @param bool $is_public Whether the setting is public (optional)
     * @param string $description Description of the setting (optional)
     * @return bool True if successful
     */
    public function set($category, $key, $value, $type = null, $is_public = null, $description = null) {
        global $connection_type, $default_settings;

        try {
            // Format value for storage
            if ($type === null) {
                // Try to determine type from default settings
                if (isset($default_settings[$category][$key])) {
                    $default_value = $default_settings[$category][$key];
                    $type = is_numeric($default_value) ? 'float' : (is_bool($default_value) ? 'boolean' : (is_array($default_value) ? 'json' : 'string'));
                } else {
                    // Determine type from value
                    $type = is_numeric($value) ? 'float' : (is_bool($value) ? 'boolean' : (is_array($value) ? 'json' : 'string'));
                }
            }

            if ($is_public === null) {
                $is_public = true;
            }

            if ($description === null) {
                $description = '';
            }

            // Format value for storage
            $formatted_value = $this->formatValue($value, $type);

            // Update in-memory settings
            $parsed_value = $this->parseValue($formatted_value, $type);
            $this->settings[$category][$key] = [
                'value' => $parsed_value,
                'data_type' => $type,
                'is_public' => (bool)$is_public,
                'description' => $description
            ];

            // Update cache if enabled
            if ($this->cache_enabled) {
                $this->saveToCache();
            }

            return true;
        } catch (Exception $e) {
            error_log("Settings update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a setting
     *
     * @param string $category The setting category
     * @param string $key The setting key
     * @return bool True if successful
     */
    public function delete($category, $key) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM system_settings
                WHERE category = ? AND setting_key = ?
            ");
            $stmt->execute([$category, $key]);

            // Remove from in-memory settings
            if (isset($this->settings[$category][$key])) {
                unset($this->settings[$category][$key]);
            }

            // Update cache if enabled
            if ($this->cache_enabled) {
                $this->saveToCache();
            }

            return true;
        } catch (PDOException $e) {
            error_log("Settings delete error: " . $e->getMessage());
            return false;
        }
    }
}

// Default settings values
$default_settings = [
    'general' => [
        'site_name' => 'Brew & Bake',
        'site_description' => 'Artisanal coffee and baked goods',
        'contact_email' => 'info@brewandbake.com',
        'contact_phone' => '+63 912 345 6789',
        'address' => '123 Main Street, Manila, Philippines',
        'business_hours' => 'Monday - Sunday: 7:00 AM - 10:00 PM',
        'currency' => 'PHP',
        'currency_symbol' => '₱',
        'tax_rate' => 12,
        'timezone' => 'Asia/Manila',
        'date_format' => 'F j, Y',
        'time_format' => 'g:i A'
    ],
    'email' => [
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_username' => 'notifications@brewandbake.com',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_email' => 'no-reply@brewandbake.com',
        'from_name' => 'Brew & Bake'
    ],
    'payment' => [
        'payment_methods' => ['Cash', 'GCash', 'Maya', 'Bank Transfer'],
        'default_payment' => 'Cash',
        'min_order_amount' => 100,
        'delivery_fee' => 50,
        'free_delivery_threshold' => 500
    ],
    'order' => [
        'order_prefix' => 'BB-',
        'enable_guest_checkout' => false,
        'default_order_status' => 'pending'
    ],
    'notification' => [
        'order_notifications' => true,
        'inventory_alerts' => true,
        'customer_feedback' => true,
        'marketing_updates' => false,
        'system_alerts' => true
    ],
    'social' => [
        'facebook_url' => 'https://facebook.com/brewandbake',
        'instagram_url' => 'https://instagram.com/brewandbake',
        'twitter_url' => ''
    ],
    'system' => [
        'maintenance_mode' => false,
        'cache_enabled' => true,
        'cache_duration' => 3600,
        'debug_mode' => false,
        'pagination_limit' => 10,
        'scheduled_backups' => false,
        'backup_frequency' => 'daily',
        'backup_time' => '00:00'
    ]
];

// Create global settings instance
$settings = Settings::getInstance($conn);

/**
 * Global function to get a setting value
 *
 * @param string $key The setting key (can be in format 'category.key')
 * @param mixed $default Default value if setting not found
 * @return mixed The setting value or default
 */
function get_setting($key, $default = null) {
    global $settings;
    return $settings->get($key, $default);
}

/**
 * Global function to set a setting value
 *
 * @param string $category The setting category
 * @param string $key The setting key
 * @param mixed $value The new value
 * @param string $type The data type (optional)
 * @return bool True if successful
 */
function set_setting($category, $key, $value, $type = null) {
    global $settings;
    return $settings->set($category, $key, $value, $type);
}
?>
