-- SQL script to update categories for Brew & Bake
-- This script will create the categories table if it doesn't exist and update it with the new categories

-- Check if categories table exists, if not create it
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clear existing categories
TRUNCATE TABLE categories;

-- Insert the new categories
INSERT INTO categories (name, description) VALUES
('cake', 'Delicious cakes and cheesecakes for all occasions, available by the slice or as whole cakes for special events.'),
('pastry', 'Freshly baked pastries including croissants, rolls, and traditional Filipino favorites like ensaymada and pandesal.'),
('coffee', 'Premium coffee drinks from espresso to specialty lattes, featuring locally sourced beans and expert barista preparation.'),
('drink', 'Refreshing non-coffee beverages including tea, smoothies, and specialty drinks for those who prefer alternatives to coffee.'),
('dessert', 'Other delightful baked items including cookies, brownies, and specialty desserts that complement our coffee and beverage offerings.');
