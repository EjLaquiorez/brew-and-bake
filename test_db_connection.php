<?php
// Test database connection
$host = "localhost";
$db = "brew_and_bake";
$user = "root";
$pass = "admin";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully!\n";
    
    // Check if users table exists
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'users'
    ");
    $stmt->execute();
    $tableExists = $stmt->fetchColumn() > 0;
    
    if ($tableExists) {
        echo "Users table exists!\n";
        
        // Get user count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $userCount = $stmt->fetchColumn();
        echo "Number of users in the database: " . $userCount . "\n";
    } else {
        echo "Users table does not exist!\n";
    }
    
    // Check if client_addresses table exists
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'client_addresses'
    ");
    $stmt->execute();
    $tableExists = $stmt->fetchColumn() > 0;
    
    if ($tableExists) {
        echo "Client_addresses table exists!\n";
        
        // Get address count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM client_addresses");
        $stmt->execute();
        $addressCount = $stmt->fetchColumn();
        echo "Number of addresses in the database: " . $addressCount . "\n";
    } else {
        echo "Client_addresses table does not exist!\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
