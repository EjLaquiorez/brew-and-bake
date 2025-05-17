<?php
/**
 * Generate Product Placeholder Images
 *
 * This script generates placeholder images for products based on the product_image_guide.md
 * It creates simple, visually appealing placeholder images until actual product photos are available.
 */

// Check if GD library is installed
if (!extension_loaded('gd')) {
    echo "<h1>GD Library Not Available</h1>";
    echo "<p>The GD library is required for image generation but is not enabled in your PHP installation.</p>";
    echo "<h2>Options:</h2>";
    echo "<ol>";
    echo "<li><strong>Enable GD Library:</strong> Contact your server administrator to enable the GD library in PHP.</li>";
    echo "<li><strong>Use Pre-made Images:</strong> Download pre-made placeholder images from a stock photo site.</li>";
    echo "<li><strong>Use CSS Placeholders:</strong> Use CSS to create colored blocks with text as placeholders.</li>";
    echo "</ol>";

    echo "<h2>How to Enable GD Library:</h2>";
    echo "<p>For XAMPP users:</p>";
    echo "<ol>";
    echo "<li>Open php.ini file (usually in C:\\xampp\\php\\php.ini)</li>";
    echo "<li>Find the line <code>;extension=gd</code> and remove the semicolon at the beginning</li>";
    echo "<li>Save the file and restart Apache</li>";
    echo "</ol>";

    echo "<h2>Alternative: Create HTML/CSS Placeholders</h2>";
    echo "<p>You can use HTML and CSS to create simple placeholders. Here's a sample code:</p>";
    echo "<pre>";
    echo htmlspecialchars('<div class="product-placeholder" style="width: 200px; height: 200px; background-color: #3c2a21; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
    <span>Espresso</span>
</div>');
    echo "</pre>";

    exit;
}

// Configuration
$width = 800;
$height = 800;
$outputDir = 'assets/images/products/';
$fontFile = 'assets/fonts/Poppins-Bold.ttf'; // Make sure this font exists

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Product categories with their background colors
$categoryColors = [
    'coffee' => [49, 46, 43],       // Dark coffee brown
    'cakes' => [190, 144, 212],     // Soft purple
    'pastries' => [214, 158, 46],   // Warm gold
    'non-coffee' => [52, 152, 219], // Bright blue
    'sandwiches' => [155, 89, 35],  // Sandwich brown
    'baked' => [230, 126, 34]       // Orange
];

// Product list from the guide
$products = [
    // Coffee
    ['name' => 'Espresso', 'filename' => 'espresso.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Americano', 'filename' => 'americano.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Cappuccino', 'filename' => 'cappuccino.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Latte', 'filename' => 'latte.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Mocha', 'filename' => 'mocha.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Caramel Macchiato', 'filename' => 'caramel-macchiato.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Flat White', 'filename' => 'flat-white.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Cold Brew', 'filename' => 'cold-brew.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Iced Coffee', 'filename' => 'iced-coffee.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Filipino Barako', 'filename' => 'filipino-barako.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Vanilla Latte', 'filename' => 'vanilla-latte.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],
    ['name' => 'Hazelnut Coffee', 'filename' => 'hazelnut-coffee.png', 'category' => 'coffee', 'icon' => 'bi-cup-hot'],

    // Cakes
    ['name' => 'Chocolate Cake', 'filename' => 'chocolate-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Red Velvet Cake', 'filename' => 'red-velvet-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Carrot Cake', 'filename' => 'carrot-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Cheesecake', 'filename' => 'cheesecake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Ube Cake', 'filename' => 'ube-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Mango Cake', 'filename' => 'mango-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Tiramisu', 'filename' => 'tiramisu.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Black Forest Cake', 'filename' => 'black-forest-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Leche Flan Cake', 'filename' => 'leche-flan-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],
    ['name' => 'Buko Pandan Cake', 'filename' => 'buko-pandan-cake.png', 'category' => 'cakes', 'icon' => 'bi-cake2'],

    // Pastries
    ['name' => 'Croissant', 'filename' => 'croissant.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Ensaymada', 'filename' => 'ensaymada.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Cinnamon Roll', 'filename' => 'cinnamon-roll.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Danish Pastry', 'filename' => 'danish-pastry.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Pandesal', 'filename' => 'pandesal.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Cheese Bread', 'filename' => 'cheese-bread.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Chocolate Muffin', 'filename' => 'chocolate-muffin.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Blueberry Muffin', 'filename' => 'blueberry-muffin.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Banana Bread', 'filename' => 'banana-bread.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Hopia', 'filename' => 'hopia.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Spanish Bread', 'filename' => 'spanish-bread.png', 'category' => 'pastries', 'icon' => 'bi-basket'],
    ['name' => 'Egg Tart', 'filename' => 'egg-tart.png', 'category' => 'pastries', 'icon' => 'bi-basket'],

    // Non-Coffee Drinks
    ['name' => 'Hot Chocolate', 'filename' => 'hot-chocolate.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Matcha Latte', 'filename' => 'matcha-latte.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Chai Tea Latte', 'filename' => 'chai-tea-latte.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Iced Tea', 'filename' => 'iced-tea.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Fruit Smoothie', 'filename' => 'fruit-smoothie.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Mango Shake', 'filename' => 'mango-shake.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Strawberry Shake', 'filename' => 'strawberry-shake.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Chocolate Milkshake', 'filename' => 'chocolate-milkshake.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Buko Juice', 'filename' => 'buko-juice.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Calamansi Juice', 'filename' => 'calamansi-juice.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Sago\'t Gulaman', 'filename' => 'sagot-gulaman.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],
    ['name' => 'Melon Juice', 'filename' => 'melon-juice.png', 'category' => 'non-coffee', 'icon' => 'bi-cup-straw'],

    // Sandwiches
    ['name' => 'Club Sandwich', 'filename' => 'club-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Grilled Cheese', 'filename' => 'grilled-cheese.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Chicken Sandwich', 'filename' => 'chicken-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Tuna Sandwich', 'filename' => 'tuna-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Egg Sandwich', 'filename' => 'egg-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Ham and Cheese', 'filename' => 'ham-cheese-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Vegetable Sandwich', 'filename' => 'vegetable-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'BLT Sandwich', 'filename' => 'blt-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Panini', 'filename' => 'panini.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],
    ['name' => 'Beef Sandwich', 'filename' => 'beef-sandwich.png', 'category' => 'sandwiches', 'icon' => 'bi-baguette'],

    // Other Baked Goods
    ['name' => 'Chocolate Chip Cookie', 'filename' => 'chocolate-chip-cookie.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Oatmeal Cookie', 'filename' => 'oatmeal-cookie.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Brownie', 'filename' => 'brownie.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Cupcake', 'filename' => 'cupcake.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Donut', 'filename' => 'donut.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Bibingka', 'filename' => 'bibingka.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Puto', 'filename' => 'puto.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Empanada', 'filename' => 'empanada.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Siopao', 'filename' => 'siopao.png', 'category' => 'baked', 'icon' => 'bi-basket'],
    ['name' => 'Pianono', 'filename' => 'pianono.png', 'category' => 'baked', 'icon' => 'bi-basket']
];

/**
 * Create a placeholder image for a product
 *
 * @param int $width Image width
 * @param int $height Image height
 * @param array $bgColor Background color [r, g, b]
 * @param string $productName Product name
 * @param string $outputPath Output file path
 * @param string $fontFile Path to font file
 * @return bool Success status
 */
function createProductImage($width, $height, $bgColor, $productName, $outputPath, $fontFile = null) {
    // Create image
    $image = imagecreatetruecolor($width, $height);

    // Enable alpha channel
    imagesavealpha($image, true);

    // Create transparent background
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);

    // Create a circular background
    $bgColorAllocated = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    $textColor = imagecolorallocate($image, 255, 255, 255);

    // Draw circle
    $centerX = $width / 2;
    $centerY = $height / 2;
    $radius = min($width, $height) * 0.4;

    // Fill circle
    imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $bgColorAllocated);

    // Add product name text
    $fontSize = 30;
    $textBounds = imagettfbbox($fontSize, 0, $fontFile, $productName);
    $textWidth = $textBounds[2] - $textBounds[0];
    $textHeight = $textBounds[1] - $textBounds[7];
    $textX = ($width - $textWidth) / 2;
    $textY = $centerY + $radius + $textHeight + 20;

    // Draw text
    if ($fontFile && file_exists($fontFile)) {
        imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $fontFile, $productName);
    } else {
        // Fallback to built-in font
        $fontSize = 5; // Built-in font size
        $textWidth = strlen($productName) * imagefontwidth($fontSize);
        $textX = ($width - $textWidth) / 2;
        imagestring($image, $fontSize, $textX, $textY - 20, $productName, $textColor);
    }

    // Save image
    imagepng($image, $outputPath);

    // Free memory
    imagedestroy($image);

    return true;
}

// Generate images for each product
$generatedCount = 0;
foreach ($products as $product) {
    $outputPath = $outputDir . $product['filename'];

    // Skip if file already exists
    if (file_exists($outputPath)) {
        echo "Skipping existing file: $outputPath\n";
        continue;
    }

    // Get background color for category
    $bgColor = $categoryColors[$product['category']] ?? [100, 100, 100];

    // Create image
    if (createProductImage($width, $height, $bgColor, $product['name'], $outputPath, $fontFile)) {
        echo "Generated: $outputPath\n";
        $generatedCount++;
    } else {
        echo "Failed to generate: $outputPath\n";
    }
}

echo "Generated $generatedCount product images successfully!\n";
echo "Remember to replace these placeholder images with actual product photos when available.\n";
?>
