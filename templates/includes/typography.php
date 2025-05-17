<?php
/**
 * Typography Helper Functions
 * 
 * This file contains helper functions for consistent text formatting
 * throughout the Brew & Bake application.
 */

/**
 * Format a title with proper capitalization
 * 
 * @param string $title The title to format
 * @return string The formatted title
 */
function format_title($title) {
    // Words that should not be capitalized (articles, conjunctions, prepositions)
    $minor_words = ['a', 'an', 'the', 'and', 'but', 'or', 'for', 'nor', 'on', 'at', 'to', 'from', 'by', 'with', 'in', 'of'];
    
    // Split the title into words
    $words = explode(' ', strtolower($title));
    
    // Capitalize each word unless it's a minor word (except for the first word)
    foreach ($words as $key => $word) {
        if ($key === 0 || !in_array($word, $minor_words)) {
            $words[$key] = ucfirst($word);
        }
    }
    
    // Join the words back together
    return implode(' ', $words);
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
 * Format a date in a readable format
 * 
 * @param string $date The date to format (MySQL date format)
 * @param string $format The format to use (optional)
 * @return string The formatted date
 */
function format_date($date, $format = null) {
    if (empty($date)) {
        return 'N/A';
    }
    
    // Get date format from settings if not specified
    if ($format === null) {
        $format = get_setting('general.date_format', 'F j, Y');
    }
    
    // Convert to timestamp
    $timestamp = strtotime($date);
    
    // Format the date
    return date($format, $timestamp);
}

/**
 * Format a time in a readable format
 * 
 * @param string $time The time to format (MySQL time format)
 * @param string $format The format to use (optional)
 * @return string The formatted time
 */
function format_time($time, $format = null) {
    if (empty($time)) {
        return 'N/A';
    }
    
    // Get time format from settings if not specified
    if ($format === null) {
        $format = get_setting('general.time_format', 'g:i A');
    }
    
    // Convert to timestamp
    $timestamp = strtotime($time);
    
    // Format the time
    return date($format, $timestamp);
}

/**
 * Format a datetime in a readable format
 * 
 * @param string $datetime The datetime to format (MySQL datetime format)
 * @param string $format The format to use (optional)
 * @return string The formatted datetime
 */
function format_datetime($datetime, $format = null) {
    if (empty($datetime)) {
        return 'N/A';
    }
    
    // Get date and time formats from settings if not specified
    if ($format === null) {
        $date_format = get_setting('general.date_format', 'F j, Y');
        $time_format = get_setting('general.time_format', 'g:i A');
        $format = $date_format . ' ' . $time_format;
    }
    
    // Convert to timestamp
    $timestamp = strtotime($datetime);
    
    // Format the datetime
    return date($format, $timestamp);
}

/**
 * Format a number with commas for thousands
 * 
 * @param int $number The number to format
 * @param int $decimals The number of decimal places (optional)
 * @return string The formatted number
 */
function format_number($number, $decimals = 0) {
    return number_format($number, $decimals);
}

/**
 * Truncate text to a specified length
 * 
 * @param string $text The text to truncate
 * @param int $length The maximum length
 * @param string $suffix The suffix to add if truncated (optional)
 * @return string The truncated text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    // Truncate the text
    $text = substr($text, 0, $length);
    
    // Find the last space to avoid cutting words
    $last_space = strrpos($text, ' ');
    if ($last_space !== false) {
        $text = substr($text, 0, $last_space);
    }
    
    // Add the suffix
    return $text . $suffix;
}

/**
 * Convert plain text to HTML with paragraphs and line breaks
 * 
 * @param string $text The text to convert
 * @return string The HTML-formatted text
 */
function text_to_html($text) {
    // Convert line breaks to <br> tags
    $text = nl2br(htmlspecialchars($text));
    
    // Wrap in paragraphs
    $paragraphs = explode('<br /><br />', $text);
    $html = '';
    
    foreach ($paragraphs as $paragraph) {
        if (trim($paragraph) !== '') {
            $html .= '<p>' . $paragraph . '</p>';
        }
    }
    
    return $html;
}

/**
 * Format a phone number for display
 * 
 * @param string $phone The phone number to format
 * @return string The formatted phone number
 */
function format_phone($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format based on length (Philippines format)
    if (strlen($phone) === 10) {
        // 9XXXXXXXXX format
        return '+63 ' . substr($phone, 0, 1) . ' ' . substr($phone, 1, 3) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 4);
    } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        // 09XXXXXXXXX format
        return '+63 ' . substr($phone, 1, 1) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8, 3);
    } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '63') {
        // 639XXXXXXXXX format
        return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 1) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 3) . ' ' . substr($phone, 9, 3);
    } elseif (strlen($phone) === 13 && substr($phone, 0, 3) === '+63') {
        // +639XXXXXXXXX format
        return substr($phone, 0, 3) . ' ' . substr($phone, 3, 1) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 3) . ' ' . substr($phone, 10, 3);
    }
    
    // Return as is if format not recognized
    return $phone;
}
?>
