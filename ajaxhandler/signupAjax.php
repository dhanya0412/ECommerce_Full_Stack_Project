<?php
$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . "/ecommerce_project/database/database.php";
require_once $path . "/ecommerce_project/database/CustomerAuth.php";

$db = new Database();
$pdo = $db->conn;

$auth = new CustomerAuth($pdo);

header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(["status" => "All fields are required"]);
            exit;
        }

        $auth = new CustomerAuth($pdo); // Make sure $pdo comes from database.php

        if ($auth->registerCustomer($name, $email, $password)) {
            echo json_encode(["status" => "OK"]);
        } else {
            echo json_encode(["status" => "Email already registered"]);
        }
    } else {
        echo json_encode(["status" => "Invalid request method"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "Server error: " . $e->getMessage()]);
}
