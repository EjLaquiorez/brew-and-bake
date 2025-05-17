<?php
/**
 * Generate CSS-based Product Placeholders
 * 
 * This script creates HTML/CSS-based placeholders for products
 * It doesn't require the GD library and works with any PHP installation
 */

// Configuration
$outputDir = 'assets/images/products/';

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Product categories with their background colors (in hex)
$categoryColors = [
    'coffee' => '#3c2a21',       // Dark coffee brown
    'cakes' => '#be90d4',        // Soft purple
    'pastries' => '#d6a92e',     // Warm gold
    'non-coffee' => '#3498db',   // Bright blue
    'sandwiches' => '#9b5923',   // Sandwich brown
    'baked' => '#e67e22'         // Orange
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
    
    // Add more products from other categories...
];

// Create a CSS file for the placeholders
$cssContent = "/* Product Placeholder Styles */
.product-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    font-family: 'Poppins', sans-serif;
    position: relative;
    overflow: hidden;
}

.product-placeholder::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 70%);
}

.product-placeholder i {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.product-placeholder span {
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
    padding: 0 1rem;
    position: relative;
    z-index: 1;
}
";

// Save CSS file
file_put_contents('assets/css/product-placeholders.css', $cssContent);

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Placeholders - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/product-placeholders.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 2rem;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .product-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .product-image {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .product-info {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .product-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .product-category {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .copy-btn {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSS-Based Product Placeholders</h1>
        <p>Since the GD library is not available, here are HTML/CSS-based placeholders for your products.</p>
        
        <div class="alert alert-info">
            <h4>How to Use These Placeholders</h4>
            <p>Copy the HTML code for each product and save it as a separate file in your <code>assets/images/products/</code> directory.</p>
            <p>Make sure to include the <code>product-placeholders.css</code> file in your pages.</p>
        </div>
        
        <h2>Product Placeholders</h2>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php 
                $bgColor = $categoryColors[$product['category']] ?? '#111827';
                $icon = $product['icon'] ?? 'bi-cup-hot';
                $name = $product['name'];
                $filename = $product['filename'];
                $category = ucfirst($product['category']);
                
                // Generate HTML for the placeholder
                $html = '<div class="product-placeholder" style="background-color: ' . $bgColor . ';">
    <i class="bi ' . $icon . '"></i>
    <span>' . $name . '</span>
</div>';
                
                // Encode for display
                $htmlEncoded = htmlspecialchars($html);
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <div class="product-placeholder" style="background-color: <?= $bgColor ?>; width: 150px; height: 150px;">
                            <i class="bi <?= $icon ?>"></i>
                            <span><?= $name ?></span>
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-title"><?= $name ?></div>
                        <div class="product-category"><?= $category ?></div>
                        <button class="btn btn-sm btn-outline-primary copy-btn" 
                                data-html="<?= $htmlEncoded ?>" 
                                data-filename="<?= $filename ?>">
                            Copy HTML
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        // Copy HTML to clipboard
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const html = this.getAttribute('data-html');
                const filename = this.getAttribute('data-filename');
                
                navigator.clipboard.writeText(html).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-success');
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
