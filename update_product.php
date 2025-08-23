<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
    header("Location: sellerLogin.php");
    exit();
}

require_once('database/database.php');
$db = new Database();
$conn = $db->conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $stock_quantity = $_POST['stock_quantity'];

    $stmt = $conn->prepare("UPDATE product SET product_name = ?, product_price = ?, stock_quantity = ? WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$product_name, $product_price, $stock_quantity, $product_id, $_SESSION['seller_id']]);

    header("Location: sellerDashboard.php");
    exit();
}
?>
