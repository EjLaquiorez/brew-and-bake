<?php
require_once "../includes/db.php";

try {
    // Get table structure
    $stmt = $conn->prepare("DESCRIBE products");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Products Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get sample data
    $stmt = $conn->prepare("SELECT * FROM products LIMIT 5");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "<h2>Sample Products Data</h2>";
        echo "<table border='1'>";
        
        // Headers
        echo "<tr>";
        foreach (array_keys($products[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($products as $product) {
            echo "<tr>";
            foreach ($product as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No products found in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
