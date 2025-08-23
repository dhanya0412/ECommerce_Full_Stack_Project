<?php
require_once(__DIR__ . '/../database/database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->conn;

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';  // no hashing
    $registration = $_POST['registration'] ?? '';

    try {
        $stmt = $conn->prepare("INSERT INTO seller (seller_name, seller_email, seller_password) VALUES (:name, :email, :password)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
       // $stmt->bindParam(':registration', $registration);
        $stmt->execute();

        echo json_encode(['status' => 'SUCCESS', 'message' => 'Seller registered successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
    }
}
?>
