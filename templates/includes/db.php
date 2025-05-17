<?php
$host = "localhost";
$db = "brew_and_bake";
$user = "root";
$pass = "admin";

// Try to connect using PDO first
try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection_type = 'pdo';
} catch(Exception $e) {
    // If PDO fails, try mysqli as fallback
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $connection_type = 'mysqli';
    } catch(Exception $e) {
        die("All connection attempts failed. Last error: " . $e->getMessage());
    }
}

/**
 * Execute a query with parameters using the available connection
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @return mixed Result of the query
 */
function executeQuery($query, $params = []) {
    global $conn, $connection_type;

    if ($connection_type === 'pdo') {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } else {
        // For mysqli, we need to manually bind parameters
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $types = '';
            $bindParams = [];

            // Determine parameter types
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                $bindParams[] = $param;
            }

            // Create reference array for bind_param
            $bindParamsRef = [];
            $bindParamsRef[] = $types;

            for ($i = 0; $i < count($bindParams); $i++) {
                $bindParamsRef[] = &$bindParams[$i];
            }

            call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
        }

        $stmt->execute();
        return $stmt;
    }
}
?>
