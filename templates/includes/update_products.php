<?php
require_once "db.php";

// First, check if the products table exists
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
    $tableExists = $tableCheck->rowCount() > 0;

    if (!$tableExists) {
        // Create the products table if it doesn't exist
        $conn->exec("
            CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(255),
                stock INT DEFAULT 0,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "Products table created successfully!<br>";
    } else {
        // Check if there are transaction_items referencing products
        $checkTransactionItems = $conn->query("SHOW TABLES LIKE 'transaction_items'")->rowCount() > 0;

        if ($checkTransactionItems) {
            $checkReferences = $conn->query("SELECT COUNT(*) FROM transaction_items")->fetchColumn();
        } else {
            $checkReferences = 0;
        }

        if ($checkReferences > 0) {
            // Method 3: Preserve transaction_items by updating existing products
            echo "Transaction items found. Using UPDATE method to preserve references.<br>";

            // Get existing product IDs
            $existingProducts = $conn->query("SELECT id FROM products")->fetchAll(PDO::FETCH_COLUMN);

            if (count($existingProducts) > 0) {
                echo "Found " . count($existingProducts) . " existing products to update.<br>";
            } else {
                echo "No existing products found to update.<br>";
            }
        } else {
            try {
                // Method 1: Using TRUNCATE with foreign key checks disabled
                // Temporarily disable foreign key checks
                $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

                // Clear existing products for demo purposes
                $conn->exec("TRUNCATE TABLE products");

                // Re-enable foreign key checks
                $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

                echo "Existing products cleared using TRUNCATE.<br>";
            } catch (PDOException $e) {
                // Method 2: If TRUNCATE fails, try using DELETE
                echo "TRUNCATE failed, trying DELETE instead.<br>";
                $conn->exec("DELETE FROM products");
                echo "Existing products cleared using DELETE.<br>";
            }
        }
    }

    // Modern coffee products
    $coffeeProducts = [
        [
            'name' => 'Single Origin Ethiopian Yirgacheffe',
            'description' => 'Bright and fruity with notes of blueberry, citrus, and floral undertones. Sourced directly from small-scale farmers in Ethiopia.',
            'category' => 'coffee',
            'price' => 195.00,
            'stock' => 50,
            'image' => 'ethiopian_coffee.jpg'
        ],
        [
            'name' => 'Specialty Cold Brew',
            'description' => 'Smooth, low-acidity cold brew steeped for 18 hours with hints of chocolate and caramel. Served with your choice of milk or black.',
            'category' => 'coffee',
            'price' => 175.00,
            'stock' => 35,
            'image' => 'cold_brew.jpg'
        ],
        [
            'name' => 'Nitro Coffee',
            'description' => 'Our signature cold brew infused with nitrogen for a creamy, stout-like texture with a beautiful cascading effect.',
            'category' => 'coffee',
            'price' => 210.00,
            'stock' => 25,
            'image' => 'nitro_coffee.jpg'
        ],
        [
            'name' => 'Barako Espresso',
            'description' => 'Traditional Filipino Liberica coffee with a bold, earthy flavor profile. Perfect as a strong espresso shot.',
            'category' => 'coffee',
            'price' => 120.00,
            'stock' => 60,
            'image' => 'barako_espresso.jpg'
        ],
        [
            'name' => 'Oat Milk Latte',
            'description' => 'Creamy plant-based latte made with premium oat milk and a double shot of our house blend espresso.',
            'category' => 'coffee',
            'price' => 185.00,
            'stock' => 40,
            'image' => 'oat_latte.jpg'
        ],
        [
            'name' => 'Dirty Matcha Latte',
            'description' => 'Ceremonial grade matcha layered with a shot of espresso and steamed milk for the perfect balance of earthy and rich flavors.',
            'category' => 'coffee',
            'price' => 205.00,
            'stock' => 30,
            'image' => 'dirty_matcha.jpg'
        ],
        [
            'name' => 'Spanish Latte',
            'description' => 'A sweet and creamy latte made with condensed milk and a hint of cinnamon. A perfect afternoon treat.',
            'category' => 'coffee',
            'price' => 165.00,
            'stock' => 45,
            'image' => 'spanish_latte.jpg'
        ]
    ];

    // Modern pastry products
    $pastryProducts = [
        [
            'name' => 'Sourdough Croissant',
            'description' => 'Flaky, buttery croissant made with our 3-day fermented sourdough starter for extra depth of flavor.',
            'category' => 'pastry',
            'price' => 95.00,
            'stock' => 24,
            'image' => 'sourdough_croissant.jpg'
        ],
        [
            'name' => 'Ube Cheese Pandesal',
            'description' => 'Soft purple yam bread rolls filled with melty cheese. A modern twist on a Filipino classic.',
            'category' => 'pastry',
            'price' => 35.00,
            'stock' => 50,
            'image' => 'ube_pandesal.jpg'
        ],
        [
            'name' => 'Bacon Kimchi Danish',
            'description' => 'Savory danish with crispy bacon, aged cheddar, and homemade kimchi for a fusion of flavors.',
            'category' => 'pastry',
            'price' => 125.00,
            'stock' => 18,
            'image' => 'kimchi_danish.jpg'
        ],
        [
            'name' => 'Gluten-Free Banana Bread',
            'description' => 'Moist banana bread made with almond and coconut flour, sweetened with coconut sugar and topped with walnuts.',
            'category' => 'pastry',
            'price' => 155.00,
            'stock' => 15,
            'image' => 'gf_banana_bread.jpg'
        ],
        [
            'name' => 'Calamansi Tart',
            'description' => 'Tangy local calamansi citrus curd in a buttery shortbread crust topped with torched meringue.',
            'category' => 'pastry',
            'price' => 165.00,
            'stock' => 12,
            'image' => 'calamansi_tart.jpg'
        ]
    ];

    // Modern cake products
    $cakeProducts = [
        [
            'name' => 'Basque Burnt Cheesecake',
            'description' => 'Creamy cheesecake with a caramelized top that\'s intentionally "burnt" for a rustic, smoky flavor contrast.',
            'category' => 'cake',
            'price' => 225.00,
            'stock' => 8,
            'image' => 'basque_cheesecake.jpg'
        ],
        [
            'name' => 'Ube Leche Flan Cake',
            'description' => 'Three-layer cake with ube chiffon, creamy leche flan, and ube halaya frosting. A Filipino favorite reinvented.',
            'category' => 'cake',
            'price' => 350.00,
            'stock' => 5,
            'image' => 'ube_flan_cake.jpg'
        ],
        [
            'name' => 'Vegan Chocolate Ganache Cake',
            'description' => 'Rich chocolate cake made with aquafaba and coconut cream, topped with silky dark chocolate ganache. 100% plant-based.',
            'category' => 'cake',
            'price' => 295.00,
            'stock' => 6,
            'image' => 'vegan_chocolate.jpg'
        ],
        [
            'name' => 'Mango Coconut Tres Leches',
            'description' => 'Light sponge cake soaked in three milks, layered with fresh Philippine mangoes and coconut cream.',
            'category' => 'cake',
            'price' => 275.00,
            'stock' => 7,
            'image' => 'mango_tres_leches.jpg'
        ]
    ];

    // Modern sandwich products
    $sandwichProducts = [
        [
            'name' => 'Sourdough Grilled Cheese',
            'description' => 'Artisanal sourdough bread with a blend of aged cheddar, gruyere, and mozzarella cheeses, served with homemade tomato soup.',
            'category' => 'sandwich',
            'price' => 245.00,
            'stock' => 15,
            'image' => 'grilled_cheese.jpg'
        ],
        [
            'name' => 'Pulled Jackfruit BBQ',
            'description' => 'Plant-based BBQ sandwich with young jackfruit, homemade coleslaw, and pickled red onions on a brioche bun.',
            'category' => 'sandwich',
            'price' => 225.00,
            'stock' => 12,
            'image' => 'jackfruit_bbq.jpg'
        ],
        [
            'name' => 'Chicken Pesto Panini',
            'description' => 'Grilled free-range chicken, homemade basil pesto, roasted red peppers, and fresh mozzarella pressed in ciabatta bread.',
            'category' => 'sandwich',
            'price' => 265.00,
            'stock' => 18,
            'image' => 'pesto_panini.jpg'
        ],
        [
            'name' => 'Banh Mi Fusion',
            'description' => 'Vietnamese-inspired sandwich with lemongrass pork belly, pickled vegetables, cilantro, and sriracha mayo on a crusty baguette.',
            'category' => 'sandwich',
            'price' => 235.00,
            'stock' => 10,
            'image' => 'banh_mi.jpg'
        ]
    ];

    // Modern beverage products
    $beverageProducts = [
        [
            'name' => 'Butterfly Pea Lemonade',
            'description' => 'Color-changing iced tea made with butterfly pea flower and calamansi, creating a magical purple to pink effect.',
            'category' => 'beverage',
            'price' => 155.00,
            'stock' => 25,
            'image' => 'butterfly_lemonade.jpg'
        ],
        [
            'name' => 'Kombucha on Tap',
            'description' => 'Locally brewed probiotic fermented tea with seasonal flavors like mango ginger or strawberry basil.',
            'category' => 'beverage',
            'price' => 185.00,
            'stock' => 20,
            'image' => 'kombucha.jpg'
        ],
        [
            'name' => 'Coconut Cold Brew',
            'description' => 'Cold brew coffee mixed with coconut water and a hint of vanilla for a refreshing, naturally sweet drink.',
            'category' => 'beverage',
            'price' => 165.00,
            'stock' => 30,
            'image' => 'coconut_coldbrew.jpg'
        ],
        [
            'name' => 'Turmeric Ginger Latte',
            'description' => 'Anti-inflammatory golden milk made with fresh turmeric, ginger, black pepper, and your choice of milk.',
            'category' => 'beverage',
            'price' => 175.00,
            'stock' => 22,
            'image' => 'turmeric_latte.jpg'
        ],
        [
            'name' => 'Sparkling Hibiscus Tea',
            'description' => 'Refreshing sparkling water infused with hibiscus flowers, lemongrass, and a touch of honey.',
            'category' => 'beverage',
            'price' => 145.00,
            'stock' => 28,
            'image' => 'hibiscus_tea.jpg'
        ]
    ];

    // Combine all products
    $allProducts = array_merge($coffeeProducts, $pastryProducts, $cakeProducts, $sandwichProducts, $beverageProducts);

    // Check if we're updating existing products or inserting new ones
    if (isset($existingProducts) && count($existingProducts) > 0) {
        // Method 3: Update existing products
        $updateStmt = $conn->prepare("
            UPDATE products
            SET name = ?, description = ?, category = ?, price = ?, stock = ?, image = ?, status = 'active'
            WHERE id = ?
        ");

        $insertStmt = $conn->prepare("
            INSERT INTO products (name, description, category, price, stock, image, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $updateCount = 0;
        $insertCount = 0;

        foreach ($allProducts as $index => $product) {
            if (isset($existingProducts[$index])) {
                // Update existing product
                $updateStmt->execute([
                    $product['name'],
                    $product['description'],
                    $product['category'],
                    $product['price'],
                    $product['stock'],
                    $product['image'],
                    $existingProducts[$index]
                ]);
                $updateCount++;
            } else {
                // Insert new product
                $insertStmt->execute([
                    $product['name'],
                    $product['description'],
                    $product['category'],
                    $product['price'],
                    $product['stock'],
                    $product['image']
                ]);
                $insertCount++;
            }
        }

        echo "Successfully updated $updateCount existing products and added $insertCount new products to the database!";
    } else {
        // Method 1 or 2: Insert all new products
        $stmt = $conn->prepare("
            INSERT INTO products (name, description, category, price, stock, image, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $insertCount = 0;
        foreach ($allProducts as $product) {
            $stmt->execute([
                $product['name'],
                $product['description'],
                $product['category'],
                $product['price'],
                $product['stock'],
                $product['image']
            ]);
            $insertCount++;
        }

        echo "Successfully added $insertCount modern products to the database!";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="mt-4">
    <a href="../admin/products.php" class="btn btn-primary">Go to Products Page</a>
</div>
