<?php
// Check PDO drivers
echo "<h2>Available PDO Drivers:</h2>";
print_r(PDO::getAvailableDrivers());

// Check PHP version
echo "<h2>PHP Version:</h2>";
echo phpversion();

// Check MySQL connection
echo "<h2>Testing MySQL Connection:</h2>";
try {
    $host = "localhost";
    $db = "brew_and_bake";
    $user = "root";
    $pass = "admin";
    
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully to MySQL database!";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
