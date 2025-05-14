<?php
session_start();
require_once "../includes/db.php";
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

header("Location: products.php");
?>
