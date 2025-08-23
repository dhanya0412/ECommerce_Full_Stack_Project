<?php
session_start();
if (!isset($_SESSION['current_user'])) {
    header("Location: login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";
$dbo = new Database();
$conn = $dbo->conn;

$current_user_id = $_SESSION['current_user'];

// Fetch orders


$stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.delivery_date, o.total_amount, o.order_status, 
           p.product_name, p.product_price AS original_price,
           oi.order_item_id,  -- ✅ Add this line
           oi.order_quantity, oi.order_unit_price, oi.order_product_id
    FROM cust_order o
    JOIN order_item oi ON o.order_id = oi.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    WHERE o.cust_id = ?
    ORDER BY o.order_date DESC
");

$stmt->execute([$current_user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group orders by order_id
$grouped_orders = [];
foreach ($orders as $order) {
    $order_id = $order['order_id'];
    if (!isset($grouped_orders[$order_id])) {
        $grouped_orders[$order_id] = [
            'order_date' => $order['order_date'],
            'delivery_date' => $order['delivery_date'],
            'order_status' => $order['order_status'],
            'total_amount' => $order['total_amount'],
            'items' => []
        ];
    }
    $grouped_orders[$order_id]['items'][] = $order;
}

// Step 1: Get all order_item_ids for the user's orders
$order_ids = array_keys($grouped_orders);
$returned_order_items = [];

if (!empty($order_ids)) {
    // 1. Fetch order_item_ids for these orders
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $conn->prepare("SELECT order_item_id FROM order_item WHERE order_id IN ($placeholders)");
    $stmt->execute($order_ids);
    $order_item_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // 2. Fetch return requests for these order_item_ids
    if (!empty($order_item_ids)) {
        $placeholders_items = implode(',', array_fill(0, count($order_item_ids), '?'));
        $stmt = $conn->prepare("SELECT return_order_item_id FROM return_request WHERE return_order_item_id IN ($placeholders_items)");
        $stmt->execute($order_item_ids);
        $returned_order_items = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}


$reviewStmt = $conn->prepare("SELECT review_product_id FROM review WHERE cust_id = ?");
$reviewStmt->execute([$current_user_id]);
$submittedReviews = $reviewStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Orders</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/loader.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: #ff3399;
            color: white;
            padding: 15px 20px;
            font-weight: 500;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .list-group {
            list-style: none;
            padding: 0;
        }
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .text-end {
            text-align: right;
        }
        
        .badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .text-warning {
            color: #ffc107;
        }
        
        .text-info {
            color: #17a2b8;
        }
        
        .border {
            border: 1px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .border:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .p-3 {
            padding: 15px;
        }
        
        .mb-3 {
            margin-bottom: 15px;
        }
        
        .mt-3 {
            margin-top: 15px;
        }
        
        .mb-2 {
            margin-bottom: 10px;
        }
        
        .mt-2 {
            margin-top: 10px;
        }
        
        .fw-bold {
            font-weight: 600;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 10px;
            border: none;
            border-bottom: 2px solid #ddd;
            outline: none;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: transparent;
        }
        
        .form-control:focus, .form-select:focus {
            border-bottom: 2px solid #ff3399;
        }
        
        .form-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #ff3399;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
        }
        
        .btn-primary:hover {
            background: #e61e8c;
            box-shadow: 0 7px 20px rgba(255, 51, 153, 0.3);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        /* Logo styling */
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo span {
            font-size: 32px;
            font-weight: 700;
            color: #ff3399;
        }
        
        /* Custom improvements */
        .order-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .savings {
            font-weight: 500;
            color: #28a745;
            margin-top: 5px;
        }
        
        .rating-area {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        /* Return form styling */
        .returnForm, .ratingForm {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        
        /* Status badge styles */
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .status-delivered {
            background-color: #28a745;
            color: white;
        }
        
        .status-processing {
            background-color: #17a2b8;
            color: white;
        }
        
        .status-shipped {
            background-color: #6610f2;
            color: white;
        }
        
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        
        /* Stars for reviews */
        .star-rating {
            color: #ffc107;
            font-size: 18px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <span>Pookie<strong>Shop</strong></span>
    </div>
    
    <h2>Your Orders</h2>
    
    <?php foreach ($grouped_orders as $order_id => $order): ?>
        <div class="card mb-4">
            <div class="card-header">
                <strong>Order #<?= $order_id ?></strong> | 
                Date: <?= $order['order_date'] ?> | 
                <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                    <?= $order['order_status'] ?>
                </span> |
                Delivery Date: <?= $order['delivery_date'] ?>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php 
                    $calculated_total = 0;
                    $original_total = 0;
                    foreach ($order['items'] as $item): 
                        $item_total = $item['order_unit_price'] * $item['order_quantity'];
                        $original_total += $item['original_price'] * $item['order_quantity'];
                        $calculated_total += $item_total;
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?= $item['product_name'] ?> (x<?= $item['order_quantity'] ?>)
                            </div>
                            <div class="text-end">
                                <?php if ($item['order_unit_price'] < $item['original_price']): ?>
                                    <small class="text-muted">
                                        <del>₹<?= number_format($item['original_price'], 2) ?></del>
                                    </small><br>
                                    <strong>₹<?= number_format($item['order_unit_price'], 2) ?></strong><br>
                                    <span class="badge">
                                        You saved ₹<?= number_format(($item['original_price'] - $item['order_unit_price']) * $item['order_quantity'], 2) ?>!
                                    </span>
                                <?php else: ?>
                                    <strong>₹<?= number_format($item['order_unit_price'], 2) ?></strong>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="order-summary">
                    <div class="text-end">
                        <strong>Order Total: ₹<?= number_format($calculated_total, 2) ?></strong><br>
                        <?php if ($calculated_total < $original_total): ?>
                            <span class="savings">🎉 You saved ₹<?= number_format($original_total - $calculated_total, 2) ?> on this order!</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($order['order_status'] === 'Delivered'): ?>
                    <?php
                    $delivery_date = new DateTime($order['delivery_date']);
                    $now = new DateTime();
                    $interval = $delivery_date->diff($now);
                    $days_since_delivery = (int)$interval->format('%r%a');
                    ?>

                    <?php foreach ($order['items'] as $item): ?>
                        <div class="border p-3 mb-3">
                            <p><strong>Product:</strong> <?= htmlspecialchars($item['product_name']) ?></p>

                            <?php
                                $order_item_id = $item['order_item_id'];

                                // Check if the item has been returned
                                if (in_array($order_item_id, $returned_order_items)) {
                                    echo '<p class="text-warning fw-bold">This item has already been submitted for return.</p>';
                                } elseif ($days_since_delivery > 10) {
                                    echo '<p class="text-danger fw-bold">Return window closed (more than 10 days since delivery).</p>';
                                } else {
                            ?>
                                <!-- Display the form only if it's not already submitted for return -->
                                <form class="returnForm mt-2">
                                    <input type="hidden" name="order_item_id" value="<?= $order_item_id ?>">
                                    <div class="mb-2">
                                        <label for="return_reason_<?= $order_item_id ?>" class="form-label">Return Reason:</label>
                                        <textarea class="form-control" id="return_reason_<?= $order_item_id ?>" name="return_reason" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-warning">Submit Return Request</button>
                                    <p class="messageBox mt-2 text-info"></p>
                                </form>
                            <?php } ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php foreach ($order['items'] as $item): ?>
                    <?php if ($order['order_status'] === 'Delivered'): ?>
                        <div class="rating-area">
                            <?php if (!in_array($item['order_product_id'], $submittedReviews)): ?>
                                <form method="POST" action="ajaxhandler/submit_review.php" class="ratingForm">
                                    <input type="hidden" name="product_id" value="<?= $item['order_product_id'] ?>">
                                    <input type="hidden" name="cust_id" value="<?= $current_user_id ?>">
                                    <div class="mb-2">
                                        <label for="rating_<?= $item['order_product_id'] ?>" class="form-label">Rate <?= $item['product_name'] ?> (1-5):</label>
                                        <select class="form-select" id="rating_<?= $item['order_product_id'] ?>" name="rating" required>
                                            <option value="">Select rating</option>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="comment_<?= $item['order_product_id'] ?>" class="form-label">Comment (optional):</label>
                                        <textarea class="form-control" id="comment_<?= $item['order_product_id'] ?>" name="comment"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Rating</button>
                                </form>
                            <?php else: ?>
                                <p class="text-success mt-2">
                                    <span class="star-rating">★★★★★</span>
                                    Rating for <strong><?= $item['product_name'] ?></strong> submitted
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="/ecommerce_project/js/return_request.js"></script>
</body>
</html>