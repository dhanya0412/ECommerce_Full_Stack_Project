<?php
session_start();
if (!isset($_SESSION['current_user'])) {
    header("Location: login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/ecommerce_project/database/database.php";
$dbo = new Database();
$conn = $dbo->conn;

$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    echo "Invalid product ID.";
    exit();
}

// Fetch product details
$product_stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ?");
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found.";
    exit();
}

// Fetch reviews for this product
$review_stmt = $conn->prepare("
    SELECT r.review_rating, r.review_comment, r.review_date, c.cust_name
    FROM review r
    JOIN customer c ON r.cust_id = c.customer_id
    WHERE r.review_product_id = ?
    ORDER BY r.review_date DESC
");
$review_stmt->execute([$product_id]);
$reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['product_name']) ?> - Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <a href="e_commerce.php" class="btn btn-outline-secondary mb-3">← Back to Products</a>

    <div class="card p-4">
        <h2><?= htmlspecialchars($product['product_name']) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($product['product_category']) ?></p>
        <p><?= nl2br(htmlspecialchars($product['product_description'])) ?></p>
        <h4>$<?= number_format($product['product_price'], 2) ?></h4>

        <button class="btn btn-success mt-3" id="addToCartBtn" data-id="<?= $product['product_id'] ?>">Add to Cart</button>
    </div>

    <div class="mt-5">
        <h4>Reviews</h4>
        <?php if (count($reviews) === 0): ?>
            <p class="text-muted">No reviews yet for this product.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($reviews as $rev): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($rev['cust_name']) ?></strong>
                        <span class="text-warning">★ <?= $rev['review_rating'] ?>/5</span>
                        <br>
                        <?= htmlspecialchars($rev['review_comment']) ?>
                        <br>
                        <small class="text-muted"><?= $rev['review_date'] ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
    $("#addToCartBtn").click(function () {
        const productId = $(this).data("id");

        $.post("/ecommerce_project/ajaxhandler/searchAjax.php", {
            action: "addToCart",
            product_id: productId
        }, function (response) {
            alert(response.message);
        }, "json");
    });
</script>

</body>
</html>
