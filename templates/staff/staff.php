<?php
session_start();
if ($_SESSION['user_role'] !== 'staff') {
    header("Location: ../../views/login.php");
    exit;
}
echo "<h1>Welcome Staff!</h1>";
?>
