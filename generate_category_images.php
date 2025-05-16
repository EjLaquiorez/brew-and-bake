<?php
// Script to generate placeholder category images with dark gray backgrounds
// This is a temporary solution until proper images are provided

// Configuration
$width = 400;
$height = 400;
$bgColor = [31, 41, 55]; // Dark gray 800
$textColor = [212, 212, 212]; // Light gray for text
$outputDir = 'assets/images/categories/';

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// List of categories to generate images for
$categories = [
    'coffee' => 'Coffee',
    'cake' => 'Cakes',
    'pastry' => 'Pastries',
    'drink' => 'Drinks',
    'dessert' => 'Desserts',
    'beverage' => 'Beverages',
    'sandwich' => 'Sandwiches',
    'tea' => 'Tea',
    'refresher' => 'Refreshers',
    'frappuccino' => 'Frappuccino',
    'energy' => 'Energy Drinks',
    'chocolate' => 'Hot Chocolate',
    'bottled' => 'Bottled Drinks',
    'breakfast' => 'Breakfast',
    'bakery' => 'Bakery',
    'treats' => 'Treats'
];

// Function to create an image with text
function createCategoryImage($width, $height, $bgColor, $textColor, $text, $outputPath) {
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Set background color
    $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    imagefill($image, 0, 0, $bg);
    
    // Set text color
    $color = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
    
    // Get font path - using default font since custom fonts might not be available
    $fontFile = __DIR__ . '/assets/fonts/arial.ttf';
    if (!file_exists($fontFile)) {
        // If the font doesn't exist, use a system font
        $fontFile = 'C:/Windows/Fonts/arial.ttf'; // Windows path
        if (!file_exists($fontFile)) {
            $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'; // Linux path
            if (!file_exists($fontFile)) {
                // If no font is found, we'll use the built-in font
                $fontSize = 5; // Built-in font size (1-5)
                
                // Calculate text dimensions
                $textWidth = imagefontwidth($fontSize) * strlen($text);
                $textHeight = imagefontheight($fontSize);
                
                // Center the text
                $x = ($width - $textWidth) / 2;
                $y = ($height - $textHeight) / 2;
                
                // Add text to image
                imagestring($image, $fontSize, $x, $y, $text, $color);
            } else {
                // Use TrueType font
                $fontSize = 24;
                
                // Get text dimensions
                $box = imagettfbbox($fontSize, 0, $fontFile, $text);
                $textWidth = abs($box[4] - $box[0]);
                $textHeight = abs($box[5] - $box[1]);
                
                // Center the text
                $x = ($width - $textWidth) / 2;
                $y = ($height + $textHeight) / 2;
                
                // Add text to image
                imagettftext($image, $fontSize, 0, $x, $y, $color, $fontFile, $text);
            }
        } else {
            // Use TrueType font
            $fontSize = 24;
            
            // Get text dimensions
            $box = imagettfbbox($fontSize, 0, $fontFile, $text);
            $textWidth = abs($box[4] - $box[0]);
            $textHeight = abs($box[5] - $box[1]);
            
            // Center the text
            $x = ($width - $textWidth) / 2;
            $y = ($height + $textHeight) / 2;
            
            // Add text to image
            imagettftext($image, $fontSize, 0, $x, $y, $color, $fontFile, $text);
        }
    } else {
        // Use TrueType font
        $fontSize = 24;
        
        // Get text dimensions
        $box = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);
        
        // Center the text
        $x = ($width - $textWidth) / 2;
        $y = ($height + $textHeight) / 2;
        
        // Add text to image
        imagettftext($image, $fontSize, 0, $x, $y, $color, $fontFile, $text);
    }
    
    // Save image
    imagejpeg($image, $outputPath, 90);
    
    // Free memory
    imagedestroy($image);
    
    return true;
}

// Generate images for each category
foreach ($categories as $key => $name) {
    $outputPath = $outputDir . $key . '-category.jpg';
    createCategoryImage($width, $height, $bgColor, $textColor, $name, $outputPath);
    echo "Generated: $outputPath\n";
}

echo "All category images have been generated successfully!\n";
?>
