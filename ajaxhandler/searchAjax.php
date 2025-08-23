<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";

header("Content-Type: application/json");

// 1) Ensure user is logged in
if (!isset($_SESSION['current_user'])) {
    echo json_encode(["status" => "ERROR", "message" => "User not logged in."]);
    exit();
}

$dbo    = new Database();
$conn   = $dbo->conn;
$cust_id = $_SESSION['current_user'];

// 2) Only handle POST + correct action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addToCart') {
    if (!isset($_POST['product_id'])) {
        echo json_encode(["status" => "ERROR", "message" => "Product ID missing."]);
        exit();
    }
    $product_id = (int)$_POST['product_id'];

    // 3) Find or create the cart
    $cartStmt = $conn->prepare("SELECT cart_id FROM Cart WHERE cust_id = :cust_id");
    $cartStmt->execute([':cust_id' => $cust_id]);
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $cart_id = $cart['cart_id'];
    } else {
        $insertCart = $conn->prepare("INSERT INTO Cart (cust_id) VALUES (:cust_id)");
        $insertCart->execute([':cust_id' => $cust_id]);
        $cart_id = $conn->lastInsertId();
    }

    // 4) Check if this product is already in the cart
    $itemCheck = $conn->prepare(
        "SELECT cart_item_id, cart_quantity
         FROM cart_item
         WHERE cart_id = :cart_id AND cart_product_id = :product_id"
    );
    $itemCheck->execute([
        ':cart_id'     => $cart_id,
        ':product_id'  => $product_id
    ]);
    $existing = $itemCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // 5a) If it exists, just bump the quantity
        $newQty = $existing['cart_quantity'] + 1;
        $updateQty = $conn->prepare(
            "UPDATE cart_item
             SET cart_quantity = :qty
             WHERE cart_item_id = :item_id"
        );
        $updateQty->execute([
            ':qty'     => $newQty,
            ':item_id' => $existing['cart_item_id']
        ]);
        echo json_encode(["status" => "SUCCESS", "message" => "Quantity updated to {$newQty}."]);
    } else {
        // 5b) Otherwise insert a new row
        $insertItem = $conn->prepare(
            "INSERT INTO cart_item (cart_id, cart_product_id, cart_quantity, cart_unit_price)
             VALUES (
               :cart_id,
               :product_id,
               1,
               (SELECT product_price FROM Product WHERE product_id = :product_id)
             )"
        );
        $insertItem->execute([
            ':cart_id'    => $cart_id,
            ':product_id' => $product_id
        ]);
        echo json_encode(["status" => "SUCCESS", "message" => "Product added to cart."]);
    }
    exit();
}

// 6) If we get here, it was an invalid request
echo json_encode(["status" => "ERROR", "message" => "Invalid action."]);
exit();
?>
