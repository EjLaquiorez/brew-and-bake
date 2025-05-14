-- SQL script to update products for Brew & Bake with new menu items
-- This script will handle foreign key constraints and update the products

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing products
TRUNCATE TABLE products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert Signature Coffee products
INSERT INTO products (name, description, category, price, stock, image, status) VALUES
('Espresso', 'A concentrated shot of coffee served in a small cup, offering a rich, intense flavor with a layer of crema on top.', 'coffee', 150.00, 100, 'espresso.jpg', 'active'),
('Americano', 'Espresso diluted with hot water, creating a coffee similar in strength to regular drip coffee but with a different flavor profile.', 'coffee', 160.00, 100, 'americano.jpg', 'active'),
('Caffè Latte', 'Espresso with steamed milk and a small layer of milk foam, creating a creamy, mild coffee experience.', 'coffee', 180.00, 100, 'latte.jpg', 'active'),
('Cappuccino', 'Equal parts espresso, steamed milk, and milk foam, offering a perfect balance of strong coffee flavor and creamy texture.', 'coffee', 180.00, 100, 'cappuccino.jpg', 'active'),
('Caramel Macchiato', 'Vanilla-flavored milk marked with espresso and topped with caramel drizzle, creating a sweet, layered coffee treat.', 'coffee', 200.00, 80, 'caramel_macchiato.jpg', 'active'),
('Mocha Latte', 'Espresso with chocolate syrup and steamed milk, topped with whipped cream for a decadent coffee experience.', 'coffee', 200.00, 80, 'mocha_latte.jpg', 'active'),
('White Chocolate Mocha', 'Espresso blended with white chocolate sauce and steamed milk, topped with whipped cream for a sweet, luxurious treat.', 'coffee', 220.00, 80, 'white_mocha.jpg', 'active'),
('Flat White', 'Espresso with microfoam (steamed milk with small, fine bubbles), resulting in a velvety texture and strong coffee flavor.', 'coffee', 210.00, 90, 'flat_white.jpg', 'active'),
('Iced Shaken Espresso', 'Espresso shots shaken with ice and a touch of sweetness, creating a frothy, refreshing cold coffee.', 'coffee', 190.00, 90, 'shaken_espresso.jpg', 'active'),
('Cold Brew', 'Coffee steeped in cold water for 12-24 hours, resulting in a smooth, less acidic brew served over ice.', 'coffee', 170.00, 90, 'cold_brew.jpg', 'active');

-- Insert Iced and Blended products
INSERT INTO products (name, description, category, price, stock, image, status) VALUES
('Iced Americano', 'Espresso shots topped with cold water and ice for a refreshing, bold coffee experience.', 'iced', 160.00, 90, 'iced_americano.jpg', 'active'),
('Iced Latte', 'Espresso combined with cold milk and ice, creating a refreshing version of the classic latte.', 'iced', 180.00, 90, 'iced_latte.jpg', 'active'),
('Iced Caramel Macchiato', 'Vanilla-flavored milk with ice, marked with espresso and topped with caramel drizzle for a sweet, cold treat.', 'iced', 200.00, 80, 'iced_caramel_macchiato.jpg', 'active'),
('Java Chip Frappe', 'Coffee blended with ice, milk, chocolate chips, and chocolate sauce, topped with whipped cream and chocolate drizzle.', 'blended', 250.00, 70, 'java_chip.jpg', 'active'),
('Mocha Frappe', 'Coffee blended with ice, milk, and chocolate sauce, topped with whipped cream and chocolate drizzle.', 'blended', 240.00, 70, 'mocha_frappe.jpg', 'active'),
('Caramel Frappe', 'Coffee blended with ice, milk, and caramel sauce, topped with whipped cream and caramel drizzle.', 'blended', 240.00, 70, 'caramel_frappe.jpg', 'active'),
('Matcha Green Tea Frappe', 'Japanese green tea powder blended with ice, milk, and sweetener, topped with whipped cream.', 'blended', 250.00, 60, 'matcha_frappe.jpg', 'active'),
('Strawberries & Cream Frappe', 'Strawberry puree blended with ice, milk, and sweetener, topped with whipped cream and strawberry drizzle.', 'blended', 260.00, 60, 'strawberry_frappe.jpg', 'active'),
('Cookies & Cream Frappe', 'Chocolate cookie pieces blended with ice, milk, and vanilla base, topped with whipped cream and cookie crumbs.', 'blended', 250.00, 60, 'cookies_cream_frappe.jpg', 'active'),
('Ube Frappe', 'Filipino purple yam flavor blended with ice, milk, and sweetener, topped with whipped cream and ube drizzle.', 'blended', 260.00, 60, 'ube_frappe.jpg', 'active');

-- Insert Pastry products
INSERT INTO products (name, description, category, price, stock, image, status) VALUES
('Classic Croissant', 'Buttery, flaky French pastry with a golden-brown exterior and soft, layered interior.', 'pastry', 80.00, 50, 'croissant.jpg', 'active'),
('Chocolate Croissant', 'Flaky croissant filled with rich chocolate batons, creating a perfect balance of buttery and sweet.', 'pastry', 100.00, 50, 'chocolate_croissant.jpg', 'active'),
('Cheese Danish', 'Flaky pastry filled with sweet cream cheese filling, topped with a light glaze.', 'pastry', 120.00, 40, 'cheese_danish.jpg', 'active'),
('Banana Bread Slice', 'Moist, dense quick bread made with ripe bananas, offering a sweet, comforting flavor.', 'pastry', 150.00, 30, 'banana_bread.jpg', 'active'),
('Blueberry Muffin', 'Tender muffin studded with juicy blueberries and topped with a light sugar sprinkle.', 'pastry', 130.00, 40, 'blueberry_muffin.jpg', 'active'),
('Chocolate Chip Cookie', 'Classic cookie with a soft, chewy center and crisp edges, filled with chocolate chips.', 'pastry', 90.00, 60, 'chocolate_chip_cookie.jpg', 'active'),
('Ube Cheese Pandesal', 'Purple yam-flavored soft bread rolls with a cheese filling, a modern twist on the Filipino breakfast staple.', 'pastry', 60.00, 60, 'ube_pandesal.jpg', 'active'),
('Ensaymada', 'Soft, buttery Filipino brioche pastry coiled into a spiral, topped with butter, sugar, and grated cheese.', 'pastry', 70.00, 50, 'ensaymada.jpg', 'active'),
('Cheese Roll', 'Soft bread roll filled with butter and cheese, then rolled in breadcrumbs and sugar for a sweet-savory treat.', 'pastry', 60.00, 50, 'cheese_roll.jpg', 'active'),
('Cinnamon Roll', 'Spiral of soft, leavened dough filled with cinnamon-sugar mixture and topped with cream cheese frosting.', 'pastry', 140.00, 40, 'cinnamon_roll.jpg', 'active');
