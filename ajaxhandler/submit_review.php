<?php
require_once __DIR__ . '/../database/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_id = $_POST['cust_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comment = $_POST['comment'] ?? '';

    if ($cust_id && $product_id && $rating >= 1 && $rating <= 5) {
        $dbo = new Database();
        $conn = $dbo->conn;

        // Prevent duplicate reviews
        $stmt = $conn->prepare("SELECT * FROM review WHERE cust_id = ? AND review_product_id = ?");
        $stmt->execute([$cust_id, $product_id]);
        if ($stmt->fetch()) {
            header("Location: ../orders.php"); // Already rated
            exit();
        }

        // Insert review
        $stmt = $conn->prepare("INSERT INTO review (cust_id, review_product_id, review_rating, review_comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cust_id, $product_id, $rating, $comment]);

        header("Location: ../orders.php");
        exit();
    } else {
        echo "Invalid input!";
    }
} else {
    echo "Invalid request method.";
}
