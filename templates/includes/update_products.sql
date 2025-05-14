-- SQL script to update products for Brew & Bake
-- This script will create the products table if it doesn't exist,
-- clear existing products, and insert modern product data

-- Create products table if it doesn't exist
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT NOT NULL, -- category column changed to category_id
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    stock INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) -- Add foreign key constraint
);

-- IMPORTANT: Choose one of the methods below based on your situation

-- Method 1: Using TRUNCATE with foreign key checks disabled
-- Use this if you don't have transaction_items referencing products
-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing products
TRUNCATE TABLE products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Alternative Method 2: Using DELETE (safer but slower)
-- If Method 1 fails, comment it out and uncomment the line below
-- DELETE FROM products;

-- Alternative Method 3: Preserve transaction_items by updating existing products
-- If you have transaction_items referencing products, comment out Methods 1 and 2,
-- and use the following approach instead:
/*
-- First, get the count of existing products
SET @product_count = (SELECT COUNT(*) FROM products);

-- If there are no products, we don't need to do anything special
-- If there are products, we'll update them instead of deleting
-- This preserves foreign key relationships

-- For each existing product, we'll update it with new data
-- You'll need to manually map which products to update
-- Example:
UPDATE products SET
    name = 'Single Origin Ethiopian Yirgacheffe',
    description = 'Bright and fruity with notes of blueberry, citrus, and floral undertones. Sourced directly from small-scale farmers in Ethiopia.',
    category_id = (SELECT id FROM categories WHERE name = 'Coffee'),
    price = 195.00,
    stock = 50,
    image = 'ethiopian_coffee.jpg',
    status = 'active'
WHERE id = 1;  -- Update the first product

-- For additional products beyond what exists, use INSERT
-- You'll need to check how many products exist first
*/

-- Insert modern coffee products
INSERT INTO products (name, description, category_id, price, stock, image, status) VALUES
('Single Origin Ethiopian Yirgacheffe', 'Bright and fruity with notes of blueberry, citrus, and floral undertones. Sourced directly from small-scale farmers in Ethiopia.', (SELECT id FROM categories WHERE name = 'Coffee'), 195.00, 50, 'ethiopian_coffee.jpg', 'active'),
('Specialty Cold Brew', 'Smooth, low-acidity cold brew steeped for 18 hours with hints of chocolate and caramel. Served with your choice of milk or black.', (SELECT id FROM categories WHERE name = 'Coffee'), 175.00, 35, 'cold_brew.jpg', 'active'),
('Nitro Coffee', 'Our signature cold brew infused with nitrogen for a creamy, stout-like texture with a beautiful cascading effect.', (SELECT id FROM categories WHERE name = 'Coffee'), 210.00, 25, 'nitro_coffee.jpg', 'active'),
('Barako Espresso', 'Traditional Filipino Liberica coffee with a bold, earthy flavor profile. Perfect as a strong espresso shot.', (SELECT id FROM categories WHERE name = 'Coffee'), 120.00, 60, 'barako_espresso.jpg', 'active'),
('Oat Milk Latte', 'Creamy plant-based latte made with premium oat milk and a double shot of our house blend espresso.', (SELECT id FROM categories WHERE name = 'Coffee'), 185.00, 40, 'oat_latte.jpg', 'active'),
('Dirty Matcha Latte', 'Ceremonial grade matcha layered with a shot of espresso and steamed milk for the perfect balance of earthy and rich flavors.', (SELECT id FROM categories WHERE name = 'Coffee'), 205.00, 30, 'dirty_matcha.jpg', 'active'),
('Spanish Latte', 'A sweet and creamy latte made with condensed milk and a hint of cinnamon. A perfect afternoon treat.', (SELECT id FROM categories WHERE name = 'Coffee'), 165.00, 45, 'spanish_latte.jpg', 'active');

-- Insert modern pastry products
INSERT INTO products (name, description, category_id, price, stock, image, status) VALUES
('Sourdough Croissant', 'Flaky, buttery croissant made with our 3-day fermented sourdough starter for extra depth of flavor.', (SELECT id FROM categories WHERE name = 'Pastry'), 95.00, 24, 'sourdough_croissant.jpg', 'active'),
('Ube Cheese Pandesal', 'Soft purple yam bread rolls filled with melty cheese. A modern twist on a Filipino classic.', (SELECT id FROM categories WHERE name = 'Pastry'), 35.00, 50, 'ube_pandesal.jpg', 'active'),
('Bacon Kimchi Danish', 'Savory danish with crispy bacon, aged cheddar, and homemade kimchi for a fusion of flavors.', (SELECT id FROM categories WHERE name = 'Pastry'), 125.00, 18, 'kimchi_danish.jpg', 'active'),
('Gluten-Free Banana Bread', 'Moist banana bread made with almond and coconut flour, sweetened with coconut sugar and topped with walnuts.', (SELECT id FROM categories WHERE name = 'Pastry'), 155.00, 15, 'gf_banana_bread.jpg', 'active'),
('Calamansi Tart', 'Tangy local calamansi citrus curd in a buttery shortbread crust topped with torched meringue.', (SELECT id FROM categories WHERE name = 'Pastry'), 165.00, 12, 'calamansi_tart.jpg', 'active');

-- Insert modern cake products
INSERT INTO products (name, description, category_id, price, stock, image, status) VALUES
('Basque Burnt Cheesecake', 'Creamy cheesecake with a caramelized top that\'s intentionally "burnt" for a rustic, smoky flavor contrast.', (SELECT id FROM categories WHERE name = 'Cake'), 225.00, 8, 'basque_cheesecake.jpg', 'active'),
('Ube Leche Flan Cake', 'Three-layer cake with ube chiffon, creamy leche flan, and ube halaya frosting. A Filipino favorite reinvented.', (SELECT id FROM categories WHERE name = 'Cake'), 350.00, 5, 'ube_flan_cake.jpg', 'active'),
('Vegan Chocolate Ganache Cake', 'Rich chocolate cake made with aquafaba and coconut cream, topped with silky dark chocolate ganache. 100% plant-based.', (SELECT id FROM categories WHERE name = 'Cake'), 295.00, 6, 'vegan_chocolate.jpg', 'active'),
('Mango Coconut Tres Leches', 'Light sponge cake soaked in three milks, layered with fresh Philippine mangoes and coconut cream.', (SELECT id FROM categories WHERE name = 'Cake'), 275.00, 7, 'mango_tres_leches.jpg', 'active');

-- Insert modern sandwich products
INSERT INTO products (name, description, category_id, price, stock, image, status) VALUES
('Sourdough Grilled Cheese', 'Artisanal sourdough bread with a blend of aged cheddar, gruyere, and mozzarella cheeses, served with homemade tomato soup.', (SELECT id FROM categories WHERE name = 'Sandwich'), 245.00, 15, 'grilled_cheese.jpg', 'active'),
('Pulled Jackfruit BBQ', 'Plant-based BBQ sandwich with young jackfruit, homemade coleslaw, and pickled red onions on a brioche bun.', (SELECT id FROM categories WHERE name = 'Sandwich'), 225.00, 12, 'jackfruit_bbq.jpg', 'active'),
('Chicken Pesto Panini', 'Grilled free-range chicken, homemade basil pesto, roasted red peppers, and fresh mozzarella pressed in ciabatta bread.', (SELECT id FROM categories WHERE name = 'Sandwich'), 265.00, 18, 'pesto_panini.jpg', 'active'),
('Banh Mi Fusion', 'Vietnamese-inspired sandwich with lemongrass pork belly, pickled vegetables, cilantro, and sriracha mayo on a crusty baguette.', (SELECT id FROM categories WHERE name = 'Sandwich'), 235.00, 10, 'banh_mi.jpg', 'active');

-- Insert modern beverage products
INSERT INTO products (name, description, category_id, price, stock, image, status) VALUES
('Butterfly Pea Lemonade', 'Color-changing iced tea made with butterfly pea flower and calamansi, creating a magical purple to pink effect.', (SELECT id FROM categories WHERE name = 'Beverage'), 155.00, 25, 'butterfly_lemonade.jpg', 'active'),
('Kombucha on Tap', 'Locally brewed probiotic fermented tea with seasonal flavors like mango ginger or strawberry basil.', (SELECT id FROM categories WHERE name = 'Beverage'), 185.00, 20, 'kombucha.jpg', 'active'),
('Coconut Cold Brew', 'Cold brew coffee mixed with coconut water and a hint of vanilla for a refreshing, naturally sweet drink.', (SELECT id FROM categories WHERE name = 'Beverage'), 165.00, 30, 'coconut_coldbrew.jpg', 'active'),
('Turmeric Ginger Latte', 'Anti-inflammatory golden milk made with fresh turmeric, ginger, black pepper, and your choice of milk.', (SELECT id FROM categories WHERE name = 'Beverage'), 175.00, 22, 'turmeric_latte.jpg', 'active'),
('Sparkling Hibiscus Tea', 'Refreshing sparkling water infused with hibiscus flowers, lemongrass, and a touch of honey.', (SELECT id FROM categories WHERE name = 'Beverage'), 145.00, 28, 'hibiscus_tea.jpg', 'active');
