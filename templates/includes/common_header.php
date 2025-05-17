<?php
/**
 * Common Header
 *
 * This file contains common header code that should be included at the top of all pages.
 * It loads essential components like database connection, authentication, and settings.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . "/db.php";

// Include authentication functions
require_once __DIR__ . "/auth.php";

// Try to include the regular settings file
$use_fallback = false;
try {
    require_once __DIR__ . "/settings.php";
} catch (Exception $e) {
    // If there's an error, use the fallback settings
    $use_fallback = true;
    require_once __DIR__ . "/settings_fallback.php";
}

// Set timezone based on settings
$timezone = get_setting('general.timezone', 'Asia/Manila');
date_default_timezone_set($timezone);

// Include typography helpers
require_once __DIR__ . "/typography.php";

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
?>
