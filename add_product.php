<?php
session_start();
require_once('database/database.php');
$db = new Database();
$conn = $db->conn;

if (!isset($_SESSION['seller_id'])) {
    header("Location: sellerLogin.php");
    exit();
}

$seller_id = $_SESSION['seller_id'];

$name = $_POST['name'];
$category = $_POST['category'];
$description = $_POST['description'];
$price = $_POST['price'];
$quantity = $_POST['quantity'];

$stmt = $conn->prepare("INSERT INTO product (product_name, product_category, product_description, product_price, stock_quantity, listed_date, seller_id) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)");
$stmt->execute([$name, $category, $description, $price, $quantity, $seller_id]);

header("Location: sellerDashboard.php");
exit();
?>
