<?php
session_start();
if (!isset($_SESSION['current_user'])) {
    header("Location: login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";
$dbo = new Database();
$conn = $dbo->conn;

$cust_id = $_SESSION['current_user'];

// Get the discount from the URL (if present)
$discount_percent = isset($_GET['discount']) && is_numeric($_GET['discount']) ? floatval($_GET['discount']) : 0;

// Fetch total orders and raw totals
$summary_stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_spent,
        ROUND(AVG(total_amount), 2) AS avg_order_value
    FROM cust_order
    WHERE cust_id = ?
");
$summary_stmt->execute([$cust_id]);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

// Apply discount only if there's a discount
if ($discount_percent > 0 && $summary['total_orders'] > 0) {
    // Get the most recent order total
    $latest_order_stmt = $conn->prepare("
        SELECT total_amount
        FROM cust_order
        WHERE cust_id = ?
        ORDER BY order_date DESC
        LIMIT 1
    ");
    $latest_order_stmt->execute([$cust_id]);
    $latest_order = $latest_order_stmt->fetch(PDO::FETCH_ASSOC);

    if ($latest_order) {
        $discount_amount = ($discount_percent / 100) * $latest_order['total_amount'];
        $summary['total_spent'] -= $discount_amount;
        $summary['avg_order_value'] = $summary['total_orders'] > 0
            ? round($summary['total_spent'] / $summary['total_orders'], 2)
            : 0;
    }
}

// Most frequently bought product
$top_product_stmt = $conn->prepare("
    SELECT p.product_name, SUM(oi.order_quantity) AS qty
    FROM order_item oi
    JOIN cust_order co ON oi.order_id = co.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    WHERE co.cust_id = ?
    GROUP BY oi.order_product_id
    ORDER BY qty DESC
    LIMIT 1
");
$top_product_stmt->execute([$cust_id]);
$top_product = $top_product_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Order Summary</title>
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
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo span {
            font-size: 32px;
            font-weight: 700;
            color: #ff3399;
        }
        
        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .btn-back {
            padding: 10px 20px;
            border: 2px solid #ff3399;
            border-radius: 50px;
            color: #ff3399;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .btn-back:hover {
            background: #ff3399;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
            text-decoration: none;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -15px;
        }
        
        .col-half {
            width: 100%;
            padding: 15px;
        }
        
        @media (min-width: 768px) {
            .col-half {
                width: 50%;
            }
        }
        
        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border: none;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-primary {
            border-top: 5px solid #ff3399;
        }
        
        .card-success {
            border-top: 5px solid #28a745;
        }
        
        .card-warning {
            border-top: 5px solid #ffc107;
        }
        
        .card-info {
            border-top: 5px solid #17a2b8;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .card-title {
            color: #666;
            font-weight: 500;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        
        .card-title span {
            margin-left: 10px;
        }
        
        .card-text {
            color: #333;
            font-weight: 600;
            font-size: 28px;
        }
        
        .text-large {
            font-size: 24px;
        }
        
        .text-medium {
            font-size: 20px;
        }
        
        .alert {
            background: white;
            border-left: 4px solid #28a745;
            color: #333;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            position: relative;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            border-left-color: #28a745;
        }
        
        .alert strong {
            color: #28a745;
            font-weight: 600;
        }
        
        .btn-close {
            position: absolute;
            right: 15px;
            top: 15px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }
        
        .btn-close:hover {
            color: #666;
        }
        
        .fade {
            transition: opacity 0.15s linear;
        }
        
        .show {
            opacity: 1;
        }
        
        .emoji {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <span>Pookie<strong>Shop</strong></span>
    </div>
    
    <?php if ($discount_percent > 0): ?>
        <div class="alert alert-success fade show" role="alert">
            <span class="emoji">🎉</span> You received a <strong><?= htmlspecialchars($discount_percent) ?>% discount</strong> on your last order!
            <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
    <?php endif; ?>

    <div class="summary-header">
        <h2>Your Order Summary</h2>
        <a href="e_commerce.php" class="btn-back">← Back to Home</a>
    </div>

    <div class="row">
        <div class="col-half">
            <div class="card card-primary">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="emoji">🧾</span> Total Orders
                    </h5>
                    <p class="card-text"><?= $summary['total_orders'] ?></p>
                </div>
            </div>
        </div>

        <div class="col-half">
            <div class="card card-success">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="emoji">💰</span> Total Spent
                    </h5>
                    <p class="card-text">₹<?= number_format($summary['total_spent'], 2) ?></p>
                </div>
            </div>
        </div>

        <div class="col-half">
            <div class="card card-warning">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="emoji">📦</span> Most Purchased Product
                    </h5>
                    <p class="card-text text-medium"><?= $top_product ? $top_product['product_name'] . " (x" . $top_product['qty'] . ")" : "N/A" ?></p>
                </div>
            </div>
        </div>

        <div class="col-half">
            <div class="card card-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="emoji">📊</span> Average Order Value
                    </h5>
                    <p class="card-text">₹<?= number_format($summary['avg_order_value'], 2) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple script to handle the alert dismissal
    document.addEventListener('DOMContentLoaded', function() {
        const closeBtn = document.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                document.querySelector('.alert').style.display = 'none';
            });
        }
    });
</script>

</body>
</html>
