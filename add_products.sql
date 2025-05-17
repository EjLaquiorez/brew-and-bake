-- Add Products SQL Script for Brew & Bake
-- This script adds sample products to the database with appropriate categories

-- Make sure categories exist first
INSERT IGNORE INTO categories (name, description) VALUES 
('Coffee', 'Various coffee drinks and beans'),
('Cakes', 'Delicious cakes for all occasions'),
('Pastries', 'Freshly baked pastries and bread'),
('Non-Coffee Drinks', 'Cold and hot non-coffee beverages'),
('Sandwiches', 'Freshly made gourmet sandwiches'),
('Other Baked Goods', 'Various baked treats and desserts');

-- Get category IDs
SET @coffee_id = (SELECT id FROM categories WHERE name = 'Coffee' LIMIT 1);
SET @cakes_id = (SELECT id FROM categories WHERE name = 'Cakes' LIMIT 1);
SET @pastries_id = (SELECT id FROM categories WHERE name = 'Pastries' LIMIT 1);
SET @drinks_id = (SELECT id FROM categories WHERE name = 'Non-Coffee Drinks' LIMIT 1);
SET @sandwiches_id = (SELECT id FROM categories WHERE name = 'Sandwiches' LIMIT 1);
SET @baked_id = (SELECT id FROM categories WHERE name = 'Other Baked Goods' LIMIT 1);

-- Add Coffee Products
INSERT INTO products (name, description, price, stock, image, category_id, status) VALUES
('Espresso', 'Single shot of concentrated coffee', 85.00, 100, 'espresso.png', @coffee_id, 'active'),
('Americano', 'Espresso diluted with hot water', 95.00, 100, 'americano.png', @coffee_id, 'active'),
('Cappuccino', 'Espresso with steamed milk and foam', 120.00, 100, 'cappuccino.png', @coffee_id, 'active'),
('Latte', 'Espresso with steamed milk', 130.00, 100, 'latte.png', @coffee_id, 'active'),
('Mocha', 'Espresso with chocolate and steamed milk', 140.00, 100, 'mocha.png', @coffee_id, 'active'),
('Caramel Macchiato', 'Vanilla-flavored espresso with caramel drizzle', 150.00, 100, 'caramel-macchiato.png', @coffee_id, 'active'),
('Flat White', 'Espresso with steamed milk', 125.00, 100, 'flat-white.png', @coffee_id, 'active'),
('Cold Brew', 'Coffee brewed with cold water', 135.00, 100, 'cold-brew.png', @coffee_id, 'active'),
('Iced Coffee', 'Chilled coffee with ice', 115.00, 100, 'iced-coffee.png', @coffee_id, 'active'),
('Filipino Barako', 'Strong local coffee variety', 100.00, 100, 'filipino-barako.png', @coffee_id, 'active'),
('Vanilla Latte', 'Latte with vanilla flavoring', 140.00, 100, 'vanilla-latte.png', @coffee_id, 'active'),
('Hazelnut Coffee', 'Coffee with hazelnut flavoring', 145.00, 100, 'hazelnut-coffee.png', @coffee_id, 'active');

-- Add Cake Products
INSERT INTO products (name, description, price, stock, image, category_id, status) VALUES
('Chocolate Cake', 'Rich chocolate layer cake', 180.00, 20, 'chocolate-cake.png', @cakes_id, 'active'),
('Red Velvet Cake', 'Red-colored cake with cream cheese frosting', 190.00, 20, 'red-velvet-cake.png', @cakes_id, 'active'),
('Carrot Cake', 'Spiced cake with carrots and cream cheese frosting', 175.00, 20, 'carrot-cake.png', @cakes_id, 'active'),
('Cheesecake', 'Classic creamy cheesecake', 200.00, 20, 'cheesecake.png', @cakes_id, 'active'),
('Ube Cake', 'Filipino purple yam cake', 185.00, 20, 'ube-cake.png', @cakes_id, 'active'),
('Mango Cake', 'Fresh mango cream cake', 195.00, 20, 'mango-cake.png', @cakes_id, 'active'),
('Tiramisu', 'Coffee-flavored Italian dessert', 210.00, 20, 'tiramisu.png', @cakes_id, 'active'),
('Black Forest Cake', 'Chocolate cake with cherries and cream', 200.00, 20, 'black-forest-cake.png', @cakes_id, 'active'),
('Leche Flan Cake', 'Cake topped with caramel custard', 190.00, 20, 'leche-flan-cake.png', @cakes_id, 'active'),
('Buko Pandan Cake', 'Coconut pandan-flavored cake', 185.00, 20, 'buko-pandan-cake.png', @cakes_id, 'active');

-- Add Pastry Products
INSERT INTO products (name, description, price, stock, image, category_id, status) VALUES
('Croissant', 'Buttery, flaky pastry', 75.00, 30, 'croissant.png', @pastries_id, 'active'),
('Ensaymada', 'Filipino sweet pastry with cheese', 65.00, 30, 'ensaymada.png', @pastries_id, 'active'),
('Cinnamon Roll', 'Sweet roll with cinnamon filling', 80.00, 30, 'cinnamon-roll.png', @pastries_id, 'active'),
('Danish Pastry', 'Multilayered sweet pastry', 85.00, 30, 'danish-pastry.png', @pastries_id, 'active'),
('Pandesal', 'Filipino bread rolls', 10.00, 100, 'pandesal.png', @pastries_id, 'active'),
('Cheese Bread', 'Bread with cheese filling', 45.00, 30, 'cheese-bread.png', @pastries_id, 'active'),
('Chocolate Muffin', 'Chocolate-flavored muffin', 60.00, 30, 'chocolate-muffin.png', @pastries_id, 'active'),
('Blueberry Muffin', 'Muffin with blueberries', 65.00, 30, 'blueberry-muffin.png', @pastries_id, 'active'),
('Banana Bread', 'Sweet bread made with mashed bananas', 70.00, 30, 'banana-bread.png', @pastries_id, 'active'),
('Hopia', 'Filipino bean-filled pastry', 15.00, 50, 'hopia.png', @pastries_id, 'active'),
('Spanish Bread', 'Sweet bread with butter filling', 20.00, 50, 'spanish-bread.png', @pastries_id, 'active'),
('Egg Tart', 'Pastry with egg custard filling', 55.00, 30, 'egg-tart.png', @pastries_id, 'active');

-- Add Non-Coffee Drink Products
INSERT INTO products (name, description, price, stock, image, category_id, status) VALUES
('Hot Chocolate', 'Warm chocolate beverage', 110.00, 100, 'hot-chocolate.png', @drinks_id, 'active'),
('Matcha Latte', 'Green tea latte', 140.00, 100, 'matcha-latte.png', @drinks_id, 'active'),
('Chai Tea Latte', 'Spiced tea with milk', 130.00, 100, 'chai-tea-latte.png', @drinks_id, 'active'),
('Iced Tea', 'Chilled tea with ice', 90.00, 100, 'iced-tea.png', @drinks_id, 'active'),
('Fruit Smoothie', 'Blended fruit beverage', 150.00, 100, 'fruit-smoothie.png', @drinks_id, 'active'),
('Mango Shake', 'Mango-flavored milkshake', 140.00, 100, 'mango-shake.png', @drinks_id, 'active'),
('Strawberry Shake', 'Strawberry-flavored milkshake', 140.00, 100, 'strawberry-shake.png', @drinks_id, 'active'),
('Chocolate Milkshake', 'Chocolate-flavored milkshake', 145.00, 100, 'chocolate-milkshake.png', @drinks_id, 'active'),
('Buko Juice', 'Young coconut juice', 95.00, 100, 'buko-juice.png', @drinks_id, 'active'),
('Calamansi Juice', 'Filipino citrus juice', 85.00, 100, 'calamansi-juice.png', @drinks_id, 'active'),
('Sago''t Gulaman', 'Filipino sweet drink with jellies', 90.00, 100, 'sagot-gulaman.png', @drinks_id, 'active'),
('Melon Juice', 'Fresh melon juice', 95.00, 100, 'melon-juice.png', @drinks_id, 'active');

-- Add Sandwich Products
INSERT INTO products (name, description, price, stock, image, category_id, status) VALUES
('Club Sandwich', 'Triple-decker sandwich with chicken and bacon', 160.00, 25, 'club-sandwich.png', @sandwiches_id, 'active'),
('Grilled Cheese', 'Toasted sandwich with melted cheese', 120.00, 25, 'grilled-cheese.png', @sandwiches_id, 'active'),
('Chicken Sandwich', 'Sandwich with chicken filling', 140.00, 25, 'chicken-sandwich.png', @sandwiches_id, 'active'),
('Tuna Sandwich', 'Sandwich with tuna filling', 130.00, 25, 'tuna-sandwich.png', @sandwiches_id, 'active'),
('Egg Sandwich', 'Sandwich with egg filling', 110.00, 25, 'egg-sandwich.png', @sandwiches_id, 'active'),
('Ham and Cheese', 'Sandwich with ham and cheese', 135.00, 25, 'ham-cheese-sandwich.png', @sandwiches_id, 'active'),
('Vegetable Sandwich', 'Sandwich with fresh vegetables', 125.00, 25, 'vegetable-sandwich.png', @sandwiches_id, 'active'),
('BLT Sandwich', 'Bacon, lettuce, and tomato sandwich', 145.00, 25, 'blt-sandwich.png', @sandwiches_id, 'active'),
('Panini', 'Pressed Italian sandwich', 155.00, 25, 'panini.png', @sandwiches_id, 'active'),
('Beef Sandwich', 'Sandwich with beef filling', 165.00, 25, 'beef-sandwich.png', @sandwiches_id, 'active');

-- Add Other Baked Goods Products
INSERT INTO products (name, description, price, stock, image, category_id, status) VALUES
('Chocolate Chip Cookie', 'Cookie with chocolate chips', 45.00, 40, 'chocolate-chip-cookie.png', @baked_id, 'active'),
('Oatmeal Cookie', 'Cookie made with oats', 40.00, 40, 'oatmeal-cookie.png', @baked_id, 'active'),
('Brownie', 'Dense chocolate square', 55.00, 40, 'brownie.png', @baked_id, 'active'),
('Cupcake', 'Small cake for one person', 50.00, 40, 'cupcake.png', @baked_id, 'active'),
('Donut', 'Fried dough confection', 45.00, 40, 'donut.png', @baked_id, 'active'),
('Bibingka', 'Filipino rice cake', 60.00, 30, 'bibingka.png', @baked_id, 'active'),
('Puto', 'Filipino steamed rice cake', 15.00, 50, 'puto.png', @baked_id, 'active'),
('Empanada', 'Stuffed pastry', 65.00, 30, 'empanada.png', @baked_id, 'active'),
('Siopao', 'Filipino steamed bun with filling', 55.00, 30, 'siopao.png', @baked_id, 'active'),
('Pianono', 'Filipino jelly roll', 50.00, 30, 'pianono.png', @baked_id, 'active');
