<?php include 'includes/header.php'; ?>
<?php
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$msg       = "";
$cart_rows = [];        // unified list of items (buy‑now or cart)
$tot_mrp   = 0;         // sum of market_price * qty
$tot_sp    = 0;         // sum of selling_price * qty
$tot_disc  = 0;         // sum of discount * qty
$cod_charge      = 40;  // 💡 COD charge you can customize
$show_cod_block  = false;
$show_payment_block  = false;


/* ── detect flow ─────────────────────────────────────────────── */
$is_buy_now = isset($_GET['buy_now']) && isset($_SESSION['buy_now_product_id']);

/* Fetch user info from DB */
$user_info = $conn->query("SELECT address, city, pincode, state, mobile FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$address   = $user_info['address'] ?? '';
$city   = $user_info['city'] ?? '';
$pincode   = $user_info['pincode'] ?? '';
$state   = $user_info['state'] ?? '';
$phone     = $user_info['mobile'] ?? '';

// ✨ Update shipping info if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_cart'])) {
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $city    = $conn->real_escape_string($_POST['city'] ?? '');
    $state   = $conn->real_escape_string($_POST['state'] ?? '');
    $pincode = $conn->real_escape_string($_POST['pincode'] ?? '');
    $phone   = $conn->real_escape_string($_POST['phone'] ?? '');

    $conn->query("
        UPDATE users SET 
            address = '$address', 
            city = '$city',
            state = '$state',
            pincode = '$pincode',
            mobile = '$phone'
        WHERE id = $user_id
    ");
}


// ✅ Handle quantity update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart']) && !$is_buy_now) {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $quantity = max(1, (int)$quantity);
            
            if (!$is_buy_now) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $stmt->execute();
            }
        }
    }
}

/* ── assemble product rows in one array ------------------------ */
if ($is_buy_now) {
    $pid = intval($_SESSION['buy_now_product_id']);
    $color = $_SESSION['buy_now_color'] ?? '';
    $res = $conn->query("SELECT id,name,price,market_price, stock, image FROM products WHERE id=$pid LIMIT 1");
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        $row['quantity'] = isset($_POST['quantities'][$pid]) ? max(1, (int)$_POST['quantities'][$pid]) : 1;
        $row['chosen_color'] = $color;
        $cart_rows[]         = $row;
    }
} else {
    $q = "
        SELECT p.id, p.name, p.price, p.market_price, p.stock, p.image,
                c.quantity, c.chosen_color
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = $user_id
    ";
    $result = $conn->query($q);
    while ($row = $result->fetch_assoc()) {
        $cart_rows[] = $row;
    }
}


/* ── calc billing figures -------------------------------------- */
foreach ($cart_rows as $it) {
    $q   = $it['quantity'];
    $mrp = $it['market_price'] * $q;
    $sp  = $it['price']        * $q;
    $tot_mrp  += $mrp;
    $tot_sp   += $sp;
    $tot_disc += ($mrp - $sp);
}
$delivery_charge = ($tot_sp >= 500 || $tot_sp == 0) ? 0 : 49;
$grand_total = 0;
foreach ($cart_rows as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}
$final_total     = $grand_total;  // Will be updated if COD selected

/* ── handle place‑order submission ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_cart'])) {
    $_SESSION['order_cart']   = $cart_rows;
    $_SESSION['order_totals'] = [
        'mrp'      => $tot_mrp,
        'discount' => $tot_disc,
        'delivery' => $delivery_charge,
        'grand'    => $grand_total,
    ];
    $_SESSION['order_info'] = [
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'pincode' => $pincode,
        'phone'   => $phone,
        'payment' => $_POST['payment'],
    ];

    // ✅ Store grand_total and delivery_charge separately
    $_SESSION['grand_total']     = $grand_total;
    $_SESSION['delivery_charge'] = $delivery_charge;

    // Redirect based on payment method
    if ($_POST['payment'] === 'Cash on Delivery') {
        $show_cod_block = true;
        $final_total = $grand_total + $cod_charge;
    } elseif ($_POST['payment'] === 'Online') {
        $show_payment_block = true;
        $final_total = $grand_total;
    } else {
        $msg = "❌ Invalid payment method selected.";
    }
}

$order_success = false;
$order_message = "";

// COD Confirm Order Save
if (isset($_POST['confirm_order']) ) {
    $unique_order_id = 'ORD' . time() . rand(0001, 9999);

    // Prepare values
    $final_total = $grand_total + $cod_charge;
    $payment_method = 'Cash on Delivery';
    $status = 'Pending';

    // Insert into `orders` table
    $order_stmt = $conn->prepare("INSERT INTO orders (order_id, user_id, total_amount, payment_method, status, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $order_stmt->bind_param("sidss", $unique_order_id, $user_id, $final_total, $payment_method, $status);
    $order_stmt->execute();

    // Insert into `order_items` table
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, quantity, chosen_color, image, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($cart_rows as $it) {
        $item_stmt->bind_param("sissssd",
            $unique_order_id,
            $it['id'],
            $it['name'],
            $it['quantity'],
            $it['chosen_color'],
            $it['image'],
            $it['price']
        );
        $item_stmt->execute();
    }

    // Clear cart or buy_now session
    if ($is_buy_now) {
        unset($_SESSION['buy_now_product_id']);
        unset($_SESSION['buy_now_color']);
    } else {
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    }

    $order_success = true;
    if (isset($_GET['order']) && $_GET['order'] == 'success') {
        echo "<script>alert('Order placed successfully!');</script>";
    }
    header("Location: index.php");
    exit();
}

// Online Payment Save
if (isset($_POST['pay_now'])) {
    // Generate unique order ID
    $unique_order_id = 'ORD' . time() . rand(0001, 9999);

    // Prepare values
    $final_total = $grand_total;
    $payment_method = 'Online';
    $status = 'Paid';

    // Insert into `orders` table
    $order_stmt = $conn->prepare("INSERT INTO orders (order_id, user_id, total_amount, payment_method, status, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $order_stmt->bind_param("sidss", $unique_order_id, $user_id, $final_total, $payment_method, $status);
    $order_stmt->execute();

    // Insert into `order_items` table
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, quantity, chosen_color, image, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($cart_rows as $it) {
        $item_stmt->bind_param("sissssd",
            $unique_order_id,
            $it['id'],
            $it['name'],
            $it['quantity'],
            $it['chosen_color'],
            $it['image'],
            $it['price']
        );
        $item_stmt->execute();
    }

    // ✅ Step 3: Clear cart or buy now session
    if ($is_buy_now) {
        unset($_SESSION['buy_now_product_id']);
        unset($_SESSION['buy_now_color']);
    } else {
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    }
    $order_success = true;
    if (isset($_GET['order']) && $_GET['order'] == 'success') {
        echo "<script>alert('Order placed successfully!');</script>";
    }
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout - Reshu eCommerce</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: #f2f2f2;
        margin: 0;
    }
    .container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .checkout-box {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,.08);
    }
    h2 {
        text-align: center;
        color: #2b6777;
        margin-top: 0
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px
    }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #e0e0e0;
        text-align: left;
        font-size: 15px
    }
    th {
        background: #2b6777;
        color: #fff
    }
    .item-img {
        width: 60px;
        height: 60px;
        object-fit: contain
    }
    .total-row td {
        font-weight: bold
    }
    input, textarea, select {
        width: 100%;
        padding: 10px;
        margin-top: 12px;
        border: 1px solid #ccc;
        border-radius: 6px
    }
    .place-btn {
        margin-top: 25px;
        background: #2b6777;
        color: #fff;
        border: none;
        padding: 12px 28px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer
    }
    .place-btn:hover {
        background: #1f4f5a
    }
    .msg {
        color: #27ae60;
        text-align: center;
        font-size: 18px;
        margin-top: 20px
    }
    .qty-wrapper {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .qty-wrapper input[type="number"] {
        width: 45px;
        padding: 4px 6px;
        font-size: 13px;
        text-align: center;
    }
    .qty-wrapper button {
        padding: 5px 9px;
        font-size: 12px;
        background: #2b6777;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .qty-wrapper button:hover {
        background: #1f4f5a;
    }

</style>
</head>
<body>

<div class="container">
    <div class="checkout-box">
        <h2>Checkout Summary</h2>

        <?php if ($msg): ?>
            <p class="msg"><?= $msg ?></p>
        <?php elseif (empty($cart_rows)): ?>
            <p style="text-align:center">Your cart is empty.</p>
        <?php else: ?>
            <form method="POST" id="checkout-form">
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th><th>Product Name</th><th>Product</th><th>Qty</th><th>Color</th><th>MRP</th><th>Selling&nbsp;Price</th><th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_rows as $it): ?>
                            <tr class="item-row" data-unit-price="<?= $it['price']; ?>">
                                <td><?= $it['id']; ?></td>
                                <td><?= htmlspecialchars($it['name']); ?></td>
                                <td><img src="assets/images/<?= htmlspecialchars($it['image']); ?>" alt="" class="item-img"></td>
                                <td>
                                    <?php if (!$is_buy_now): ?>
                                        <input type="number"
                                            name="quantities[<?= $it['id']; ?>]"
                                            value="<?= $it['quantity']; ?>"
                                            min="1"
                                            max="<?= $it['stock']; ?>"
                                            class="quantity-input"
                                            style="width:50px; text-align:center">
                                    <?php else: ?>
                                        <input type="number"
                                            name="quantities[<?= $it['id']; ?>]"
                                            value="<?= $it['quantity']; ?>"
                                            min="1"
                                            max="<?= $it['stock']; ?>"
                                            class="quantity-input"
                                            style="width:50px; text-align:center">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="display:inline-block; width:60px; height:20px; border-radius:5%; background-color: <?= htmlspecialchars($item['chosen_color']); ?>; border:1px solid #ccc;"></span>
                                    <?= $it['chosen_color'] ? htmlspecialchars($it['chosen_color']) : '-'; ?>
                                </td>
                                <td>₹<?= number_format($it['market_price'], 2); ?></td>
                                <td>₹<span class="unit-price"><?= number_format($it['price'], 2); ?></span></td>
                                <td>₹<span class="item-total"><?= number_format($it['price'] * $it['quantity'], 2); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" name="update_cart" class="place-btn" style="margin-top:15px;">Update Quantities</button>

                <!-- Billing Break‑up -->
                <table style="margin-top:25px">
                    <tr>
                        <td>Total MRP</td>
                        <td style="text-align:right">₹<span id="total-mrp"><?= number_format($tot_mrp, 2); ?></span></td>
                    </tr>
                    <tr>
                        <td>Total Discount</td>
                        <td style="text-align:right;color:#d33">‑₹<span id="total-discount"><?= number_format($tot_disc, 2); ?></span></td>
                    </tr>
                    <tr>
                        <td>Delivery Charge</td>
                        <td style="text-align:right" id="delivery-charge-display">
                            <?= $delivery_charge ? '₹' . number_format($delivery_charge, 2) : 'FREE'; ?>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td>Grand Total</td>
                        <td style="text-align:right">₹<span id="grand-total"><?= number_format($grand_total, 2); ?></span></td>
                    </tr>
                </table>

                <!-- address / phone / payment -->
                <!-- ✨ Editable shipping section -->
                <h3 style="margin-top:30px;">Shipping Info</h3>
                <label>Address (Street Address)</label>
                <textarea name="address" required><?= htmlspecialchars($address); ?></textarea>

                <label>City/District</label>
                <input type="text" name="city" value="<?= htmlspecialchars($city); ?>" required>

                <label>State</label>
                <input type="text" name="state" value="<?= htmlspecialchars($state); ?>" required>

                <label>Pincode</label>
                <input type="text" name="pincode" value="<?= htmlspecialchars($pincode); ?>" required>

                <label>Phone number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($phone); ?>" required>

                <label>Payment Method</label>
                <select name="payment" required>
                    <option value="" disabled selected>Select payment method</option>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                    <option value="Online">Online (Mock)</option>
                </select>

                <!-- Hidden input for Grand Total -->
                <input type="hidden" name="grand_total" value="<?= $grand_total ?>">
                <button type="submit" class="place-btn">Proceed Your Order</button>

                <?php if ($show_cod_block): ?>
                    <div style="margin-top: 40px; padding: 25px; background: #fffbe6; border: 1px solid #f1d888; border-radius: 8px;">
                        <h3 style="color:#b98d00;">Cash on Delivery Selected</h3>
                        <p>Additional COD Charge: ₹<?= number_format($cod_charge, 2); ?></p>
                        <p><strong>Final Payable Amount: ₹<?= number_format($final_total, 2); ?></strong></p>
                        <p style="margin-top: 20px;">
                            <button 
                                name="confirm_order" 
                                type="submit" 
                                style="background: #2b6777; color:#fff; padding: 12px 24px; border: none; border-radius: 6px; cursor:pointer;">
                                    Confirm Order
                            </button>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (isset($show_payment_block) && $show_payment_block): ?>
                    <!-- ✅ UI: Payment Success Info -->
                    <div style="margin-top: 40px; padding: 25px; background: #e8f5e9; border: 1px solid #81c784; border-radius: 8px;">
                        <h3 style="color:#2e7d32;">Online Payment Selected</h3>
                        <p><strong>Amount to Pay: ₹<?= number_format($final_total, 2); ?></strong></p>
                        <p style="margin-top: 20px;">
                            <button 
                                name="pay_now" 
                                type="submit" 
                                style="background: #2e7d32; color:#fff; padding: 12px 24px; border: none; border-radius: 6px; cursor:pointer;">
                                    Pay Now
                            </button>
                        </p>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>

    </div>
</div>

<script>
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', () => {
            const row = input.closest('.item-row');
            const unitPrice = parseFloat(row.dataset.unitPrice);
            const qty = parseInt(input.value) || 1;
            const rowTotal = row.querySelector('.item-total');
            rowTotal.textContent = (unitPrice * qty).toFixed(2);

            updateGrandTotal();
        });
    });

    function updateGrandTotal() {
        let totalSP = 0;
        let totalMRP = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const unitSP = parseFloat(row.dataset.unitPrice);
            const qtyInput = row.querySelector('.quantity-input');
            const qty = parseInt(qtyInput?.value || 1);

            const mrp = parseFloat(row.querySelector('td:nth-child(6)').textContent.replace('₹', '').replace(',', ''));

            totalSP += unitSP * qty;
            totalMRP += mrp * qty;
        });

        const totalDiscount = totalMRP - totalSP;
        const deliveryCharge = totalSP >= 500 || totalSP === 0 ? 0 : 49;
        const grandTotal = totalSP + deliveryCharge;

        // Update the DOM
        document.getElementById('total-mrp').textContent = totalMRP.toFixed(2);
        document.getElementById('total-discount').textContent = totalDiscount.toFixed(2);
        document.getElementById('delivery-charge-display').textContent = deliveryCharge === 0 ? 'FREE' : `₹${deliveryCharge.toFixed(2)}`;
        document.getElementById('grand-total').textContent = grandTotal.toFixed(2);

        // Also update the hidden grand total input
        const hiddenInput = document.querySelector('input[name="grand_total"]');
        if (hiddenInput) hiddenInput.value = grand.toFixed(2);
    }

    document.getElementById('checkout-form').addEventListener('submit', function (e) {
        const addressField = document.querySelector('textarea[name="address"]');
        if (!addressField.value.trim()) {
            alert("Please fill in the Street Address field.");
            addressField.focus();
            e.preventDefault();
        }
    });

</script>


<?php include 'includes/footer.php'; ?>
</body>
</html>
