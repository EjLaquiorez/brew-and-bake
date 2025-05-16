<?php
// Use the correct path to the database connection file
require_once __DIR__ . "/templates/includes/db.php";

// Check if Sandwich category exists
try {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE LOWER(name) = 'sandwich'");
    $stmt->execute();
    $sandwichCategory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sandwichCategory) {
        // Add Sandwich category if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute(['Sandwich', 'Freshly made gourmet sandwiches']);
        echo "✅ Sandwich category added successfully!";
    } else {
        echo "ℹ️ Sandwich category already exists.";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
