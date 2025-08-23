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

// Fetch return requests for this customer
$sql = "
    SELECT rr.return_id, rr.request_id, rr.return_reason, rr.return_status, 
           co.order_date, co.total_amount, p.product_name
    FROM return_request rr
    JOIN order_item oi ON rr.return_order_item_id = oi.order_item_id
    JOIN cust_order co ON oi.order_id = co.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    WHERE co.cust_id = ?
    ORDER BY rr.return_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$current_user_id]);
$return_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Return Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        }
        
        .return-requests-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .return-requests-container:hover {
            transform: translateY(-5px);
        }
        
        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #ff3399;
            color: white;
            font-weight: 500;
            border: none;
            padding: 12px 15px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #eee;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 12px;
        }
        
        .bg-warning {
            background-color: #ffcc00 !important;
            color: #333;
        }
        
        .bg-success {
            background-color: #4cd964 !important;
        }
        
        .bg-danger {
            background-color: #ff3b30 !important;
        }
        
        .text-muted {
            color: #6c757d;
            text-align: center;
            font-size: 16px;
            font-weight: 300;
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
        
        .back-button {
            margin-top: 20px;
            text-align: center;
        }
        
        .btn-back {
            padding: 10px 25px;
            border: none;
            border-radius: 50px;
            background: #ff3399;
            color: white;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #e61e8c;
            box-shadow: 0 7px 20px rgba(255, 51, 153, 0.3);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>Shop<strong>Easy</strong></span>
        </div>
        
        <div class="return-requests-container">
            <h2>Your Return Requests</h2>

            <?php if (empty($return_requests)): ?>
                <p class="text-muted">You have not made any return requests.</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Product</th>
                            <th>Order Date</th>
                            <th>Total Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($return_requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['request_id']) ?></td>
                                <td><?= htmlspecialchars($request['product_name']) ?></td>
                                <td><?= htmlspecialchars($request['order_date']) ?></td>
                                <td>₹<?= number_format($request['total_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($request['return_reason']) ?></td>
                                <td>
                                    <?php
                                    $status = $request['return_status'];
                                    $badge = '';
                                    $icon = '';

                                    switch ($status) {
                                        case 'Pending':
                                            $badge = 'warning';
                                            $icon = '⏳';
                                            break;
                                        case 'Approved':
                                            $badge = 'success';
                                            $icon = '✅';
                                            break;
                                        case 'Rejected':
                                            $badge = 'danger';
                                            $icon = '❌';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= $icon ?> <?= $status ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="back-button">
                <a href="e_commerce.php" class="btn-back">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>