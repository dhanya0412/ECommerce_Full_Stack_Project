<?php
session_start();

// Redirect if seller is not logged in
if (!isset($_SESSION['seller_id'])) {
    header("Location: sellerLogin.php");
    exit();
}

require_once('database/database.php');
$db = new Database();
$conn = $db->conn;

$seller_id = $_SESSION['seller_id'];
$seller_name = $_SESSION['seller_name'];

// ✅ Add this block here
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_id'], $_POST['new_stock'])) {
    $product_id = $_POST['product_id'];
    $new_stock = (int) $_POST['new_stock'];

    // Get old stock
    $old_stock_stmt = $conn->prepare("SELECT stock_quantity FROM product WHERE product_id = ? AND seller_id = ?");
    $old_stock_stmt->execute([$product_id, $seller_id]);
    $old_stock = (int) $old_stock_stmt->fetchColumn();

    // Update stock
    $conn->prepare("UPDATE product SET stock_quantity = ? WHERE product_id = ? AND seller_id = ?")
         ->execute([$new_stock, $product_id, $seller_id]);

    // If stock is updated from 0 to more than 0, notify in cart
    if ($old_stock == 0 && $new_stock > 0) {
        // Set a notify flag in cart item (we will interpret cart_quantity = 0 as "notify")
        $conn->prepare("
            UPDATE cart_item 
            SET cart_quantity = 1  -- assuming it was 0 before
            WHERE cart_product_id = :product_id 
            AND cart_quantity = 0
        ")->execute([':product_id' => $product_id]);
    }
}

// Fetch all products for this seller
$stmt = $conn->prepare("SELECT * FROM product WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top-selling product
$stmt = $conn->prepare("
    SELECT p.product_name, SUM(oi.order_quantity) AS total_sales
    FROM order_item oi
    JOIN product p ON oi.order_product_id = p.product_id
    JOIN cust_order o ON o.order_id = oi.order_id
    WHERE p.seller_id = ?
    GROUP BY p.product_id
    ORDER BY total_sales DESC
    LIMIT 1
");
$stmt->execute([$seller_id]);
$top_product = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch top customer for this seller
$stmt = $conn->prepare("
    SELECT o.cust_id, cu.cust_name, SUM(oi.order_quantity * oi.order_unit_price) AS total_spent
    FROM order_item oi
    JOIN cust_order o ON oi.order_id = o.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    JOIN customer cu ON o.cust_id = cu.customer_id
    WHERE p.seller_id = ?
    GROUP BY o.cust_id
    ORDER BY total_spent DESC
    LIMIT 1
");
$stmt->execute([$seller_id]);
$top_customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch total customers for this seller
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.cust_id) AS total_customers
    FROM cust_order o
    JOIN order_item oi ON o.order_id = oi.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    WHERE p.seller_id = ?
");
$stmt->execute([$seller_id]);
$total_customers = $stmt->fetchColumn();


// Fetch total transactions and total amount spent by customers
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.order_id) AS total_transactions,
           SUM(oi.order_quantity * oi.order_unit_price) AS total_spent
    FROM order_item oi
    JOIN cust_order o ON oi.order_id = o.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    WHERE p.seller_id = ?
");
$stmt->execute([$seller_id]);
$transaction_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent transactions (last 5)
$stmt = $conn->prepare("
    SELECT o.order_id, cu.cust_name, SUM(oi.order_quantity * oi.order_unit_price) AS total_spent
    FROM order_item oi
    JOIN cust_order o ON oi.order_id = o.order_id
    JOIN product p ON oi.order_product_id = p.product_id
    JOIN customer cu ON o.cust_id = cu.customer_id
    WHERE p.seller_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->execute([$seller_id]);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Fetch monthly revenue
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(co.order_date, '%Y-%m') AS month,
        SUM(oi.order_quantity * oi.order_unit_price) AS total_revenue
    FROM 
        cust_order co
    JOIN 
        order_item oi ON co.order_id = oi.order_id
    JOIN 
        product p ON oi.order_product_id = p.product_id
    WHERE 
        p.seller_id = :seller_id
    GROUP BY 
        month
    ORDER BY 
        month;
");
$stmt->execute(['seller_id' => $seller_id]);

$months = [];
$revenues = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $months[] = $row['month'];
    $revenues[] = $row['total_revenue'];
}


// Sales analytics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.order_id) AS total_orders, 
        SUM(oi.order_quantity) AS total_units_sold, 
        SUM(oi.order_quantity * oi.order_unit_price) AS total_revenue
    FROM order_item oi
    JOIN product p ON oi.order_product_id = p.product_id
    JOIN cust_order o ON o.order_id = oi.order_id
    WHERE p.seller_id = ?
");
$stmt->execute([$seller_id]);
$sales_stats = $stmt->fetch(PDO::FETCH_ASSOC);


// Fetch reviews for seller's products
$stmt = $conn->prepare("
    SELECT 
        r.review_id,
        r.review_product_id,
        p.product_name,
        r.review_rating,
        r.review_date,
        r.review_comment,
        c.cust_name
    FROM review r
    JOIN product p ON r.review_product_id = p.product_id
    JOIN customer c ON r.cust_id = c.customer_id
    WHERE p.seller_id = ?
    ORDER BY r.review_date DESC
");
$stmt->execute([$seller_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending return requests for this seller
$stmt = $conn->prepare("
    SELECT rr.*, cu.cust_name, p.product_name
FROM return_request rr
JOIN order_item oi ON rr.return_order_item_id = oi.order_item_id
JOIN cust_order o ON oi.order_id = o.order_id
JOIN product p ON oi.order_product_id = p.product_id
JOIN customer cu ON o.cust_id = cu.customer_id
WHERE rr.return_status = 'Pending' AND p.seller_id = ?
GROUP BY rr.return_id

");
$stmt->execute([$seller_id]);
$pending_returns = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: #333;
            padding: 40px;
        }

        .dashboard {
            background: white;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .dashboard:hover {
            transform: translateY(-5px);
        }

        h1, h2, h3 {
            color: #5e3370;
            font-weight: 600;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 22px;
            margin: 30px 0 15px 0;
        }

        h3 {
            font-size: 20px;
            margin: 25px 0 15px 0;
        }

        .logout {
            float: right;
            background: #9966cc;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(153, 102, 204, 0.2);
        }

        .logout:hover {
            background: #8a5cb8;
            box-shadow: 0 7px 20px rgba(153, 102, 204, 0.3);
        }

        .highlight {
            background-color: #f8f1f8;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            font-size: 16px;
            color: #5e3370;
            transition: all 0.3s ease;
        }
        
        .highlight:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #eee;
        }

        th {
            background-color: #9966cc;
            color: white;
            font-weight: 500;
        }

        tr:hover {
            background-color: #f8f1f8;
        }

        td {
            color: #333;
        }

        .top-customer-card {
            max-width: 400px;
            background: white;
            margin: 30px auto;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .top-customer-card:hover {
            transform: translateY(-5px);
        }

        .top-customer-card i {
            font-size: 3.5rem;
            color: #9966cc;
            margin-bottom: 15px;
        }

        .top-customer-card h5 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #5e3370;
        }

        .top-customer-card p {
            margin: 10px 0;
            font-size: 1rem;
            color: #333;
        }

        .chart-container {
            width: 100%;
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        button {
            padding: 12px;
            border: none;
            border-radius: 50px;
            background: #9966cc;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(153, 102, 204, 0.2);
            margin: 10px 5px;
        }
        
        button:hover {
            background: #8a5cb8;
            box-shadow: 0 7px 20px rgba(153, 102, 204, 0.3);
        }

        input, textarea {
            width: 100%;
            padding: 12px 10px;
            margin-bottom: 15px;
            border: none;
            border-bottom: 2px solid #ddd;
            outline: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus {
            border-bottom: 2px solid #9966cc;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 100px auto;
            padding: 40px;
            width: 400px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo span {
            font-size: 32px;
            font-weight: 700;
            color: #9966cc;
        }

        .action-btn {
            background-color: #9966cc;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 5px;
            font-size: 14px;
        }

        .edit-btn {
            background-color: #ffa500;
        }

        .delete-btn {
            background-color: #ff4d4d;
        }

        .add-btn {
            display: inline-block;
            background-color: #9966cc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            margin: 10px 0;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 5px 15px rgba(153, 102, 204, 0.2);
        }

        .add-btn:hover {
            background-color: #8a5cb8;
            box-shadow: 0 7px 20px rgba(153, 102, 204, 0.3);
        }

        .low-stock {
            background-color: #fff0f4;
        }

        .low-stock-warning {
            color: #d32f2f;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="logo">
            <span>Pookie<strong>Shop</strong></span>
        </div>
        
        <a href="logout.php" class="logout">Logout</a>
        <h1>Welcome, <?php echo htmlspecialchars($seller_name); ?> 👋</h1>

        <?php if ($top_product): ?>
            <div class="highlight">
                <strong>Top Selling Product:</strong> <?php echo htmlspecialchars($top_product['product_name']); ?> (<?php echo $top_product['total_sales']; ?> units sold)
            </div>
        <?php else: ?>
            <div class="highlight">
                <strong>No sales yet.</strong>
            </div>
        <?php endif; ?>

        <div class="highlight">
            <strong>Total Orders:</strong> <?php echo $sales_stats['total_orders'] ?? 0; ?> | 
            <strong>Total Units Sold:</strong> <?php echo $sales_stats['total_units_sold'] ?? 0; ?> | 
            <strong>Total Revenue:</strong> ₹<?php echo number_format($sales_stats['total_revenue'], 2) ?? '0.00'; ?>
        </div>

        <!-- Seller Transaction Summary -->
        <div class="highlight">
            <strong>Total Customers:</strong> <?php echo $total_customers ?? 0; ?> | 
            <strong>Total Transactions:</strong> <?php echo $transaction_summary['total_transactions'] ?? 0; ?> | 
            <strong>Total Amount Spent:</strong> ₹<?php echo number_format($transaction_summary['total_spent'], 2) ?? '0.00'; ?>
        </div>

        <h2>Customer Reviews on Your Products</h2>

        <?php if (count($reviews) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?= htmlspecialchars($review['product_name']) ?></td>
                            <td><?= htmlspecialchars($review['cust_name']) ?></td>
                            <td><?= str_repeat("⭐", (int)$review['review_rating']) ?> (<?= $review['review_rating'] ?>/5)</td>
                            <td><?= nl2br(htmlspecialchars($review['review_comment'])) ?></td>
                            <td><?= htmlspecialchars($review['review_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No reviews yet for your products.</p>
        <?php endif; ?>

        <h2>Pending Return Requests</h2>

        <?php if (count($pending_returns) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Return ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_returns as $request): ?>
                    <tr>
                        <td><?= $request['return_id'] ?></td>
                        <td><?= htmlspecialchars($request['cust_name']) ?></td>
                        <td><?= htmlspecialchars($request['product_name']) ?></td>
                        <td><?= htmlspecialchars($request['return_reason']) ?></td>
                        <td>
                            <form method="post" action="handleReturnDecision.php">
                                <input type="hidden" name="return_id" value="<?= $request['return_id'] ?>">
                                <button type="submit" name="decision" value="Approved" class="action-btn">Approve</button>
                                <button type="submit" name="decision" value="Rejected" class="action-btn delete-btn">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending return requests.</p>
        <?php endif; ?>

        <h3>Recent Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                </tr>
             </thead>
            <tbody>
                <?php foreach ($recent_transactions as $transaction): ?>
                    <tr>
                        <td><?php echo $transaction['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($transaction['cust_name']); ?></td>
                        <td>₹<?php echo number_format($transaction['total_spent'], 2); ?></td>
                    </tr>
                 <?php endforeach; ?>
             </tbody>
        </table>

        <h3>Your Products</h3>
        <button onclick="openAddModal()" class="add-btn">+ Add New Product</button>

        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Inventory Left</th>
                    <th>Total Units Sold</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
               
            <?php foreach ($products as $product): 
                $stmt = $conn->prepare("SELECT SUM(order_quantity) FROM order_item WHERE order_product_id = ?");
                $stmt->execute([$product['product_id']]);
                $sold_count = $stmt->fetchColumn() ?? 0;

                $low_stock = $product['stock_quantity'] < 5;
            ?>
            <tr <?php if ($low_stock) echo 'class="low-stock"'; ?>>
                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                <td>
                    <?php echo htmlspecialchars($product['stock_quantity']); ?>
                    <?php if ($low_stock): ?>
                        <span class="low-stock-warning">⚠ Low Stock</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $sold_count; ?></td>
                <td>
                    <button 
                        onclick="openEditModal(
                            <?php echo $product['product_id']; ?>, 
                            '<?php echo addslashes($product['product_name']); ?>',
                            <?php echo $product['product_price']; ?>, 
                            <?php echo $product['stock_quantity']; ?>
                        )"
                        class="action-btn edit-btn"
                    >
                        Edit
                    </button>
                    <form action="delete_product.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <button type="submit" class="action-btn delete-btn">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
        $low_stock_count = 0;
        foreach ($products as $p) {
            if ($p['stock_quantity'] < 5) $low_stock_count++;
        }
        ?>

        <?php if ($low_stock_count > 0): ?>
            <div class="highlight">
                <strong>⚠ Warning:</strong> You have <?php echo $low_stock_count; ?> product(s) with low stock. Please consider restocking soon.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($top_customer): ?>
        <div class="top-customer-card">
            <i class="bi bi-person-circle"></i>
            <h5>Top Customer</h5>
            <p><strong>Name:</strong> <?= htmlspecialchars($top_customer['cust_name']) ?></p>
            <p><strong>Total Spent:</strong> ₹<?= number_format($top_customer['total_spent'], 2) ?></p>
        </div>
    <?php endif; ?>

    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>

    <script>
        const months = <?php echo json_encode($months); ?>;
        const revenues = <?php echo json_encode($revenues); ?>;

        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Monthly Revenue (₹)',
                    data: revenues,
                    backgroundColor: 'rgba(153, 102, 204, 0.6)',
                    borderColor: 'rgba(153, 102, 204, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Sales Revenue',
                        font: {
                            family: 'Poppins',
                            size: 18,
                            weight: '600'
                        },
                        color: '#5e3370'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue (₹)',
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });
    </script>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Product</h3>
            <form id="editForm" method="POST" action="update_product.php">
                <input type="hidden" name="product_id" id="edit_product_id">
                <label>Product Name:</label>
                <input type="text" name="product_name" id="edit_product_name" required>

                <label>Product Price:</label>
                <input type="number" step="0.01" name="product_price" id="edit_product_price" required>

                <label>Stock Quantity:</label>
                <input type="number" name="stock_quantity" id="edit_stock_quantity" required>

                <button type="submit">Save Changes</button>
                <button type="button" onclick="closeEditModal()" style="background-color: #aaa;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3>Add New Product</h3>
            <form method="POST" action="add_product.php">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="text" name="category" placeholder="Category" required>
                <textarea name="description" placeholder="Description"></textarea>
                <input type="number" name="price" placeholder="Price" min="0" step="0.01" required>
                <input type="number" name="quantity" placeholder="Stock Quantity" min="0" required>
                <button type="submit">Add Product</button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="background-color: #aaa;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, price, quantity) {
            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_product_name').value = name;
            document.getElementById('edit_product_price').value = price;
            document.getElementById('edit_stock_quantity').value = quantity;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('addModal')) {
                document.getElementById('addModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>