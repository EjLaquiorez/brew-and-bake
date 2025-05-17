<?php
/**
 * Script to add the welcome card to all admin pages that don't have it
 */

// Define the directory containing admin pages
$adminDir = __DIR__ . '/templates/admin/';

// Get all PHP files in the admin directory
$adminFiles = glob($adminDir . '*.php');

// Define the pattern to look for (after the alerts section)
$pattern = '            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Error</h6>
                            <p class="alert-text"><?= htmlspecialchars($errorMessage) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>';

// Define the welcome card code to insert
$welcomeCardCode = '            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Error</h6>
                            <p class="alert-text"><?= htmlspecialchars($errorMessage) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Include Welcome Card -->
            <?php include \'includes/welcome-card.php\'; ?>';

// Counter for tracking changes
$updatedFiles = 0;
$skippedFiles = 0;
$errorFiles = [];

// Process each file
foreach ($adminFiles as $file) {
    // Skip test files, backup files, and files that already have the welcome card
    if (strpos(basename($file), 'test-') === 0 || 
        strpos(basename($file), '_new') !== false ||
        basename($file) === 'check_db.php' ||
        basename($file) === 'delete_product.php' ||
        basename($file) === 'process_product_image.php') {
        echo "Skipping " . basename($file) . "...\n";
        $skippedFiles++;
        continue;
    }
    
    try {
        // Read the file content
        $content = file_get_contents($file);
        
        // Check if the file already has the welcome card
        if (strpos($content, 'welcome-card.php') !== false || 
            strpos($content, 'class="welcome-card"') !== false) {
            echo basename($file) . " already has the welcome card.\n";
            $skippedFiles++;
            continue;
        }
        
        // Replace the pattern with the welcome card code
        $newContent = str_replace($pattern, $welcomeCardCode, $content);
        
        // Check if the replacement was successful
        if ($newContent === $content) {
            echo "Could not find the insertion point in " . basename($file) . ".\n";
            $errorFiles[] = basename($file);
            continue;
        }
        
        // Write the updated content back to the file
        file_put_contents($file, $newContent);
        
        echo "Added welcome card to " . basename($file) . " successfully.\n";
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
