<?php
include 'includes/header.php';
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? '';

// Fetch order info
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("si", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<div class='container'><p>Order not found or access denied.</p></div>";
    include 'includes/footer.php';
    exit();
}

// Fetch user address info
$user_stmt = $conn->prepare("SELECT address, city, state, pincode FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$full_address = "{$user['address']}, {$user['city']}, {$user['state']} - {$user['pincode']}";
?>

<style>
    body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
        }

    .container {
        max-width: 900px;
    }

    .order-header {
        background-color: #f1f9ff;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        border-left: 5px solid #3d9df6;
    }

    .tracking-timeline .list-group-item {
        background-color: #f9f9f9;
        border: none;
        padding: 12px 16px;
        font-size: 16px;
    }

    .tracking-timeline .list-group-item-success {
        background-color: #d4edda !important;
        font-weight: 500;
    }

    .list-group-item .status-icon {
        font-size: 18px;
        margin-right: 10px;
    }

    .order-item {
        background-color: #fff;
        border: 1px solid #ddd;
        margin-bottom: 10px;
        border-radius: 8px;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        transition: box-shadow 0.3s ease;
    }

    .order-item:hover {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    .order-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        margin-right: 15px;
    }

    .order-item .item-info {
        flex: 1;
    }

    .item-info .color-circle {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1px solid #ccc;
        margin: 0 5px 0 2px;
    }



    .card.billing-summary {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-left: 4px solid #3d9df6;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .billing-summary .card-body {
        padding: 20px;
    }

    .billing-summary .row {
        padding: 6px 0;
        align-items: center;
        font-size: 16px;
    }

    .billing-summary .row .col-6:last-child {
        text-align: right;
    }

    .billing-summary .text-success {
        color: #28a745 !important;
    }

    .billing-summary .fw-bold {
        font-weight: 600;
        font-size: 17px;
    }

    .billing-summary hr {
        margin: 10px 0 15px;
        border-top: 1px solid #ddd;
    }

    .view-product-btn {
        display: inline-block;
        margin-top: 10px;
        padding: 6px 16px;
        font-size: 14px;
        background: #2b6777;
        color: white;
        text-decoration: none;
        border-radius: 6px;
    }

    .view-product-btn:hover {
        background: #1f4f5a;
    }

    .item-details{
        font-size: 13px;
        color: #666;
    }
    

</style>

<div class="container mt-4 mb-5">

    <div class="order-header">
        <h3>📦 Tracking Order #<?= htmlspecialchars($order_id); ?></h3>
        <p><strong>Payment Status:</strong> <?= htmlspecialchars($order['status']); ?></p>
        <p><strong>Placed On:</strong> <?= date('d M Y, h:i A', strtotime($order['order_date'])); ?></p>
        <p><strong>Total Amount to pay:</strong> ₹<?= number_format($order['total_amount'], 2); ?></p>
        <p><strong>Shipping Address:</strong> <?= htmlspecialchars($full_address); ?></p>
    </div>

    <h4 class="mb-3">🚚 Tracking Timeline</h4>
    <ul class="list-group tracking-timeline mb-4">
        <?php
        $steps = ["Order Placed", "Packed", "Shipped", "Out for Delivery", "Delivered"];
        $statusIndex = array_search(ucfirst($order['status']), $steps);

        foreach ($steps as $index => $step):
            $done  = ($index <= $statusIndex);
            $icon  = $done ? "✅" : "⏳";
            $class = $done ? "list-group-item-success" : "";
        ?>
            <li class="list-group-item <?= $class; ?>">
                <span class="status-icon"><?= $icon; ?></span> <?= $step; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Bill Summary -->
    <h4 class="mb-3">🧾 Order Summary</h4>
    <div class="card billing-summary mb-4">
        <div class="card-body">
            <?php
                $extra_Charge = 0;
                if (($order['status']) == 'Pending') {
                    $extra_Charge = 40; // COD charge
                }
            ?>
            <div class="row mb-1">
                <div class="col-6"><strong>Subtotal:</strong></div>
                <div class="col-6">₹<?= number_format($order['subtotal'] ?? $order['total_amount'] - $extra_Charge, 2); ?></div>
            </div>

            <div class="row mb-1">
                <div class="col-6"><strong>Delivery Charges:</strong></div>
                <div class="col-6">₹<?= number_format($order['delivery_charge'] ?? 0, 2); ?></div>
            </div>

            <div class="row mb-1">
                <div class="col-6"><strong>Extra Charges (COD Charge):</strong></div>
                <div class="col-6">₹<?= number_format($extra_Charge, 2); ?></div>
            </div>

            <?php if (!empty($order['discount'])): ?>
            <div class="row mb-1">
                <div class="col-6"><strong>Discount:</strong></div>
                <div class="col-6 text-success">− ₹<?= number_format($order['discount'], 2); ?></div>
            </div>
            <?php endif; ?>

            <hr>

            <div class="row">
                <div class="col-6"><strong>Total Amount:</strong></div>
                <div class="col-6 fw-bold">₹<?= number_format($order['total_amount'], 2); ?></div>
            </div>
        </div>
    </div>

    <h4 class="mb-3">🛍️ Order Items</h4>
    <?php
    $item_stmt = $conn->prepare("
        SELECT order_items.*, products.id AS product_id 
        FROM order_items 
        JOIN products ON order_items.name = products.name 
        WHERE order_items.order_id = ?
    ");
    $item_stmt->bind_param("s", $order_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();

    while ($item = $item_result->fetch_assoc()):
    ?>
        <div class="order-item">
            <img 
                src="assets/images/<?= htmlspecialchars($item['image']); ?>" 
                alt="<?= htmlspecialchars($item['name']); ?>"
            >
            <div class="item-info">
                <div><strong><?= htmlspecialchars($item['name']); ?></strong></div>
                <div class="item-details">
                    Quantity: <?= $item['quantity']; ?> |
                    Color: 
                    <span 
                        class="color-circle" 
                        style="background-color:<?= htmlspecialchars($item['chosen_color']); ?>;"
                    ></span>
                    <?= htmlspecialchars($item['chosen_color']); ?>
                </div>
                <div class="item-details">Price: ₹<?= number_format($item['price'], 2); ?></div>
                <a href="product.php?id=<?= $item['product_id']; ?>" class="view-product-btn">View Product</a>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>
