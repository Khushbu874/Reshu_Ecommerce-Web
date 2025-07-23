<?php
include 'config/db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('location:login.php');
    exit();
}

$cod_charge = 50;
$grand_total = isset($_SESSION['grand_total']) ? floatval($_SESSION['grand_total']) : 0;
$total_payable = $grand_total + $cod_charge;
$user_id = $_SESSION['user_id'];
$msg = "";

// Save order if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, ?, 'COD', 'Pending')");
    $stmt->bind_param("id", $user_id, $total_payable);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $msg = "✅ Your order has been placed successfully with Cash on Delivery.";
        // Optionally clear cart
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    } else {
        $msg = "❌ Failed to place the order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirm COD Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background: #f4f4f4;
        }
        .container {
            background: #fff;
            max-width: 500px;
            margin: auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
        }
        .summary {
            margin-top: 20px;
            font-size: 16px;
        }
        .summary p {
            margin: 10px 0;
        }
        .btn {
            background-color: #28a745;
            padding: 12px 20px;
            color: #fff;
            border: none;
            margin-top: 20px;
            cursor: pointer;
            border-radius: 6px;
        }
        .btn:hover {
            background-color: #218838;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            color: #fff;
            background: #007bff;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Confirm Your Order (COD)</h2>

    <div class="summary">
        <p><strong>Grand Total:</strong> ₹<?= number_format($grand_total, 2) ?></p>
        <p><strong>Cash on Delivery Charge:</strong> ₹<?= number_format($cod_charge, 2) ?></p>
        <hr>
        <p><strong>Total Payable:</strong> ₹<?= number_format($total_payable, 2) ?></p>
    </div>

    <form method="post">
        <button class="btn" type="submit">Place Your Order</button>
    </form>

    <?php if ($msg): ?>
        <div class="message"><?= $msg ?></div>
    <?php endif; ?>
</div>
</body>
</html>