<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";

if (!isset($_SESSION['current_user'])) {
    echo json_encode(["status" => "ERROR", "message" => "User not logged in"]);
    exit();
}

$dbo = new Database();
$conn = $dbo->conn;
$cust_id = $_SESSION['current_user'];

if (!isset($_POST["action"])) {
    echo json_encode(["status" => "ERROR", "message" => "Invalid request"]);
    exit();
}

$action = $_POST["action"];

if ($action == "addToCart") {
    if (!isset($_POST["product_id"])) {
        echo json_encode(["status" => "ERROR", "message" => "Product ID missing"]);
        exit();
    }

    $product_id = $_POST["product_id"];

    // Check if the product is in stock
    $stock_check = $conn->prepare("SELECT stock_quantity FROM Products WHERE product_id = :product_id");
    $stock_check->execute([':product_id' => $product_id]);
    $stock_result = $stock_check->fetch(PDO::FETCH_ASSOC);

    if (!$stock_result) {
        echo json_encode(["status" => "ERROR", "message" => "Invalid product ID"]);
        exit();
    }
    $stock = $stock_result['stock_quantity'];

    // Find the cart ID for the user
    $cart_check = $conn->prepare("SELECT cart_id FROM Cart WHERE cust_id = :cust_id");
    $cart_check->execute([':cust_id' => $cust_id]);
    $cart = $cart_check->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        $conn->prepare("INSERT INTO Cart (cust_id) VALUES (:cust_id)")->execute([':cust_id' => $cust_id]);
        $cart_id = $conn->lastInsertId();
    } else {
        $cart_id = $cart['cart_id'];
    }

    if ($stock <= 0) {
        echo json_encode(["status" => "ERROR", "message" => "This product is currently out of stock. We'll notify you when it's back!"]);
        exit();
    }

    // Check if product already in cart
    $cart_item_check = $conn->prepare("SELECT * FROM cart_item WHERE cart_id = :cart_id AND cart_product_id = :product_id");
    $cart_item_check->execute([':cart_id' => $cart_id, ':product_id' => $product_id]);
    $existing = $cart_item_check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $conn->prepare("UPDATE cart_item SET cart_quantity = cart_quantity + 1 WHERE cart_item_id = :cart_item_id")
             ->execute([':cart_item_id' => $existing['cart_item_id']]);
    } else {
        $product_price = $conn->prepare("SELECT product_price FROM Products WHERE product_id = :product_id");
        $product_price->execute([':product_id' => $product_id]);
        $price = $product_price->fetch(PDO::FETCH_ASSOC)['product_price'];

        $conn->prepare("INSERT INTO cart_item (cart_id, cart_product_id, cart_quantity, cart_unit_price) VALUES (:cart_id, :product_id, 1, :price)")
             ->execute([':cart_id' => $cart_id, ':product_id' => $product_id, ':price' => $price]);
    }

    echo json_encode(["status" => "SUCCESS", "message" => "Product added to cart"]);
} 

elseif ($action == "removeFromCart") {
    if (!isset($_POST["cart_item_id"])) {
        echo json_encode(["status" => "ERROR", "message" => "Cart item ID missing"]);
        exit();
    }

    $cart_item_id = $_POST["cart_item_id"];
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE cart_item_id = :cart_item_id");
    $stmt->execute([':cart_item_id' => $cart_item_id]);

    echo json_encode(["status" => "SUCCESS", "message" => "Item removed from cart"]);
}

elseif ($action == "updateQuantity") {
    $cart_item_id = $_POST['cart_item_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;

    if (!$cart_item_id || !$quantity || $quantity < 1) {
        echo json_encode(["status" => "ERROR", "message" => "Invalid data"]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE cart_item SET cart_quantity = :qty WHERE cart_item_id = :cart_item_id");
    $stmt->execute([
        ':qty' => $quantity,
        ':cart_item_id' => $cart_item_id
    ]);

    echo json_encode(["status" => "SUCCESS", "message" => "Cart updated"]);
}

else {
    echo json_encode(["status" => "ERROR", "message" => "Invalid action"]);
}
?>
