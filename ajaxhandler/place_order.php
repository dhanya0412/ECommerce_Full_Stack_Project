<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['current_user'])) {
    echo json_encode(["status" => "ERROR", "message" => "Not logged in."]);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";
$dbo = new Database();
$conn = $dbo->conn;

$cust_id = $_SESSION['current_user'];
$discountCode = $_POST['discount_code'] ?? '';
$finalTotal = $_POST['final_total'] ?? null;

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT cart_id FROM Cart WHERE cust_id = ?");
    $stmt->execute([$cust_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cart) throw new Exception("No cart found.");
    $cart_id = $cart['cart_id'];

    $stmt = $conn->prepare("SELECT cart_product_id, cart_quantity, cart_unit_price FROM Cart_Item WHERE cart_id = ?");
    $stmt->execute([$cart_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($items) === 0) throw new Exception("Cart is empty.");

    $total = 0;
    foreach ($items as $item) {
        $total += $item['cart_quantity'] * $item['cart_unit_price'];
    }

    $discountPercent = 0;
    if ($discountCode) {
        $stmt = $conn->prepare("SELECT * FROM trivia_attempts WHERE customer_id = ? AND discount_code = ? AND attempt_date = CURDATE() AND score = 1");
        $stmt->execute([$cust_id, $discountCode]);
        $valid = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($valid) {
            $difficulty = $valid['difficulty'];
            $discountPercent = ($difficulty === 'Easy' ? 5 : ($difficulty === 'Medium' ? 10 : 15));
        }
    }

    if ($finalTotal === null) {
        $finalTotal = $total - ($total * $discountPercent / 100);
    }
    $trivia_discount_amount = $total * ($discountPercent / 100);

    // Generate a random delivery date between 0 and 3 days
    //$deliveryDate = date('Y-m-d', strtotime("+".rand(1, 3)." days"));
    $deliveryDate = date('Y-m-d');


    // Insert the order with the generated delivery date
    $stmt = $conn->prepare("INSERT INTO cust_order (cust_id, order_date, total_amount, order_status, delivery_date) VALUES (?, NOW(), ?, 'Pending', ?)");
    $stmt->execute([$cust_id, $finalTotal, $deliveryDate]);
    $order_id = $conn->lastInsertId();

    $insertOrderItem = $conn->prepare("INSERT INTO order_item (order_id, order_product_id, order_quantity, order_unit_price) VALUES (?, ?, ?, ?)");
    $updateStock = $conn->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
    $getSellerIdStmt = $conn->prepare("SELECT seller_id, product_name FROM product WHERE product_id = ?");
    $getTopCustomerStmt = $conn->prepare("SELECT o.cust_id FROM cust_order o JOIN order_item oi ON o.order_id = oi.order_id JOIN product p ON oi.order_product_id = p.product_id WHERE p.seller_id = ? GROUP BY o.cust_id ORDER BY SUM(oi.order_quantity * oi.order_unit_price) DESC LIMIT 1");

    $discount_applied = [];
    $discount_details = [];

    foreach ($items as $item) {
        $product_id = $item['cart_product_id'];
        $quantity = $item['cart_quantity'];
        $unit_price = $item['cart_unit_price'];
    
        // Trivia discount applied first (if any)
        $trivia_price = $unit_price;
        if ($discountPercent > 0) {
            $trivia_price = round($unit_price * (1 - $discountPercent / 100), 2);
        }
    
        // Get seller info
        $getSellerIdStmt->execute([$product_id]);
        $seller = $getSellerIdStmt->fetch(PDO::FETCH_ASSOC);
        $seller_id = $seller['seller_id'];
        $product_name = $seller['product_name'];
    
        if (!isset($discount_applied[$seller_id])) {
            $discount_applied[$seller_id] = false;
        }
    
        $getTopCustomerStmt->execute([$seller_id]);
        $top_customer = $getTopCustomerStmt->fetch(PDO::FETCH_ASSOC);
        $top_customer_id = $top_customer ? $top_customer['cust_id'] : null;
    
        $final_price = $trivia_price;
        $original_price = $unit_price;
    
        // Apply top customer discount (after trivia)
        if ($cust_id == $top_customer_id && !$discount_applied[$seller_id]) {
            $final_price = round($trivia_price * 0.90, 2); // Extra 10% off
            $discount_applied[$seller_id] = true;
            $discount_details[] = [
                'seller_id' => $seller_id,
                'product' => $product_name,
                'original_price' => $original_price,
                'trivia_discounted_price' => $trivia_price,
                'final_discounted_price' => $final_price
            ];
        }
    
        $insertOrderItem->execute([$order_id, $product_id, $quantity, $final_price]);
    
        $updateStock->execute([$quantity, $product_id, $quantity]);
        if ($updateStock->rowCount() === 0) {
            throw new Exception("Insufficient stock for product ID " . $product_id);
        }
    }
    

    $conn->prepare("DELETE FROM Cart_Item WHERE cart_id = ?")->execute([$cart_id]);
    $conn->commit();

    echo json_encode([
        "status" => "OK",
        "message" => "Order placed successfully!",
        "discounts" => $discount_details,
        "trivia_discount_percent" => $discountPercent,
        "trivia_discount_amount" => round($trivia_discount_amount, 2),
        "final_amount" => $finalTotal,
        "delivery_date" => $deliveryDate
    ]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(["status" => "ERROR", "message" => $e->getMessage()]);
}
