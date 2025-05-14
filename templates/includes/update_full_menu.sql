-- SQL script to update products for Brew & Bake with comprehensive menu
-- This script will handle foreign key constraints and update the products
-- Field structure: id, name, description, category, price, image, created_at, status, stock

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing products
TRUNCATE TABLE products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert Cake products
INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('New York Cheesecake', 'Classic creamy cheesecake with a buttery graham crust', 'cake', 250.00, 'ny_cheesecake.jpg', 'active', 20);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Blueberry Cheesecake', 'Cheesecake topped with fresh blueberry compote', 'cake', 270.00, 'blueberry_cheesecake.jpg', 'active', 20);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Matcha Cheesecake', 'Earthy matcha-infused cheesecake', 'cake', 280.00, 'matcha_cheesecake.jpg', 'active', 15);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Red Velvet Cake', 'Moist red cake with cream cheese frosting', 'cake', 250.00, 'red_velvet.jpg', 'active', 20);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Tiramisu', 'Coffee-soaked layers with mascarpone cream', 'cake', 300.00, 'tiramisu.jpg', 'active', 15);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Mocha Cake', 'Rich chocolate and espresso-flavored cake', 'cake', 260.00, 'mocha_cake.jpg', 'active', 20);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Carrot Cake', 'Spiced cake with cream cheese frosting and walnuts', 'cake', 240.00, 'carrot_cake.jpg', 'active', 20);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Opera Cake', 'Layers of almond sponge, coffee buttercream, and chocolate ganache', 'cake', 320.00, 'opera_cake.jpg', 'active', 15);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Sans Rival', 'Crisp cashew meringue layers with buttercream', 'cake', 300.00, 'sans_rival.jpg', 'active', 15);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Chocolate Lava Cake', 'Warm, gooey chocolate center', 'cake', 320.00, 'lava_cake.jpg', 'active', 15);

-- Insert Pastry products
INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Classic Croissant', 'Flaky, buttery croissant', 'pastry', 100.00, 'croissant.jpg', 'active', 30);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Chocolate Croissant', 'Croissant filled with rich chocolate', 'pastry', 120.00, 'chocolate_croissant.jpg', 'active', 30);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Almond Croissant', 'Croissant with almond paste and flaked almonds', 'pastry', 130.00, 'almond_croissant.jpg', 'active', 25);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Cinnamon Roll', 'Soft, spiced roll with cream cheese frosting', 'pastry', 140.00, 'cinnamon_roll.jpg', 'active', 25);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Cheese Roll', 'Soft bread with a creamy cheese filling', 'pastry', 70.00, 'cheese_roll.jpg', 'active', 40);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Ube Cheese Pandesal', 'Filipino favorite with ube and cheese filling', 'pastry', 60.00, 'ube_pandesal.jpg', 'active', 50);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Blueberry Muffin', 'Moist muffin with blueberry bits', 'pastry', 130.00, 'blueberry_muffin.jpg', 'active', 30);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Banana Nut Muffin', 'Classic banana muffin with walnuts', 'pastry', 120.00, 'banana_muffin.jpg', 'active', 30);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Eclairs', 'Choux pastry with cream filling and chocolate glaze', 'pastry', 150.00, 'eclairs.jpg', 'active', 20);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Macarons', 'Delicate, colorful French almond cookies (5 pcs)', 'pastry', 200.00, 'macarons.jpg', 'active', 20);

-- Insert Coffee products
INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Espresso', 'Bold, concentrated coffee shot', 'coffee', 150.00, 'espresso.jpg', 'active', 100);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Americano', 'Espresso with hot water', 'coffee', 160.00, 'americano.jpg', 'active', 100);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Caffè Latte', 'Espresso with steamed milk', 'coffee', 180.00, 'latte.jpg', 'active', 100);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Cappuccino', 'Espresso with steamed milk and foam', 'coffee', 180.00, 'cappuccino.jpg', 'active', 100);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Caramel Macchiato', 'Vanilla syrup, steamed milk, espresso, caramel drizzle', 'coffee', 200.00, 'caramel_macchiato.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Mocha', 'Espresso, steamed milk, and chocolate syrup', 'coffee', 200.00, 'mocha.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Flat White', 'Creamy espresso with a thin layer of microfoam', 'coffee', 210.00, 'flat_white.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Cold Brew', 'Smooth, slow-brewed cold coffee', 'coffee', 170.00, 'cold_brew.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Nitro Coffee', 'Cold brew infused with nitrogen for a creamy texture', 'coffee', 250.00, 'nitro_coffee.jpg', 'active', 60);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Affogato', 'Espresso shot poured over vanilla ice cream', 'coffee', 220.00, 'affogato.jpg', 'active', 60);

-- Insert Non-Coffee Drinks products
INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Matcha Latte', 'Creamy green tea with steamed milk', 'drink', 220.00, 'matcha_latte.jpg', 'active', 70);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Chai Tea Latte', 'Spiced black tea with steamed milk', 'drink', 210.00, 'chai_latte.jpg', 'active', 70);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Hot Chocolate', 'Rich, velvety cocoa', 'drink', 200.00, 'hot_chocolate.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Iced Tea', 'Refreshing, lightly sweetened iced tea (Peach, Lemon, Raspberry)', 'drink', 160.00, 'iced_tea.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Fruit Smoothies', 'Mango, Strawberry, or Banana', 'drink', 250.00, 'smoothie.jpg', 'active', 60);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Lemonade', 'Freshly squeezed, sweet and tangy', 'drink', 160.00, 'lemonade.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Milkshakes', 'Classic Vanilla, Chocolate, or Strawberry', 'drink', 250.00, 'milkshake.jpg', 'active', 60);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Italian Soda', 'Sparkling water with flavored syrup', 'drink', 180.00, 'italian_soda.jpg', 'active', 70);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Fruit Infused Water', 'Chilled water with fresh fruits', 'drink', 150.00, 'infused_water.jpg', 'active', 80);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Sparkling Water', 'Chilled, refreshing bubbly water', 'drink', 100.00, 'sparkling_water.jpg', 'active', 100);

-- Insert Other Baked Goods products
INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Brownies', 'Rich, fudgy chocolate squares', 'dessert', 80.00, 'brownies.jpg', 'active', 40);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Cookies', 'Chocolate Chip, Oatmeal, White Chocolate Macadamia', 'dessert', 90.00, 'cookies.jpg', 'active', 50);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Banana Bread', 'Moist banana loaf slice', 'dessert', 150.00, 'banana_bread.jpg', 'active', 30);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Biscotti', 'Crisp, twice-baked Italian cookies', 'dessert', 120.00, 'biscotti.jpg', 'active', 40);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Madeleines', 'Soft, buttery French tea cakes', 'dessert', 100.00, 'madeleines.jpg', 'active', 40);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Pies', 'Apple, Pecan, Buko (slice)', 'dessert', 200.00, 'pies.jpg', 'active', 25);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Tarts', 'Lemon, Fruit, Chocolate', 'dessert', 150.00, 'tarts.jpg', 'active', 30);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Donuts', 'Glazed, Chocolate, Cinnamon', 'dessert', 80.00, 'donuts.jpg', 'active', 50);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Crinkles', 'Soft, chocolatey cookies with powdered sugar', 'dessert', 90.00, 'crinkles.jpg', 'active', 50);

INSERT INTO products (name, description, category, price, image, status, stock)
VALUES ('Blondie Bars', 'White chocolate brownies', 'dessert', 100.00, 'blondies.jpg', 'active', 40);
