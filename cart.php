<?php include 'includes/header.php'; ?>
<?php
include 'config/db.php';
// session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// ✅ Load and clear error messages from session
$errors = $_SESSION['cart_error'] ?? [];
unset($_SESSION['cart_error']);


// ✅ Handle quantity update
if (isset($_POST['update_qty'])) {
    $cart_id = intval($_POST['cart_id']);
    $qty = max(1, intval($_POST['quantity']));

    // Get product stock for this cart item
    $stock_check = $conn->query("
        SELECT products.stock 
        FROM cart 
        JOIN products ON cart.product_id = products.id 
        WHERE cart.id = $cart_id AND cart.user_id = $user_id
    ");

    $available_stock = $stock_check->fetch_assoc()['stock'] ?? 0;

    if ($qty > $available_stock) {
        $_SESSION['cart_error'][$cart_id] = "❌ Only $available_stock units available in stock.";
    } else {
        $conn->query("UPDATE cart SET quantity = $qty WHERE id = $cart_id AND user_id = $user_id");
        $_SESSION['cart_error'][$cart_id] = "✅ Quantity updated.";
        $msg = "✅ Quantity updated.";
    }

    // Redirect to avoid resubmission and show messages properly
    header("Location: cart.php");
    exit();
}


// ❌ Handle item removal
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $conn->query("DELETE FROM cart WHERE id = $remove_id AND user_id = $user_id");
    $msg = "❌ Item removed from cart.";
}

// 🛒 Fetch cart items
$cart_items = $conn->query("
    SELECT cart.id AS cart_id, products.*, cart.quantity, cart.chosen_color, products.stock
    FROM cart 
    JOIN products ON cart.product_id = products.id 
    WHERE cart.user_id = $user_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h2 {
            text-align: center;
            color: #2b6777;
            margin-bottom: 20px;
        }

        .msg {
            text-align: center;
            font-weight: bold;
            color: green;
            margin-bottom: 15px;
        }

        .cart-item {
            display: flex;
            align-items: flex-start;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            gap: 20px;
        }

        .cart-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .cart-details {
            flex: 1;
        }

        .cart-details h3 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .cart-details a.product-link {
            font-size: 14px;
            color: #2b6777;
            text-decoration: underline;
        }

        .cart-details p {
            margin: 5px 0;
            color: #2b6777;
        }

        .qty-form {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .qty-form input[type="number"] {
            width: 60px;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .qty-form button {
            padding: 6px 12px;
            background: #2b6777;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .qty-form button:hover {
            background: #1f4f5a;
        }

        .remove-btn {
            color: red;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            align-self: center;
        }

        .total {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            margin-top: 30px;
            color: #333;
        }

        .checkout-btn {
            display: block;
            text-align: right;
            margin-top: 15px;
        }

        .checkout-btn a {
            background-color: #2b6777;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.2s;
        }

        .checkout-btn a:hover {
            background-color: #1f4f5a;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .qty-form {
                justify-content: center;
            }

            .total, .checkout-btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🛒 Your Shopping Cart</h2>

    <?php if ($msg): ?>
        <p class="msg"><?= $msg ?></p>
    <?php endif; ?>

    <?php
    $total = 0;
    if ($cart_items->num_rows > 0):
        while ($item = $cart_items->fetch_assoc()):
            $sub_total = $item['price'] * $item['quantity'];
            $total += $sub_total;
    ?>
        <div class="cart-item">
            <a href="product.php?id=<?= $item['id']; ?>">
                <img src="assets/images/<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
            </a>
            <div class="cart-details">
                <h3><?= htmlspecialchars($item['name']); ?></h3>
                <p>Price: ₹<?= number_format($item['price'], 2); ?></p>
                <?php if (!empty($item['chosen_color'])): ?>
                    <p>
                        Color: 
                        <span style="display:inline-block; width:14px; height:14px; background:<?= htmlspecialchars($item['chosen_color']); ?>; border-radius:50%; vertical-align:middle; margin-right:6px; border:1px solid #ccc;"></span>
                        <?= htmlspecialchars($item['chosen_color']); ?>
                    </p>
                <?php endif; ?>
                <p>Subtotal: ₹<?= number_format($sub_total, 2); ?></p>

                <form method="POST" class="qty-form">
                    <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                    <input type="number" name="quantity" value="<?= $item['quantity']; ?>" min="1">
                    <button type="submit" name="update_qty">Update</button>
                    <a class="remove-btn" href="cart.php?remove=<?= $item['cart_id']; ?>" onclick="return confirm('Remove item?')">Remove</a>
                </form>

                <?php if (isset($errors[$item['cart_id']])): ?>
                    <p style="color: red; margin-top: 8px;"><?= $errors[$item['cart_id']]; ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
        <div class="total">Grand Total: ₹<?= number_format($total, 2); ?></div>
        <div class="checkout-btn">
            <a href="checkout.php">Proceed to Checkout</a>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #555;">🛍️ Your cart is currently empty.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
