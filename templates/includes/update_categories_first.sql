-- SQL script to update categories for Brew & Bake
-- This script will create the categories table if it doesn't exist and update it with the new categories

-- Check if categories table exists, if not create it
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clear existing categories
TRUNCATE TABLE categories;

-- Insert the new categories with exact IDs to match existing data
INSERT INTO categories (id, name, description, created_at, updated_at) VALUES
(1, 'Coffee', 'Freshly brewed coffee and espresso-based drinks.', NOW(), NOW()),
(2, 'Pastry', 'Freshly baked pastries and bread.', NOW(), NOW()),
(3, 'Cake', 'Decadent cakes and desserts.', NOW(), NOW()),
(4, 'Beverage', 'Non-coffee drinks like juices, teas, and smoothies.', NOW(), NOW()),
(5, 'Sandwich', 'Savory sandwiches and light meals.', NOW(), NOW());
