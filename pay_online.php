<?php
include("config/db.php");

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get grand total from checkout.php
$grand_total = $_SESSION['grand_total'] ?? 0;

$payment_successful = false;
$order_id = '';
$payment_time = '';

// On form submit
if (isset($_POST['pay_now'])) {
    $payment_successful = true;

    // Generate order ID
    $order_id = 'ORD' . strtoupper(uniqid());
    $payment_time = date("Y-m-d H:i:s");
    $user_id = $_SESSION['user_id'];
    $payment_method = 'Online';
    $status = 'Paid';

    // Insert into orders table
    $stmt = $conn->prepare("INSERT INTO orders (order_id, user_id, order_date, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisdss", $order_id, $user_id, $payment_time, $grand_total, $payment_method, $status);
    $stmt->execute();

    // Get cart items
    $cart_items = $conn->query("SELECT * FROM cart WHERE user_id = $user_id");
    while ($row = $cart_items->fetch_assoc()) {
        $product_id = $row['product_id'];
        $quantity = $row['quantity'];
        $chosen_color = $row['chosen_color'];
        
        // Get product price
        $product = $conn->query("SELECT price FROM products WHERE id = $product_id")->fetch_assoc();
        $price = $product['price'];

        // Insert into order_items
        $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, chosen_color) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("siids", $order_id, $product_id, $quantity, $price, $chosen_color);
        $stmt2->execute();
    }

    // Clear cart
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Payment</title>
    <style>
        .box {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            max-width: 600px;
            margin: auto;
            text-align: center;
        }
        .btn {
            background: #28a745;
            padding: 10px 25px;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 17px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .bill {
            margin-top: 20px;
            text-align: left;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Online Payment</h2>

    <p><strong>Grand Total:</strong> ₹<?= number_format($grand_total, 2) ?></p>

    <?php if ($payment_successful): ?>
        <h3 style="color: green;">✅ Payment Successful!</h3>
        <div class="bill">
            <h4>Payment Receipt</h4>
            <p><strong>Order ID:</strong> <?= $order_id ?></p>
            <p><strong>Amount Paid:</strong> ₹<?= number_format($grand_total, 2) ?></p>
            <p><strong>Payment Mode:</strong> Online</p>
            <p><strong>Date & Time:</strong> <?= $payment_time ?></p>
            <p><strong>Status:</strong> Paid</p>
        </div>
    <?php else: ?>
        <form method="post">
            <button type="submit" name="pay_now" class="btn">💳 Pay ₹<?= number_format($grand_total, 2) ?> Now</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
