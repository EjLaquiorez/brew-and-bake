<?php
/**
 * AJAX endpoint for generating temporary product images
 * Returns JSON data for creating a temporary product image
 */

// Include the product helpers
require_once "product_helpers.php";

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters
$productName = isset($_GET['name']) ? $_GET['name'] : '';
$categoryName = isset($_GET['category']) ? $_GET['category'] : '';

// Validate input
if (empty($productName)) {
    echo json_encode([
        'success' => false,
        'message' => 'Product name is required'
    ]);
    exit;
}

// Generate temporary product image data
$imageData = generateTempProductImage($productName, $categoryName);

// Return the image data as JSON
echo json_encode([
    'success' => true,
    'type' => $imageData['type'],
    'icon' => $imageData['icon'],
    'background' => $imageData['background'],
    'text' => $imageData['text'],
    'initials' => $imageData['initials']
]);
