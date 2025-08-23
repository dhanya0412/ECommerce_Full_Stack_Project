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

// Fetch cart items with stock info + seller_id
$stmt = $conn->prepare("
    SELECT ci.cart_item_id, p.product_name, ci.cart_quantity, ci.cart_unit_price, p.stock_quantity, p.seller_id
    FROM Cart_Item ci
    JOIN Product p ON ci.cart_product_id = p.product_id
    JOIN Cart c ON ci.cart_id = c.cart_id
    WHERE c.cust_id = ?
");
$stmt->execute([$cust_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top customer per seller
$topCustomerQuery = "
    SELECT o.seller_id, o.cust_id
    FROM (
        SELECT p.seller_id, co.cust_id, SUM(coi.order_quantity * coi.order_unit_price) AS total_spent
        FROM cust_order co
        JOIN order_item coi ON co.order_id = coi.order_id
        JOIN product p ON p.product_id = coi.order_product_id
        GROUP BY p.seller_id, co.cust_id
    ) o
    INNER JOIN (
        SELECT seller_id, MAX(total_spent) AS max_spent
        FROM (
            SELECT p.seller_id, co.cust_id, SUM(coi.order_quantity * coi.order_unit_price) AS total_spent
            FROM cust_order co
            JOIN order_item coi ON co.order_id = coi.order_id
            JOIN product p ON p.product_id = coi.order_product_id
            GROUP BY p.seller_id, co.cust_id
        ) AS totals
        GROUP BY seller_id
    ) top ON o.seller_id = top.seller_id AND o.total_spent = top.max_spent
";

$topCustomerStmt = $conn->query($topCustomerQuery);
$topCustomers = [];
foreach ($topCustomerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $topCustomers[$row['seller_id']] = $row['cust_id'];
}

$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart | ShopEasy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            max-width: 1000px;
            margin: 0 auto;
            padding-top: 40px;
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
        
        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        h5 {
            color: #444;
            font-weight: 500;
        }
        
        .cart-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        
        .cart-container:hover {
            transform: translateY(-5px);
        }
        
        .table {
            margin-bottom: 30px;
        }
        
        .table th {
            font-weight: 600;
            color: #444;
            border-bottom: 2px solid #f5f5f5;
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f5f5f5;
        }
        
        .btn-outline-secondary {
            color: #777;
            border-color: #ddd;
            padding: 8px 20px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background: #f5f5f5;
            color: #555;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #ff3399;
            border: none;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
        }
        
        .btn-success:hover {
            background: #e61e8c;
            box-shadow: 0 7px 20px rgba(255, 51, 153, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-outline-success {
            color: #ff3399;
            border-color: #ff3399;
            border-radius: 0 4px 4px 0;
            transition: all 0.3s ease;
        }
        
        .btn-outline-success:hover {
            background: #ff3399;
            color: white;
        }
        
        .btn-danger {
            background: #ff6b6b;
            border: none;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
        
        .form-control {
            border: none;
            border-bottom: 2px solid #ddd;
            border-radius: 0;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-bottom: 2px solid #ff3399;
            box-shadow: none;
        }
        
        .input-group {
            max-width: 400px;
        }
        
        .text-success {
            color: #00c853 !important;
        }
        
        .text-warning {
            color: #ff9800 !important;
        }
        
        .discount-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .empty-cart-message {
            text-align: center;
            padding: 50px 0;
            color: #777;
        }
        
        .empty-cart-message p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .empty-cart-message .btn {
            padding: 10px 30px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        /* For quantity badge */
        .quantity-badge {
            background: #ff3399;
            color: white;
            border-radius: 50%;
            padding: 0 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* For discount badge */
        .discount-badge {
            display: inline-block;
            background: #fff3e0;
            color: #ff9800;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        /* For back in stock badge */
        .stock-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #00c853;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: right;
        }
        
        .product-name {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>Pookie<strong>Shop</strong></span>
        </div>
        
        <div class="cart-container">
            <div class="header-section">
                <h2>Your Cart</h2>
                <a href="e_commerce.php" class="btn btn-outline-secondary">← Back to Shopping</a>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart-message">
                    <p>Your cart is empty.</p>
                    <a href="e_commerce.php" class="btn btn-success">Continue Shopping</a>
                </div>
            <?php else: ?>
                <table class="table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Subtotal</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cart_items as $item): ?>
            <?php
                $original_price = $item['cart_unit_price'];
                $discount_applied = false;

                if (isset($topCustomers[$item['seller_id']]) && $topCustomers[$item['seller_id']] == $cust_id) {
                    $discount_applied = true;
                    $discount_amount = $original_price * 0.10;
                    $price = $original_price - $discount_amount;
                } else {
                    $price = $original_price;
                }

                $subtotal = $price * $item['cart_quantity'];
                $total += $subtotal;
            ?>
            <tr>
                <td>
                    <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                    <?php if ($item['cart_quantity'] == 1 && $item['stock_quantity'] > 0 && $item['stock_quantity'] < 5): ?>
                        <div class="stock-badge">Back in stock!</div>
                    <?php endif; ?>
                    <?php if ($discount_applied): ?>
                        <div class="discount-badge">10% Top Customer Discount Applied</div>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="number" class="cart-quantity-input" min="1"
                           data-cart-item-id="<?= $item['cart_item_id'] ?>"
                           value="<?= $item['cart_quantity'] ?>">
                </td>
                <td>
                    <div>₹<?= number_format($price, 2) ?></div>
                    <?php if ($discount_applied): ?>
                        <small class="text-muted"><del>₹<?= number_format($original_price, 2) ?></del></small>
                    <?php endif; ?>
                </td>
                <td>₹<?= number_format($subtotal, 2) ?></td>
                <td>
                    <button class="btn btn-danger btn-sm remove-from-cart" data-id="<?= $item['cart_item_id'] ?>">Remove</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
  $(function(){
    // whenever a quantity input changes…
    $('.cart-quantity-input').on('change', function(){
      const newQty     = parseInt($(this).val(), 10);
      const cartItemId = $(this).data('cart-item-id');

      if (newQty < 1) return;

      console.log('⏳ updating cart_item_id=' + cartItemId + ' to qty=' + newQty);

      $.post(
        '/ecommerce_project/ajaxhandler/cartAjax.php',
        {
          action:         'updateQuantity',
          cart_item_id:   cartItemId,
          quantity:       newQty
        },
        function(resp){
          console.log('✅ response:', resp);
          if (resp.status === 'SUCCESS') {
            location.reload();    // reload to see change
          } else {
            alert('Error: ' + resp.message);
          }
        },
        'json'
      );
    });
  });
</script>


                <div class="total-section">
                    <h5>Total: ₹<span id="subtotal"><?= number_format($total, 2) ?></span></h5>
                </div>

                <!-- Discount Code Input -->
                <div class="discount-section mt-4">
                    <h5>🎁 Have a discount code from trivia?</h5>
                    <div class="input-group mt-3">
                        <input type="text" class="form-control" id="discountCodeInput" placeholder="Enter your code">
                        <button class="btn btn-outline-success" id="applyDiscountBtn">Apply</button>
                    </div>
                    <div id="discountMessage" class="mt-2"></div>
                </div>

                <!-- Place Order Button -->
                <form id="placeOrderForm" class="text-end mt-4">
                    <input type="hidden" name="final_total" id="finalTotalInput" value="<?= $total ?>">
                    <input type="hidden" name="discount_code" id="discountCodeHidden" value="">
                    <button type="submit" class="btn btn-success">Place Order</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

<script>
    $(".remove-from-cart").click(function () {
        let cartItemId = $(this).data("id");
        $.post("/ecommerce_project/ajaxhandler/cartAjax.php", {
            action: "removeFromCart",
            cart_item_id: cartItemId
        }, function (response) {
            alert(response.message);
            location.reload();
        }, "json");
    });

    let discountPercent = 0;
    let originalTotal = <?= $total ?>;

    $('#applyDiscountBtn').click(function () {
      const code = $('#discountCodeInput').val().trim();
      if (!code) {
        $('#discountMessage').html('<div class="text-danger">Please enter a code.</div>');
        return;
      }

      $.post('ajaxhandler/triviaAjax.php', { action: 'check_code', code }, function (data) {
        if (data.valid) {
          discountPercent = data.discount;
          const discountedTotal = originalTotal - (originalTotal * discountPercent / 100);
          $('#discountMessage').html(`<div class="text-success">✅ Code applied! You will get ${discountPercent}% off. New total: ₹${discountedTotal.toFixed(2)}</div>`);
          $('#finalTotalInput').val(discountedTotal);
          $('#discountCodeHidden').val(code);
        } else {
          $('#discountMessage').html('<div class="text-danger">❌ Invalid or expired code.</div>');
        }
      }, 'json');
    });

    $("#placeOrderForm").submit(function (e) {
        e.preventDefault();
        if (confirm("Are you sure you want to place this order?")) {
            $.post("/ecommerce_project/ajaxhandler/place_order.php", {
                final_total: $('#finalTotalInput').val(),
                discount_code: $('#discountCodeHidden').val()
            }, function (response) {
                alert(response.message);
                if (response.status === "OK") {
                    location.href = "orders.php";
                }
            }, "json");
        }
    });
</script>
</body>
</html>
