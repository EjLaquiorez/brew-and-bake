<?php
/**
 * Script to check if all admin pages include the welcome card
 */

// Define the directory containing admin pages
$adminDir = __DIR__ . '/templates/admin/';

// Get all PHP files in the admin directory
$adminFiles = glob($adminDir . '*.php');

// Counter for tracking
$hasWelcomeCard = [];
$noWelcomeCard = [];
$skippedFiles = [];

// Process each file
foreach ($adminFiles as $file) {
    // Skip test files and backup files
    if (strpos(basename($file), 'test-') === 0 || 
        strpos(basename($file), '_new') !== false ||
        basename($file) === 'check_db.php' ||
        basename($file) === 'delete_product.php' ||
        basename($file) === 'process_product_image.php') {
        $skippedFiles[] = basename($file);
        continue;
    }
    
    // Read the file content
    $content = file_get_contents($file);
    
    // Check if the file includes the welcome card
    if (strpos($content, 'welcome-card.php') !== false || 
        strpos($content, 'class="welcome-card"') !== false) {
        $hasWelcomeCard[] = basename($file);
    } else {
        $noWelcomeCard[] = basename($file);
    }
}

// Print summary
echo "=== Welcome Card Check ===\n";
echo "Files with welcome card (" . count($hasWelcomeCard) . "):\n";
foreach ($hasWelcomeCard as $file) {
    echo "- $file\n";
}

echo "\nFiles without welcome card (" . count($noWelcomeCard) . "):\n";
foreach ($noWelcomeCard as $file) {
    echo "- $file\n";
}

echo "\nSkipped files (" . count($skippedFiles) . "):\n";
foreach ($skippedFiles as $file) {
    echo "- $file\n";
}
?>
