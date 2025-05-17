# Brew & Bake Product Image Guide

This document provides a comprehensive list of products organized by category with recommended image naming conventions. Following these conventions will ensure seamless integration with the existing website.

## Image Requirements

- **Format**: JPG or PNG (PNG preferred for products with transparency)
- **Size**: 800x800 pixels (1:1 aspect ratio)
- **Background**: Transparent or white background preferred
- **File Size**: Optimize images to be under 200KB each
- **Naming Convention**: Use lowercase with hyphens between words (e.g., `caramel-macchiato.png`)

## Image Storage Location

All product images should be stored in: `assets/images/products/`

## Product Categories and Image Names

### Coffee

| Product Name | Image Filename | Description |
|--------------|----------------|-------------|
| Espresso | espresso.png | Single shot of concentrated coffee |
| Americano | americano.png | Espresso diluted with hot water |
| Cappuccino | cappuccino.png | Espresso with steamed milk and foam |
| Latte | latte.png | Espresso with steamed milk |
| Mocha | mocha.png | Espresso with chocolate and steamed milk |
| Caramel Macchiato | caramel-macchiato.png | Vanilla-flavored espresso with caramel drizzle |
| Flat White | flat-white.png | Espresso with steamed milk |
| Cold Brew | cold-brew.png | Coffee brewed with cold water |
| Iced Coffee | iced-coffee.png | Chilled coffee with ice |
| Filipino Barako | filipino-barako.png | Strong local coffee variety |
| Vanilla Latte | vanilla-latte.png | Latte with vanilla flavoring |
| Hazelnut Coffee | hazelnut-coffee.png | Coffee with hazelnut flavoring |

### Cakes

| Product Name | Image Filename | Description |
|--------------|----------------|-------------|
| Chocolate Cake | chocolate-cake.png | Rich chocolate layer cake |
| Red Velvet Cake | red-velvet-cake.png | Red-colored cake with cream cheese frosting |
| Carrot Cake | carrot-cake.png | Spiced cake with carrots and cream cheese frosting |
| Cheesecake | cheesecake.png | Classic creamy cheesecake |
| Ube Cake | ube-cake.png | Filipino purple yam cake |
| Mango Cake | mango-cake.png | Fresh mango cream cake |
| Tiramisu | tiramisu.png | Coffee-flavored Italian dessert |
| Black Forest Cake | black-forest-cake.png | Chocolate cake with cherries and cream |
| Leche Flan Cake | leche-flan-cake.png | Cake topped with caramel custard |
| Buko Pandan Cake | buko-pandan-cake.png | Coconut pandan-flavored cake |

### Pastries

| Product Name | Image Filename | Description |
|--------------|----------------|-------------|
| Croissant | croissant.png | Buttery, flaky pastry |
| Ensaymada | ensaymada.png | Filipino sweet pastry with cheese |
| Cinnamon Roll | cinnamon-roll.png | Sweet roll with cinnamon filling |
| Danish Pastry | danish-pastry.png | Multilayered sweet pastry |
| Pandesal | pandesal.png | Filipino bread rolls |
| Cheese Bread | cheese-bread.png | Bread with cheese filling |
| Chocolate Muffin | chocolate-muffin.png | Chocolate-flavored muffin |
| Blueberry Muffin | blueberry-muffin.png | Muffin with blueberries |
| Banana Bread | banana-bread.png | Sweet bread made with mashed bananas |
| Hopia | hopia.png | Filipino bean-filled pastry |
| Spanish Bread | spanish-bread.png | Sweet bread with butter filling |
| Egg Tart | egg-tart.png | Pastry with egg custard filling |

### Non-Coffee Drinks

| Product Name | Image Filename | Description |
|--------------|----------------|-------------|
| Hot Chocolate | hot-chocolate.png | Warm chocolate beverage |
| Matcha Latte | matcha-latte.png | Green tea latte |
| Chai Tea Latte | chai-tea-latte.png | Spiced tea with milk |
| Iced Tea | iced-tea.png | Chilled tea with ice |
| Fruit Smoothie | fruit-smoothie.png | Blended fruit beverage |
| Mango Shake | mango-shake.png | Mango-flavored milkshake |
| Strawberry Shake | strawberry-shake.png | Strawberry-flavored milkshake |
| Chocolate Milkshake | chocolate-milkshake.png | Chocolate-flavored milkshake |
| Buko Juice | buko-juice.png | Young coconut juice |
| Calamansi Juice | calamansi-juice.png | Filipino citrus juice |
| Sago't Gulaman | sagot-gulaman.png | Filipino sweet drink with jellies |
| Melon Juice | melon-juice.png | Fresh melon juice |

### Sandwiches

| Product Name | Image Filename | Description |
|--------------|----------------|-------------|
| Club Sandwich | club-sandwich.png | Triple-decker sandwich with chicken and bacon |
| Grilled Cheese | grilled-cheese.png | Toasted sandwich with melted cheese |
| Chicken Sandwich | chicken-sandwich.png | Sandwich with chicken filling |
| Tuna Sandwich | tuna-sandwich.png | Sandwich with tuna filling |
| Egg Sandwich | egg-sandwich.png | Sandwich with egg filling |
| Ham and Cheese | ham-cheese-sandwich.png | Sandwich with ham and cheese |
| Vegetable Sandwich | vegetable-sandwich.png | Sandwich with fresh vegetables |
| BLT Sandwich | blt-sandwich.png | Bacon, lettuce, and tomato sandwich |
| Panini | panini.png | Pressed Italian sandwich |
| Beef Sandwich | beef-sandwich.png | Sandwich with beef filling |

### Other Baked Goods

| Product Name | Image Filename | Description |
|--------------|----------------|-------------|
| Chocolate Chip Cookie | chocolate-chip-cookie.png | Cookie with chocolate chips |
| Oatmeal Cookie | oatmeal-cookie.png | Cookie made with oats |
| Brownie | brownie.png | Dense chocolate square |
| Cupcake | cupcake.png | Small cake for one person |
| Donut | donut.png | Fried dough confection |
| Bibingka | bibingka.png | Filipino rice cake |
| Puto | puto.png | Filipino steamed rice cake |
| Empanada | empanada.png | Stuffed pastry |
| Siopao | siopao.png | Filipino steamed bun with filling |
| Pianono | pianono.png | Filipino jelly roll |

## Implementation Tips

1. **Consistency**: Maintain consistent lighting, angle, and style across all product images
2. **Product Focus**: Ensure the product is the main focus of each image
3. **Quality**: Use high-resolution images that showcase product details
4. **Fallback**: If a specific product image is not available, the system will use a category-based placeholder
5. **Updating**: When adding new products, follow the same naming convention

## Example HTML Usage

```html
<img src="../../assets/images/products/cappuccino.png" alt="Cappuccino">
```

## Example PHP Usage

```php
<?php if (!empty($product['image'])): ?>
    <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
<?php else: ?>
    <?= getTempProductImageHtml($product['name'], $product['category_name'] ?? 'Uncategorized') ?>
<?php endif; ?>
```
