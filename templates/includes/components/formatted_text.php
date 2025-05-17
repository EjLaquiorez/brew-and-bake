<?php
/**
 * Formatted Text Component
 * 
 * This component provides consistent text formatting with proper spacing,
 * line height, and other readability enhancements.
 * 
 * Usage:
 * include 'templates/includes/components/formatted_text.php';
 * 
 * Parameters:
 * - $text (string): The text content to format
 * - $format (string, optional): The format type ('paragraph', 'heading', 'list', 'quote')
 * - $level (int, optional): Heading level (1-6) for 'heading' format
 * - $class (string, optional): Additional CSS classes
 * - $style (string, optional): Additional inline styles
 * - $id (string, optional): ID attribute
 */

// Default values
$text = $text ?? '';
$format = $format ?? 'paragraph';
$level = $level ?? 2;
$class = $class ?? '';
$style = $style ?? '';
$id = $id ?? '';

// ID attribute
$id_attr = !empty($id) ? ' id="' . htmlspecialchars($id) . '"' : '';

// Style attribute
$style_attr = !empty($style) ? ' style="' . htmlspecialchars($style) . '"' : '';

// Format the text based on the specified format
switch ($format) {
    case 'heading':
        // Ensure level is between 1 and 6
        $level = max(1, min(6, $level));
        
        // Add appropriate heading classes
        $heading_class = 'heading-' . $level;
        $combined_class = trim($heading_class . ' ' . $class);
        
        // Output the heading
        echo "<h{$level} class=\"{$combined_class}\"{$id_attr}{$style_attr}>" . htmlspecialchars($text) . "</h{$level}>";
        break;
        
    case 'list':
        // Determine if it's an ordered or unordered list
        $list_type = isset($list_type) && $list_type === 'ordered' ? 'ol' : 'ul';
        
        // Add appropriate list classes
        $list_class = 'formatted-list';
        $combined_class = trim($list_class . ' ' . $class);
        
        // Start the list
        echo "<{$list_type} class=\"{$combined_class}\"{$id_attr}{$style_attr}>";
        
        // Process list items
        if (is_array($text)) {
            foreach ($text as $item) {
                echo "<li>" . htmlspecialchars($item) . "</li>";
            }
        } else {
            // Split text by new lines if it's a string
            $items = explode("\n", $text);
            foreach ($items as $item) {
                if (trim($item) !== '') {
                    echo "<li>" . htmlspecialchars(trim($item)) . "</li>";
                }
            }
        }
        
        // End the list
        echo "</{$list_type}>";
        break;
        
    case 'quote':
        // Add appropriate quote classes
        $quote_class = 'formatted-quote';
        $combined_class = trim($quote_class . ' ' . $class);
        
        // Output the blockquote
        echo "<blockquote class=\"{$combined_class}\"{$id_attr}{$style_attr}>";
        echo "<p>" . htmlspecialchars($text) . "</p>";
        
        // Add citation if provided
        if (isset($citation) && !empty($citation)) {
            echo "<footer class=\"blockquote-footer\">" . htmlspecialchars($citation) . "</footer>";
        }
        
        echo "</blockquote>";
        break;
        
    case 'paragraph':
    default:
        // Add appropriate paragraph classes
        $paragraph_class = 'formatted-paragraph';
        $combined_class = trim($paragraph_class . ' ' . $class);
        
        // Process paragraphs
        $paragraphs = explode("\n\n", $text);
        foreach ($paragraphs as $paragraph) {
            if (trim($paragraph) !== '') {
                echo "<p class=\"{$combined_class}\"{$id_attr}{$style_attr}>" . nl2br(htmlspecialchars(trim($paragraph))) . "</p>";
            }
        }
        break;
}
?>
