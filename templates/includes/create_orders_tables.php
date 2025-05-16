<?php
require_once "db.php";

try {
    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $ordersTableExists = $stmt->rowCount() > 0;

    // Check if order_items table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'order_items'");
    $stmt->execute();
    $orderItemsTableExists = $stmt->rowCount() > 0;

    // Check if payments table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'payments'");
    $stmt->execute();
    $paymentsTableExists = $stmt->rowCount() > 0;

    // Create orders table if it doesn't exist
    if (!$ordersTableExists) {
        $sql = "CREATE TABLE orders (
            id INT NOT NULL AUTO_INCREMENT,
            client_id INT NOT NULL,
            order_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'cancelled') NULL DEFAULT 'pending',
            payment_status ENUM('unpaid', 'paid') NULL DEFAULT 'unpaid',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        )";

        $conn->exec($sql);
        echo "Orders table created successfully!<br>";
    } else {
        echo "Orders table already exists.<br>";
    }

    // Create order_items table if it doesn't exist
    if (!$orderItemsTableExists) {
        $sql = "CREATE TABLE order_items (
            id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * price) STORED,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";

        $conn->exec($sql);
        echo "Order items table created successfully!<br>";
    } else {
        echo "Order items table already exists.<br>";
    }

    // Create payments table if it doesn't exist
    if (!$paymentsTableExists) {
        $sql = "CREATE TABLE payments (
            id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'credit_card', 'gcash', 'bank_transfer') NULL DEFAULT 'cash',
            payment_status ENUM('pending', 'completed', 'failed') NULL DEFAULT 'pending',
            transaction_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";

        $conn->exec($sql);
        echo "Payments table created successfully!<br>";
    } else {
        echo "Payments table already exists.<br>";
    }

    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='../../templates/client/orders.php'>Go to Orders Page</a></p>";

} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "<br>";
}
?>
