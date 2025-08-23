<?php
require_once __DIR__ . '/../database/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

// Check required fields
if (!isset($data->order_item_id) || !isset($data->return_reason)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$order_item_id = $data->order_item_id;
$return_reason = $data->return_reason;

$dbo = new Database();
$pdo = $dbo->conn;

// Check if the item is from a delivered order
$stmt = $pdo->prepare("
    SELECT oi.*, o.order_status
    FROM order_item oi
    JOIN cust_order o ON oi.order_id = o.order_id
    WHERE oi.order_item_id = ? AND o.order_status = 'Delivered'
");
$stmt->execute([$order_item_id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Order item not found or order not delivered.']);
    exit;
}

// Optional: Check if return already submitted
$stmt = $pdo->prepare("SELECT * FROM return_request WHERE return_order_item_id = ?");
$stmt->execute([$order_item_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Return request already submitted.']);
    exit;
}

// Submit return request
$request_id = uniqid('RET');
$stmt = $pdo->prepare("
    INSERT INTO return_request (return_order_item_id, request_id, return_reason) 
    VALUES (?, ?, ?)
");
$stmt->execute([$order_item_id, $request_id, $return_reason]);

echo json_encode(['success' => true, 'message' => 'Return request submitted successfully.']);
