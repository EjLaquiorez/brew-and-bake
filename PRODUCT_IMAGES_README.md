# Brew & Bake Product Images Guide

This guide explains how to set up product images for your Brew & Bake website. The files in this package provide a complete solution for managing product images, including naming conventions, placeholder generation, and database integration.

## Files Included

1. **product_image_guide.md** - Comprehensive list of products with naming conventions
2. **generate_product_images.php** - Script to generate placeholder images for products
3. **add_products.sql** - SQL script to add sample products to the database
4. **import_products.php** - PHP script to import products and check for missing images

## Quick Start Guide

### Step 1: Import Products to Database

1. Open your browser and navigate to:
   ```
   http://localhost/brew-and-bake/import_products.php
   ```

2. This will import all the sample products into your database and check for missing images.

### Step 2: Generate Placeholder Images

You have two options for generating placeholder images:

#### Option A: Using GD Library (Recommended if Available)

1. If you have the GD library enabled in PHP, run the image generator:
   ```
   http://localhost/brew-and-bake/generate_product_images.php
   ```

2. This will create placeholder images for all products in the `assets/images/products/` directory.

#### Option B: Using CSS-Based Placeholders (No GD Library Required)

1. If you don't have the GD library enabled, use the CSS-based placeholder generator:
   ```
   http://localhost/brew-and-bake/generate_css_placeholders.php
   ```

2. This will show you HTML/CSS-based placeholders that you can use for your products.
3. The script also creates a CSS file at `assets/css/product-placeholders.css` that you should include in your pages.

### Step 3: View Your Products

1. Navigate to your client page to see the products with images:
   ```
   http://localhost/brew-and-bake/templates/client/client.php
   ```

## Adding Your Own Product Images

When you have actual product photos, simply save them in the `assets/images/products/` directory using the naming conventions in the `product_image_guide.md` file.

For example:
- For a cappuccino, save the image as `cappuccino.png`
- For a chocolate cake, save the image as `chocolate-cake.png`

## Image Requirements

- **Format**: JPG or PNG (PNG preferred for products with transparency)
- **Size**: 800x800 pixels (1:1 aspect ratio)
- **Background**: Transparent or white background preferred
- **File Size**: Optimize images to be under 200KB each

## Replacing Placeholder Images

As you obtain actual product photos, simply replace the placeholder images with real photos using the same filenames. The website will automatically use your new images.

## Troubleshooting

If you encounter any issues:

1. **GD Library Error**: If you see an error about `imagecreatetruecolor()` or other GD functions:
   - The GD library is not enabled in your PHP installation
   - You can enable it by editing your php.ini file and removing the semicolon from `;extension=gd`
   - After enabling, restart your web server
   - Alternatively, use the CSS-based placeholders option

2. **Missing Images**: Make sure the `assets/images/products/` directory exists and is writable

3. **Database Errors**: Check that your database connection is working properly

4. **PHP Errors**: Ensure PHP has permission to write to the images directory

5. **CSS Placeholders Not Working**: Make sure you've included the `product-placeholders.css` file in your pages

## Additional Resources

- For more detailed information about product categories and naming, refer to `product_image_guide.md`
- To customize placeholder images, edit the `generate_product_images.php` script
- To add more products, modify the `add_products.sql` file and run the import again

## Next Steps

1. Replace placeholder images with actual product photos as they become available
2. Consider adding more product details and descriptions in the database
3. Organize products into featured collections on your homepage
