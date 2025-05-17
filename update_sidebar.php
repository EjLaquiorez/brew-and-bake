<?php
// List of files to update
$files = [
    'templates/admin/admin_new.php',
    'templates/admin/analytics.php',
    'templates/admin/categories.php',
    'templates/admin/manage_product_images.php',
    'templates/admin/products.php',
    'templates/admin/profile.php',
    'templates/admin/sales.php',
    'templates/admin/settings.php',
    'templates/admin/test-sidebar.php',
    'templates/admin/view_product.php'
];

// Process each file
foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    echo "Processing $file...\n";
    
    // Read the file content
    $content = file_get_contents($file);
    
    // Find the sidebar section
    $pattern = '/<aside class="admin-sidebar">.*?<div class="sidebar-footer">.*?<\/aside>/s';
    
    // Replace with the include statement
    $replacement = '<?php include \'includes/sidebar.php\'; ?>';
    
    // Perform the replacement
    $newContent = preg_replace($pattern, $replacement, $content);
    
    // Check if replacement was successful
    if ($newContent !== $content) {
        // Write the updated content back to the file
        file_put_contents($file, $newContent);
        echo "Updated $file successfully\n";
    } else {
        echo "No changes made to $file\n";
    }
}

echo "All files processed.\n";
?>
