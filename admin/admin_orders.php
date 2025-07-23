<?php
include '../config/db.php';

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("ss", $status, $order_id);
    $stmt->execute();
}

// Fetch orders with user info
$orders = $conn->query("
    SELECT 
        orders.id,
        orders.order_id, 
        orders.user_id,
        orders.order_date,
        orders.total_amount,
        orders.status,
        users.name AS customer_name,
        users.mobile,
        users.email,
        users.address, users.city, users.pincode, users.state
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    ORDER BY orders.order_date DESC
");

// Fetch order items with product info
$order_items_result = $conn->query("
    SELECT 
        order_items.*, 
        products.name AS product_name, 
        products.image 
    FROM order_items 
    JOIN products ON order_items.product_id = products.id
");

// Group items by order_id
$order_items = [];
while ($item = $order_items_result->fetch_assoc()) {
    $order_items[$item['order_id']][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - All Orders</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }

        .container {
            width: 100%;
            padding: 25px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        h2 {
            text-align: center;
            color: #2b6777;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #2b6777;
            color: white;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 6px;
            background: #fafafa;
        }

        .product-info, .customer-info {
            margin-bottom: 10px;
        }

        .status {
            font-weight: bold;
        }

        form {
            display: flex;
            gap: 10px;
        }

        select, button {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            background: #2b6777;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #1f4f5a;
        }

        .subheading {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .info-block {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h2>📋 Admin - Manage Orders</h2>

<div class="container">

    <table>
        <thead>
            <tr>
                <th rowspan="2">Order Number</th>
                <th rowspan="2">Order ID</th>
                
                <!-- Customer Info Headings -->
                <th colspan="5">Customer Information</th>

                <!-- Product Info Headings -->
                <th colspan="6">Product Information</th>

                <th rowspan="2">Total Amount</th>
                <th rowspan="2">Status</th>
                <th rowspan="2">Order Date & Time</th>
                <th rowspan="2">Action</th>
            </tr>
            <tr>
                <!-- Customer Info Sub-Headings -->
                <th>ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>Address</th>

                <!-- Product Info Sub-Headings -->
                <th>Product ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Qty</th>
                <th>Color</th>
                <th>Price</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <?php
                $items = $order_items[$order['order_id']] ?? [[]];
                $rowspan = count($items);
            ?>
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <?php if ($index == 0): ?>
                        <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($order['id']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($order['order_id']); ?></td>

                        <!-- Customer Info -->
                        <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($order['user_id']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($order['customer_name']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($order['mobile']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($order['email']); ?></td>
                        <td rowspan="<?= $rowspan ?>">
                            <?= htmlspecialchars($order['address']); ?>
                            <?= htmlspecialchars($order['city']); ?>
                            <?= htmlspecialchars($order['state']); ?>
                            <?= htmlspecialchars($order['pincode']); ?>
                        </td>
                    <?php endif; ?>

                    <!-- Product Info -->
                    <td><?= htmlspecialchars($item['product_id'] ?? ''); ?></td>
                    <td>
                        <?php if (!empty($item['image'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($item['image']); ?>" class="product-img" alt="">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['product_name'] ?? ''); ?></td>
                    <td><?= htmlspecialchars($item['quantity'] ?? ''); ?></td>
                    <td><span style="display:inline-block; width:60px; height:20px; border-radius:5%; background-color: <?= htmlspecialchars($item['chosen_color']); ?>; border:1px solid #ccc;"></span><?= htmlspecialchars($item['chosen_color'] ?? ''); ?></td>
                    <td>₹<?= number_format($item['price'] ?? 0, 2); ?></td>

                    <?php if ($index == 0): ?>
                        <!-- Total -->
                        <td rowspan="<?= $rowspan ?>">₹<?= number_format($order['total_amount'], 2); ?></td>

                        <!-- Status -->
                        <td rowspan="<?= $rowspan ?>" class="status"><?= ucfirst($order['status']); ?></td>

                        <!-- Order Date -->
                        <td rowspan="<?= $rowspan ?>" class="order_date"><?= ucfirst($order['order_date']); ?></td>

                        <!-- Action -->
                        <td rowspan="<?= $rowspan ?>">
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']); ?>">
                                <select name="status" required>
                                    <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status">Update</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>


</body>
</html>
