-- Fix client_addresses table
USE brew_and_bake;

-- Check if client_addresses table exists
CREATE TABLE IF NOT EXISTS client_addresses (
    id INT NOT NULL AUTO_INCREMENT,
    client_id INT NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT 'Manila',
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NOT NULL DEFAULT '1000',
    country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
    phone VARCHAR(20) NULL,
    is_default BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add phone column if it doesn't exist
ALTER TABLE client_addresses ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER country;

-- Add is_default column if it doesn't exist
ALTER TABLE client_addresses ADD COLUMN IF NOT EXISTS is_default BOOLEAN NOT NULL DEFAULT 1 AFTER phone;

-- Make sure all existing records have is_default set to 1
UPDATE client_addresses SET is_default = 1 WHERE is_default IS NULL;

-- Show the table structure
DESCRIBE client_addresses;
