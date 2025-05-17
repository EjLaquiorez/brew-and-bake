<?php
/**
 * Settings Fallback
 * 
 * This file provides a fallback for the settings system when the database is not available
 * It uses hardcoded default values for all settings
 */

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

/**
 * Get a setting value
 * 
 * @param string $key The setting key (can be in format 'category.key')
 * @param mixed $default Default value if setting not found
 * @return mixed The setting value or default
 */
function get_setting($key, $default = null) {
    global $default_settings;
    
    // Parse key in format 'category.key'
    $parts = explode('.', $key);
    
    if (count($parts) === 2) {
        $category = $parts[0];
        $setting_key = $parts[1];
        
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
 * @return array The settings in the category
 */
function get_category_settings($category) {
    global $default_settings;
    
    if (isset($default_settings[$category])) {
        return $default_settings[$category];
    }
    
    return [];
}

/**
 * Get the currency symbol based on the currency code
 * 
 * @param string $currency_code The currency code (e.g., PHP, USD)
 * @return string The currency symbol
 */
function get_currency_symbol($currency_code = null) {
    if ($currency_code === null) {
        $currency_code = get_setting('general.currency', 'PHP');
    }
    
    $symbols = [
        'PHP' => '₱',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥'
    ];
    
    return $symbols[$currency_code] ?? $currency_code;
}

/**
 * Format a price with the currency symbol
 * 
 * @param float $price The price to format
 * @param string $currency_code The currency code (optional)
 * @return string The formatted price with currency symbol
 */
function format_price($price, $currency_code = null) {
    $symbol = get_currency_symbol($currency_code);
    return $symbol . number_format($price, 2);
}

/**
 * Get the site name
 * 
 * @return string The site name
 */
function get_site_name() {
    return get_setting('general.site_name', 'Brew & Bake');
}

/**
 * Get the site description
 * 
 * @return string The site description
 */
function get_site_description() {
    return get_setting('general.site_description', 'Artisanal coffee and baked goods');
}

/**
 * Get the contact email
 * 
 * @return string The contact email
 */
function get_contact_email() {
    return get_setting('general.contact_email', 'info@brewandbake.com');
}

/**
 * Get the contact phone
 * 
 * @return string The contact phone
 */
function get_contact_phone() {
    return get_setting('general.contact_phone', '+63 912 345 6789');
}

/**
 * Get the business address
 * 
 * @return string The business address
 */
function get_business_address() {
    return get_setting('general.address', '123 Main Street, Manila, Philippines');
}

/**
 * Get the business hours
 * 
 * @return string The business hours
 */
function get_business_hours() {
    return get_setting('general.business_hours', 'Monday - Sunday: 7:00 AM - 10:00 PM');
}

/**
 * Check if maintenance mode is enabled
 * 
 * @return bool True if maintenance mode is enabled
 */
function is_maintenance_mode() {
    return (bool)get_setting('system.maintenance_mode', false);
}

/**
 * Get social media URL
 * 
 * @param string $platform The social media platform (facebook, instagram, twitter)
 * @return string The social media URL
 */
function get_social_url($platform) {
    return get_setting("social.{$platform}_url", '');
}

// Create a settings class that mimics the real settings class
class Settings {
    private static $instance = null;
    private $settings = [];
    
    private function __construct() {
        global $default_settings;
        $this->settings = $default_settings;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        return get_setting($key, $default);
    }
    
    public function getCategory($category) {
        return get_category_settings($category);
    }
    
    public function set($category, $key, $value) {
        // This is a fallback, so we don't actually save anything
        // Just update the in-memory settings
        $this->settings[$category][$key] = $value;
        return true;
    }
    
    public function clearCache() {
        // Do nothing in fallback mode
        return true;
    }
}

// Create global settings instance
$settings = Settings::getInstance();
?>
