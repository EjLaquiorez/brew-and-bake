<?php
/**
 * Script to update all admin pages with the new theme
 * This script adds the new CSS file reference to all admin pages
 */

// Define the directory containing admin pages
$adminDir = __DIR__ . '/templates/admin/';

// Get all PHP files in the admin directory
$adminFiles = glob($adminDir . '*.php');

// Define the old and new CSS link patterns
$oldPattern = '<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">';
$newPattern = '<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/admin-theme.css?v=<?= time() ?>">';

// Define the old and new session variable patterns
$oldSessionPattern = '$_SESSION[\'user\'][\'name\']';
$newSessionPattern = '$_SESSION[\'user_name\']';

// Counter for tracking changes
$updatedFiles = 0;
$skippedFiles = 0;
$errorFiles = [];

// Process each file
foreach ($adminFiles as $file) {
    // Skip test files and backup files
    if (strpos(basename($file), 'test-') === 0 || 
        strpos(basename($file), '_new') !== false ||
        basename($file) === 'check_db.php' ||
        basename($file) === 'delete_product.php') {
        echo "Skipping " . basename($file) . "...\n";
        $skippedFiles++;
        continue;
    }
    
    try {
        // Read the file content
        $content = file_get_contents($file);
        
        // Check if the file already has the new CSS link
        if (strpos($content, 'admin-theme.css') !== false) {
            echo basename($file) . " already has the new theme CSS.\n";
            $skippedFiles++;
            continue;
        }
        
        // Replace the CSS link
        $newContent = str_replace($oldPattern, $newPattern, $content);
        
        // Replace session variable references
        $newContent = str_replace($oldSessionPattern, $newSessionPattern, $newContent);
        
        // Write the updated content back to the file
        file_put_contents($file, $newContent);
        
        echo "Updated " . basename($file) . " successfully.\n";
        $updatedFiles++;
    } catch (Exception $e) {
        echo "Error updating " . basename($file) . ": " . $e->getMessage() . "\n";
        $errorFiles[] = basename($file);
    }
}

// Print summary
echo "\n=== Update Summary ===\n";
echo "Total files updated: $updatedFiles\n";
echo "Total files skipped: $skippedFiles\n";
echo "Total files with errors: " . count($errorFiles) . "\n";

if (count($errorFiles) > 0) {
    echo "Files with errors:\n";
    foreach ($errorFiles as $errorFile) {
        echo "- $errorFile\n";
    }
}

echo "\nUpdate complete!\n";
?>
