<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

require_once __DIR__ . '/../database/database.php';
require_once __DIR__. '/../database/CustomerAuth.php';
$db = new Database();
$pdo = $db->conn;

$auth = new CustomerAuth($pdo);

header("Content-Type: application/json");

if (!isset($_POST['cust_email']) || !isset($_POST['cust_password'])) {
    echo json_encode(["status" => "Missing credentials"]);
    exit;
}

$email = $_POST['cust_email'];
$password = $_POST['cust_password'];

$auth = new CustomerAuth($pdo); // $pdo comes from database.php
$user = $auth->validateCustomerLogin($email, $password);

if ($user) {
    session_start();
    $_SESSION['current_user'] = $user['customer_id'];
    $_SESSION['user_email'] = $user['cust_email'];
    $cust_id = $user['customer_id'];
    $update = $pdo->prepare("UPDATE cust_order SET order_status = 'Delivered' WHERE cust_id = ? AND delivery_date <= CURDATE() AND order_status = 'Pending'");
    $update->execute([$cust_id]);
    echo json_encode(["status" => "ALL OK"]);
} else {
    echo json_encode(["status" => "Invalid email or password"]);
}
