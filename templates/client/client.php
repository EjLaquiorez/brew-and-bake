<?php
session_start();
if ($_SESSION['user_role'] !== 'client') {
    header("Location: ../../views/login.php");
    exit;
}
echo "<h1>Welcome Client!</h1>";
?>
