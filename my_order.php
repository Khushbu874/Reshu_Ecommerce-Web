<?php include 'includes/header.php'; ?>
<?php
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$orders->bind_param("i", $user_id);
$orders->execute();
$order_result = $orders->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📦 My Orders - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
        }

        .orders-container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #2b6777;
            margin-bottom: 30px;
        }

        .order-box {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }

        .order-box:last-child {
            border-bottom: none;
        }

        .order-id {
            font-weight: bold;
            color: #444;
            margin-bottom: 8px;
        }

        .order-meta {
            font-size: 14px;
            color: #777;
            margin-bottom: 8px;
        }

        .order-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            background: #ffc107;
            color: #333;
            font-weight: bold;
        }

        .order-status.shipped {
            background: #17a2b8;
            color: white;
        }

        .order-status.delivered {
            background: #28a745;
            color: white;
        }

        .order-items {
            margin-top: 10px;
        }

        .order-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
        }

        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 6px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 500;
            color: #333;
        }

        .item-qty, .item-color {
            font-size: 13px;
            color: #666;
        }

        .order-total {
            font-weight: bold;
            margin-top: 15px;
            color: #2b6777;
        }

        .track-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 16px;
            font-size: 14px;
            background: #2b6777;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .track-btn:hover {
            background: #1f4f5a;
        }

        .no-orders {
            text-align: center;
            color: #888;
            font-size: 16px;
            padding: 40px 0;
        }
    </style>
</head>
<body>

<div class="orders-container">
    <h2>📦 My Orders</h2>

    <?php if ($order_result->num_rows > 0): ?>
        <?php while ($order = $order_result->fetch_assoc()): ?>
            <div class="order-box">
                <?php
                    $order_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?" );
                    $order_items->bind_param("s", $order['order_id']);
                    $order_items->execute();
                    $items_result = $order_items->get_result();
                ?>

                <div class="order-id">Order ID: <?= htmlspecialchars($order['order_id']); ?></div>
                <!-- <div class="order-no">Order No: <?= htmlspecialchars($order['id']); ?></div> -->
                <div class="order-meta">
                    Date: <?= date('d M Y, h:i A', strtotime($order['order_date'])); ?> |
                    Status:
                    <span class="order-status <?= strtolower($order['status']); ?>">
                        <?= ucfirst($order['status']); ?>
                    </span>
                </div>

                <div class="order-items">
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <div class="order-item">
                            <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="Product Image">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']); ?></div>
                                <div class="order-meta">
                                    <span class="item-qty">Quantity: <?= $item['quantity']; ?></span> |
                                    Color: <span class="item-color" style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color: <?= htmlspecialchars($item['chosen_color']); ?>; border:1px solid #ccc;"></span>
                                    <?= htmlspecialchars($item['chosen_color']); ?>
                                </div>
                                <!-- <div class="item-qty">Quantity: <?= $item['quantity']; ?></div>
                                <div class="item-color">
                                    Color: <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color: <?= htmlspecialchars($item['chosen_color']); ?>; border:1px solid #ccc;"></span>
                                    <?= htmlspecialchars($item['chosen_color']); ?>
                                </div> -->
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="order-total">Total: ₹<?= number_format($order['total_amount'], 2); ?></div>
                <a href="track_order.php?order_id=<?= urlencode($order['order_id']); ?>" class="track-btn">Track Order</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-orders">🛒 You haven't placed any orders yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>
