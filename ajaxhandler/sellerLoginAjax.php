<?php
require_once(__DIR__ . '/../SellerAuth.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new SellerAuth();
    $result = $auth->login($email, $password);

    if ($result['status'] === 'SUCCESS') {
        session_start();
        $_SESSION['seller_id'] = $result['seller']['seller_id'];
        $_SESSION['seller_name'] = $result['seller']['seller_name'];

        // ✅ Send success as JSON
        echo json_encode(["status" => "SUCCESS"]);
    } else {
        echo json_encode(["status" => "ERROR", "message" => "Invalid credentials."]);
    }
}
