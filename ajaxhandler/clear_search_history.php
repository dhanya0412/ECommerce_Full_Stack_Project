<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['current_user'])) {
    echo json_encode(["message" => "Not logged in."]);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";
$dbo = new Database();
$conn = $dbo->conn;

$cust_id = $_SESSION['current_user'];

try {
    $stmt = $conn->prepare("DELETE FROM Search_History WHERE cust_id = ?");
    $stmt->execute([$cust_id]);
    echo json_encode(["message" => "Search history cleared successfully."]);
} catch (Exception $e) {
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
