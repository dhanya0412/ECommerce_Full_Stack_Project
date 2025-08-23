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

// Handle search & filters
$search_query = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Fetch distinct categories for dropdown
$category_stmt = $conn->query("SELECT DISTINCT product_category FROM Product");
$categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build dynamic product query
$query = "SELECT * FROM Product WHERE 1=1";
$params = [];

if ($search_query) {
    $query .= " AND product_name LIKE :search_query";
    $params[':search_query'] = "%$search_query%";
}
if ($category) {
    $query .= " AND product_category = :category";
    $params[':category'] = $category;
}
if ($min_price !== '') {
    $query .= " AND product_price >= :min_price";
    $params[':min_price'] = $min_price;
}
if ($max_price !== '') {
    $query .= " AND product_price <= :max_price";
    $params[':max_price'] = $max_price;
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$product = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search history (still works)
if ($search_query) {
    $insert_search = $conn->prepare("INSERT INTO Search_History (cust_id, search_query) VALUES (:cust_id, :search_query)");
    $insert_search->execute([':cust_id' => $cust_id, ':search_query' => $search_query]);
}

$search_history = $conn->prepare("SELECT search_query, search_date FROM Search_History WHERE cust_id = :cust_id ORDER BY search_date DESC");
$search_history->execute([':cust_id' => $cust_id]);
$search_history = $search_history->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PookieSHop Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
            padding-bottom: 40px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 80px;
        }
        
        h1 {
            color: #333;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            font-size: 32px;
        }
        
        h4 {
            color: #444;
            font-weight: 500;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo span {
            font-size: 32px;
            font-weight: 700;
            color: #ff3399;
        }
        
        .search-filters {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .search-filters:hover {
            transform: translateY(-5px);
        }
        
        input.form-control, select.form-select {
            border: none;
            border-bottom: 2px solid #ddd;
            border-radius: 0;
            padding: 10px 5px;
            font-size: 15px;
            background: transparent;
            transition: all 0.3s ease;
        }
        
        input.form-control:focus, select.form-select:focus {
            border-bottom: 2px solid #ff3399;
            box-shadow: none;
        }
        
        .btn-primary {
            background: #ff3399;
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
        }
        
        .btn-primary:hover {
            background: #e61e8c;
            box-shadow: 0 7px 20px rgba(255, 51, 153, 0.3);
        }
        
        .btn-outline-primary {
            color: #ff3399;
            border-color: #ff3399;
        }
        
        .btn-outline-primary:hover {
            background: #ff3399;
            color: white;
        }
        
        .btn-outline-secondary, .btn-outline-info, .btn-outline-danger {
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover, .btn-outline-info:hover, .btn-outline-danger:hover {
            transform: translateY(-2px);
        }
        
        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 12px 15px;
            transition: background 0.3s ease;
        }
        
        .list-group-item:hover {
            background: #f8f9fa;
        }
        
        .history-section, .products-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none !important;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .product-card h5 {
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .product-card a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .product-card a:hover {
            color: #ff3399;
        }
        
        .product-card p {
            font-size: 18px;
            font-weight: 500;
            color: #ff3399;
            margin-bottom: 15px;
        }
        
        .product-card .btn {
            border-radius: 50px;
            padding: 8px 20px;
        }
        
        .product-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .top-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 10px 0;
        }
        
        .dropdown-item {
            padding: 8px 20px;
            transition: background 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: #f5f7fa;
        }
    </style>
</head>
<body>

<!-- 🧭 Top Right Controls -->
<div class="top-controls">
    <!-- 🛒 Cart Icon -->
    <a href="cart.php" class="btn btn-outline-primary" title="Your Cart">🛒</a>

    <!-- 🔻 Your Account Dropdown -->
    <div class="btn-group">
        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            Your Account
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="orders.php">Your Orders</a></li>
            <li><a class="dropdown-item" href="orders_summary.php">Your Summary</a></li>
            <li><a class="dropdown-item" href="return_requests.php">Your Return Requests</a></li>
        </ul>
    </div>

    <!-- Add inside your Bootstrap top nav or header -->
    <div class="dropdown">
        <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            🎮 Play Trivia
        </button>
         <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="trivia.php">🧠 Today's Trivia</a></li>
            <li><a class="dropdown-item" href="leaderboard.php">🏆 Leaderboard</a></li>
        </ul>
    </div>

    <!-- 🚪 Logout -->
    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
</div>

<div class="container">
    <div class="logo">
        <span>Pookie<strong>Shop</strong></span>
    </div>

    <h1>Welcome to PookieShop</h1>

    <!-- 🔍 Search + Category + Price Filter -->
    <div class="search-filters">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search for product..." value="<?= htmlspecialchars($search_query) ?>">
            </div>

            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $cat == $category ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <input type="number" name="min_price" class="form-control" placeholder="Min ₹" value="<?= htmlspecialchars($min_price) ?>">
            </div>

            <div class="col-md-2">
                <input type="number" name="max_price" class="form-control" placeholder="Max ₹" value="<?= htmlspecialchars($max_price) ?>">
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Go</button>
            </div>
        </form>
    </div>

    <!-- 🕘 Search History -->
    <div class="history-section">
        <h4>Your Search History</h4>
        
        <!-- 🗑️ Clear Search History -->
        <form id="clearSearchForm" class="mb-3">
            <button type="submit" class="btn btn-sm btn-outline-danger">Clear Search History</button>
        </form>
        
        <ul class="list-group">
            <?php foreach ($search_history as $search): ?>
                <li class="list-group-item">
                    <?= htmlspecialchars($search['search_query']) ?> - <?= $search['search_date'] ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- 🛍️ Products -->
    <div class="products-section">
        <h4>Available Products</h4>
        <div class="row">
            <?php foreach ($product as $p): ?>
                <?php 
                    $imageWidth = 400;
                    $imageHeight = 200;
                ?>
                <div class="col-md-6">
                    <div class="product-card">
                        <img src="https://img.freepik.com/premium-vector/cute-funny-ghost-isolated-white-background-hand-drawn-doodle-illustration-halloween_361363-967.jpg" alt="<?= htmlspecialchars($p['product_name']) ?>" class="product-image">
                        <h5>
                            <a href="product.php?product_id=<?= $p['product_id'] ?>">
                                <?= htmlspecialchars($p['product_name']) ?>
                            </a>
                        </h5>
                        <p>₹<?= number_format($p['product_price'], 2) ?></p>
                        <button class="btn btn-primary add-to-cart" data-id="<?= $p['product_id'] ?>">Add to Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 🧠 JS -->
<script>
    // Add to Cart
    $(".add-to-cart").click(function () {
        let productId = $(this).data("id");

        $.post("/ecommerce_project/ajaxhandler/searchAjax.php", {
            action: "addToCart",
            product_id: productId
        }, function (response) {
            alert(response.message);
        }, "json");
    });

    // Clear Search History
    $("#clearSearchForm").submit(function (e) {
        e.preventDefault();
        if (confirm("Are you sure you want to clear your search history?")) {
            $.post("/ecommerce_project/ajaxhandler/clear_search_history.php", {}, function (response) {
                alert(response.message);
                location.reload();
            }, "json");
        }
    });
</script>

</body>
</html>