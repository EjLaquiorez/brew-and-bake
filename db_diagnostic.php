<?php
// Database connection diagnostic script

// Display PHP version and loaded extensions
echo "<h2>PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h3>Loaded Extensions:</h3>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

// Check if PDO and PDO_MYSQL are available
echo "<h3>PDO Status:</h3>";
echo "<p>PDO Available: " . (class_exists('PDO') ? 'Yes' : 'No') . "</p>";
echo "<p>PDO MySQL Driver Available: " . (in_array('pdo_mysql', get_loaded_extensions()) ? 'Yes' : 'No') . "</p>";

// Try to connect to MySQL using mysqli as an alternative
echo "<h2>MySQL Connection Test (mysqli)</h2>";
$host = "localhost";
$user = "root";
$pass = "admin";
$db = "brew_and_bake";

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    echo "<p>Connection failed: " . $mysqli->connect_error . "</p>";
} else {
    echo "<p>Connected successfully using mysqli!</p>";
    
    // Check if users table exists
    $result = $mysqli->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p>Users table exists!</p>";
        
        // Get user count
        $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "<p>Number of users in the database: " . $row['count'] . "</p>";
    } else {
        echo "<p>Users table does not exist!</p>";
    }
    
    // Check if client_addresses table exists
    $result = $mysqli->query("SHOW TABLES LIKE 'client_addresses'");
    if ($result->num_rows > 0) {
        echo "<p>Client_addresses table exists!</p>";
        
        // Get address count
        $result = $mysqli->query("SELECT COUNT(*) as count FROM client_addresses");
        $row = $result->fetch_assoc();
        echo "<p>Number of addresses in the database: " . $row['count'] . "</p>";
    } else {
        echo "<p>Client_addresses table does not exist!</p>";
    }
    
    $mysqli->close();
}

// Try to connect using PDO
echo "<h2>MySQL Connection Test (PDO)</h2>";
try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>Connected successfully using PDO!</p>";
    
    // Check if users table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p>Users table exists!</p>";
        
        // Get user count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $userCount = $stmt->fetchColumn();
        echo "<p>Number of users in the database: " . $userCount . "</p>";
    } else {
        echo "<p>Users table does not exist!</p>";
    }
    
    // Check if client_addresses table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'client_addresses'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p>Client_addresses table exists!</p>";
        
        // Get address count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM client_addresses");
        $stmt->execute();
        $addressCount = $stmt->fetchColumn();
        echo "<p>Number of addresses in the database: " . $addressCount . "</p>";
    } else {
        echo "<p>Client_addresses table does not exist!</p>";
    }
} catch(PDOException $e) {
    echo "<p>Connection failed: " . $e->getMessage() . "</p>";
}
?>
