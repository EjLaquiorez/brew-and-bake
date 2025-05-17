<?php
/**
 * Product Helper Functions
 * Contains utility functions for product-related operations
 */

/**
 * Generate a temporary product image based on product name and category
 * 
 * @param string $productName The name of the product
 * @param string $categoryName The category name of the product
 * @param string $fallbackIcon The fallback icon to use if no specific icon is found
 * @return array An array containing image details (type, icon, background, text)
 */
function generateTempProductImage($productName, $categoryName, $fallbackIcon = 'bi-cup-hot') {
    // Normalize inputs
    $productName = strtolower(trim($productName));
    $categoryName = strtolower(trim($categoryName));
    
    // Default values
    $result = [
        'type' => 'icon',
        'icon' => $fallbackIcon,
        'background' => '#111827', // Dark gray (primary color)
        'text' => '#f59e0b', // Secondary color
        'initials' => strtoupper(substr($productName, 0, 2))
    ];
    
    // Coffee-related products
    if (strpos($categoryName, 'coffee') !== false || 
        strpos($productName, 'coffee') !== false || 
        strpos($productName, 'espresso') !== false || 
        strpos($productName, 'latte') !== false || 
        strpos($productName, 'cappuccino') !== false || 
        strpos($productName, 'mocha') !== false) {
        $result['icon'] = 'bi-cup-hot';
        $result['background'] = '#3c2a21'; // Coffee brown
        return $result;
    }
    
    // Tea-related products
    if (strpos($categoryName, 'tea') !== false || 
        strpos($productName, 'tea') !== false || 
        strpos($productName, 'chai') !== false) {
        $result['icon'] = 'bi-cup';
        $result['background'] = '#1e5128'; // Tea green
        return $result;
    }
    
    // Cold drinks
    if (strpos($categoryName, 'cold') !== false || 
        strpos($productName, 'cold') !== false || 
        strpos($productName, 'iced') !== false || 
        strpos($productName, 'frappe') !== false || 
        strpos($productName, 'smoothie') !== false || 
        strpos($productName, 'milkshake') !== false) {
        $result['icon'] = 'bi-cup-straw';
        $result['background'] = '#0c4a6e'; // Cool blue
        return $result;
    }
    
    // Cakes
    if (strpos($categoryName, 'cake') !== false || 
        strpos($productName, 'cake') !== false || 
        strpos($productName, 'cheesecake') !== false) {
        $result['icon'] = 'bi-cake2';
        $result['background'] = '#be185d'; // Cake pink
        return $result;
    }
    
    // Pastries
    if (strpos($categoryName, 'pastry') !== false || 
        strpos($categoryName, 'pastries') !== false || 
        strpos($productName, 'croissant') !== false || 
        strpos($productName, 'danish') !== false || 
        strpos($productName, 'muffin') !== false || 
        strpos($productName, 'scone') !== false) {
        $result['icon'] = 'bi-basket';
        $result['background'] = '#92400e'; // Pastry brown
        return $result;
    }
    
    // Bread and sandwiches
    if (strpos($categoryName, 'bread') !== false || 
        strpos($categoryName, 'sandwich') !== false || 
        strpos($productName, 'bread') !== false || 
        strpos($productName, 'sandwich') !== false || 
        strpos($productName, 'toast') !== false) {
        $result['icon'] = 'bi-baguette';
        $result['background'] = '#854d0e'; // Bread golden brown
        return $result;
    }
    
    // Desserts
    if (strpos($categoryName, 'dessert') !== false || 
        strpos($productName, 'dessert') !== false || 
        strpos($productName, 'pudding') !== false || 
        strpos($productName, 'ice cream') !== false || 
        strpos($productName, 'chocolate') !== false) {
        $result['icon'] = 'bi-emoji-heart-eyes';
        $result['background'] = '#7e22ce'; // Sweet purple
        return $result;
    }
    
    // Breakfast
    if (strpos($categoryName, 'breakfast') !== false || 
        strpos($productName, 'breakfast') !== false || 
        strpos($productName, 'egg') !== false || 
        strpos($productName, 'bacon') !== false || 
        strpos($productName, 'pancake') !== false || 
        strpos($productName, 'waffle') !== false) {
        $result['icon'] = 'bi-egg-fried';
        $result['background'] = '#b45309'; // Breakfast orange
        return $result;
    }
    
    // If no specific category is matched, use the first two letters of the product name
    $result['type'] = 'text';
    
    return $result;
}

/**
 * Generate HTML for a temporary product image
 * 
 * @param string $productName The name of the product
 * @param string $categoryName The category name of the product
 * @return string HTML for the temporary product image
 */
function getTempProductImageHtml($productName, $categoryName) {
    $imageData = generateTempProductImage($productName, $categoryName);
    
    if ($imageData['type'] === 'icon') {
        return '<div class="temp-product-image" style="background-color: ' . $imageData['background'] . ';">
                    <i class="bi ' . $imageData['icon'] . '" style="color: ' . $imageData['text'] . ';"></i>
                </div>';
    } else {
        return '<div class="temp-product-image" style="background-color: ' . $imageData['background'] . ';">
                    <span style="color: ' . $imageData['text'] . ';">' . $imageData['initials'] . '</span>
                </div>';
    }
}
