<?php
session_start();
require_once('database/database.php');
$db = new Database();
$conn = $db->conn;

if (!isset($_SESSION['seller_id'])) {
    header("Location: sellerLogin.php");
    exit();
}

$product_id = $_POST['product_id'] ?? null;

if ($product_id) {
    $stmt = $conn->prepare("DELETE FROM product WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $_SESSION['seller_id']]);
}

header("Location: sellerDashboard.php");
exit();
?>
