-- SQL script to update products for Brew & Bake with Filipino menu items
-- This script will handle foreign key constraints and update the products

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing products
TRUNCATE TABLE products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert coffee products
INSERT INTO products (name, description, category, price, stock, image, status) VALUES
('Kapeng Barako', 'Strong and aromatic Filipino coffee made from Liberica beans, known for its bold flavor and high caffeine content.', 'coffee', 120.00, 50, 'kapeng_barako.jpg', 'active'),
('Tablea Hot Chocolate', 'Traditional Filipino hot chocolate made from pure cacao tablets, rich and bittersweet with a hint of nuttiness.', 'coffee', 130.00, 45, 'tablea_chocolate.jpg', 'active'),
('Civet Coffee (Kape Alamid)', 'Premium exotic coffee made from beans that have been eaten and digested by civet cats, resulting in a smooth, less acidic flavor.', 'coffee', 450.00, 15, 'civet_coffee.jpg', 'active'),
('Iced Caramel Macchiato', 'Espresso combined with vanilla syrup, milk and ice, topped with caramel drizzle for a sweet, refreshing treat.', 'coffee', 160.00, 40, 'caramel_macchiato.jpg', 'active'),
('Ube Latte', 'Espresso blended with steamed milk and purple yam (ube) syrup, creating a uniquely Filipino coffee experience with subtle sweetness.', 'coffee', 170.00, 35, 'ube_latte.jpg', 'active'),
('Espresso Con Panna', 'A shot of rich espresso topped with a dollop of whipped cream, offering a perfect balance of bitter and sweet.', 'coffee', 140.00, 30, 'espresso_panna.jpg', 'active'),
('Affogato', 'A scoop of vanilla ice cream "drowned" with a shot of hot espresso, creating a delightful hot-and-cold dessert beverage.', 'coffee', 180.00, 25, 'affogato.jpg', 'active'),
('Café Mocha', 'A chocolate lover\'s dream combining espresso, steamed milk, and chocolate syrup, topped with whipped cream.', 'coffee', 160.00, 40, 'cafe_mocha.jpg', 'active'),
('Cold Brew', 'Coffee steeped in cold water for 12-24 hours, resulting in a smooth, less acidic brew served over ice.', 'coffee', 150.00, 35, 'cold_brew.jpg', 'active'),
('Latte Art Coffee', 'Expertly crafted latte with beautiful designs created in the milk foam, as pleasing to the eye as it is to the palate.', 'coffee', 180.00, 30, 'latte_art.jpg', 'active');

-- Insert pastry products
INSERT INTO products (name, description, category, price, stock, image, status) VALUES
('Ensaymada Classic', 'Soft, buttery Filipino brioche pastry coiled into a spiral, topped with butter, sugar, and grated cheese.', 'pastry', 60.00, 40, 'ensaymada.jpg', 'active'),
('Ube Cheese Pandesal', 'Purple yam-flavored soft bread rolls with a cheese filling, a modern twist on the Filipino breakfast staple.', 'pastry', 50.00, 50, 'ube_pandesal.jpg', 'active'),
('Pan de Sal (Bag)', 'Traditional Filipino bread rolls with a slightly sweet taste and soft, airy texture, perfect for breakfast.', 'pastry', 40.00, 60, 'pandesal.jpg', 'active'),
('Bibingka Delight', 'Rice cake traditionally cooked in clay pots lined with banana leaves, topped with butter, cheese, and salted egg.', 'pastry', 120.00, 30, 'bibingka.jpg', 'active'),
('Buko Pie Slice', 'Young coconut custard filling in a flaky pie crust, a specialty from the Laguna province.', 'pastry', 150.00, 25, 'buko_pie.jpg', 'active'),
('Cassava Cake', 'Moist and chewy cake made from grated cassava, coconut milk, and condensed milk with a creamy custard topping.', 'pastry', 100.00, 35, 'cassava_cake.jpg', 'active'),
('Leche Flan', 'Silky smooth Filipino caramel custard made with egg yolks, condensed milk, and caramelized sugar.', 'pastry', 120.00, 30, 'leche_flan.jpg', 'active'),
('Mamon Supreme', 'Light and fluffy Filipino sponge cake topped with butter and sugar, with a melt-in-your-mouth texture.', 'pastry', 70.00, 40, 'mamon.jpg', 'active'),
('Spanish Bread', 'Soft bread rolls filled with a buttery, sweet filling and rolled in breadcrumbs before baking.', 'pastry', 40.00, 45, 'spanish_bread.jpg', 'active'),
('Cheese Roll', 'Soft bread roll filled with butter and cheese, then rolled in breadcrumbs and sugar for a sweet-savory treat.', 'pastry', 60.00, 40, 'cheese_roll.jpg', 'active');
