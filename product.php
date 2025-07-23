<?php include 'includes/header.php'; ?>
<?php
include 'config/db.php';

$msg = "";
$msg_color = "#28a745";

/* ── redirect if id missing ───────────────────── */
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$product_id = intval($_GET['id']);

/* ── fetch product ────────────────────────────── */
$product_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();
if (!$product) {
    die("❌ Product not found.");
}


/* ── viewed‑products tracker ───────────────────── */
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['viewed_products'])) {
        $_SESSION['viewed_products'] = [];
    }
    // Add product to start of array if not already there
    if (!in_array($product_id, $_SESSION['viewed_products'])) {
        array_unshift($_SESSION['viewed_products'], $product_id);
    }
    // Limit list to last 6
    $_SESSION['viewed_products'] = array_slice($_SESSION['viewed_products'], 0, 6);
}

/* ── color helper ─────────────────────────────────────────────── */
$color_required = !empty($product['colors']) && substr_count($product['colors'], ',') !== false;

/* ── handle POST (add‑to‑cart / buy‑now) ───────── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id']; 
    $selected_color = $_POST['selected_color'] ?? '';

    if ($color_required && $selected_color === '') {
        $msg = "⚠️ Please select a color before proceeding.";
        $msg_color = "#d33";
    } else {

        // ---------- Add to Cart ----------
        if (isset($_POST['add_to_cart'])) {

            $chk = $conn->prepare(
                "SELECT 1 FROM cart WHERE user_id=? AND product_id=? AND chosen_color=?"
            );
            $chk->bind_param("iis", $user_id, $product_id, $selected_color);
            $chk->execute();
            $exists = $chk->get_result()->num_rows;

            if ($exists) {
                $msg = "⚠️ Product already in cart.";  $msg_color = "#d33";
            } else {
                $add = $conn->prepare(
                    "INSERT INTO cart (user_id, product_id, quantity, chosen_color)
                    VALUES (?,?,1,?)"
                );
                $add->bind_param("iis", $user_id, $product_id, $selected_color);
                if ($add->execute()) {
                    $msg = "✅ Added to cart!";
                    $msg_color = "#28a745";
                } else {
                    $msg = "❌ DB error: " . $conn->error;
                    $msg_color = "#d33";
                }
            }

        // ---------- Buy Now ----------
        } elseif (isset($_POST['buy_now'])) {
            $_SESSION['buy_now_product_id'] = $product_id;
            $_SESSION['buy_now_color']      = $selected_color;
            header("Location: checkout.php?buy_now=1");
            exit();
        }
    }
}

/* ── images array ──────────────────────────────────────────────── */
$images = [$product['image']];                                       // main image
if (!empty($product['additional_images'])) {
    $extras = array_filter(array_map('trim', explode(',', $product['additional_images'])));
    $images = array_merge($images, $extras);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']); ?> - Product Details</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f3f3;
        }

        header, footer {
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .product-box {
            background: #fff;
            display: flex;
            gap: 30px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .product-box img#mainImage {
            width: 240px;
            height: 240px;
            object-fit: contain;
            border-radius: 8px;
            background: #fafafa;
            transition: 0.3s ease;
        }

        .product-info {
            flex: 1;
            max-width: 600px;
        }

        .product-info h2 {
            font-size: 22px;
            margin: 10px 0;
            color: #2b6777;
        }

        .product-info p {
            font-size: 14px;
            margin: 8px 0;
            line-height: 1.5;
        }

        .product-info strong {
            font-size: 18px;
            color: #333;
        }

        .category-tag {
            display: inline-block;
            background: #2b6777;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .actions form {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .actions button {
            background: #2b6777;
            color: white;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .actions button:hover {
            background: #1f4f5a;
        }

        .msg {
            margin-top: 15px;
            color: #28a745;
        }

        .edit-link {
            margin-top: 15px;
        }

        .edit-link a {
            color: orange;
            font-weight: bold;
            text-decoration: none;
            margin-right: 14px;
        }

        .edit-link a:hover {
            text-decoration: underline;
        }

        .color-circle input[type="radio"] {
            display: none;
        }

        .color-circle span {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #ccc;
            box-shadow: 0 0 2px rgba(0,0,0,0.3);
            transition: 0.2s;
        }

        .color-circle input[type="radio"]:checked + span {
            outline: 3px solid #2b6777;
            outline-offset: 2px;
            transform: scale(1.1);
        }

        .thumb-strip{
            margin-top:10px;
            display:flex;
            gap:8px;
            flex-wrap:wrap
        }

        .thumb-strip img{
            width:60px;
            height:60px;
            object-fit:cover;
            border-radius:4px;
            cursor:pointer;
            opacity:.8;
            transition:.2s
        }

        .thumb-strip img:hover,
        .thumb-strip img.active{
            opacity:1;
            outline:3px solid #2b6777;
            outline-offset:2px
        }


    </style>
</head>
<body>

<div class="container">
    <div class="product-box">

        <!-- ─── image / thumbnails ─────────────── -->
        <div>
            <img id="mainImage" src="assets/images/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>">

            <?php
            // Optional: Show more images
            if (count($images) > 1): ?>
            <div class="thumb-strip">
                <?php foreach ($images as $idx=>$img): ?>
                    <img src="assets/images/<?= htmlspecialchars($img) ?>"
                        data-big="assets/images/<?= htmlspecialchars($img) ?>"
                        class="<?= $idx===0?'active':'' ?>" alt="thumb">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>


        <!-- ─── product info + single form ─────── -->
        <div class="product-info">
            <span class="category-tag"><?= ucfirst(htmlspecialchars($product['category'])); ?></span>
            <h2><?= htmlspecialchars($product['name']); ?></h2>

            <?php
                $price = $product['price'];
                $market_price = $product['market_price'];
                $discount = 0;
                if ($market_price > $price) {
                    $discount = round((($market_price - $price) / $market_price) * 100);
                }
            ?>

            <?php if ($discount > 0): ?>
                <p>
                    <strong style="color: green;">₹<?= number_format($price, 2); ?></strong>
                    <span style="text-decoration: line-through; color: #888;">₹<?= number_format($market_price, 2); ?></span>
                    <span style="color: orange; font-weight: bold;">(<?= $discount; ?>% OFF)</span>
                </p>
            <?php else: ?>
                <p><strong>₹<?= number_format($price, 2); ?></strong></p>
            <?php endif; ?>

            <form method="POST" id="productForm" onsubmit="return validateColor();">
                <?php if ($color_required): ?>
                    <p><strong>Available Colors:</strong></p>
                    <div class="color-options" style="display: flex; gap: 12px; margin-bottom: 15px;">
                        <?php
                        $color_list = explode(',', $product['colors']);
                        foreach ($color_list as $index => $color):
                            $c = trim($color);
                            $color_id = "color_" . $index;
                        ?>
                            <label for="<?= $color_id; ?>" class="color-circle">
                                <input type="radio" name="selected_color" value="<?= htmlspecialchars($c); ?>" id="<?= $color_id; ?>">
                                <span style="background: <?= htmlspecialchars($c); ?>;"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
            

                <p>
                    <strong>Stock:</strong>
                    <?= intval($product['stock']); ?> unit left
                    <?php if ($product['stock'] <= 5): ?>
                        <span style="color: red; font-weight: bold;">(Low Stock!)</span>
                    <?php endif; ?>
                </p>

                <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="actions">
                        <?php if (intval($product['stock']) > 0): ?>
                                <button type="submit" name="add_to_cart">Add to Cart</button>
                                <button type="submit" name="buy_now">Buy Now</button>
                        <?php else: ?>
                            <p style="color: red; font-weight: bold; margin-top: 10px;">❌ Out of Stock</p>
                            <button disabled style="padding: 12px 20px; background: #ccc; border-radius: 6px; color: #fff; font-weight: bold;">Not Available</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p><a href="login.php" style="color:#2b6777; font-weight:bold;">Login</a> to buy this product.</p>
                <?php endif; ?>

                <!-- message placeholder (always present) -->
                <p class="msg" id="form-msg" style="color:<?= $msg_color; ?>;">
                    <?= htmlspecialchars($msg); ?>
                </p>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['user_id']): ?>
                    <div class="edit-link">
                        <a href="edit_product.php?id=<?= $product['id']; ?>">✏️ Edit</a>
                        <a href="delete_product.php?id=<?= $product['id']; ?>"
                        onclick="return confirm('Delete this product?')">🗑️ Delete</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- related Products  -->
<?php
$related_stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$related_stmt->bind_param("si", $product['category'], $product['id']);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
?>

<?php if ($related_result->num_rows > 0): ?>
    <h3 style="margin-top: 40px;  margin-left: 20px; color: #2b6777;">🔄 Related Products</h3>
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px; margin-left: 20px;">
        <?php while ($related = $related_result->fetch_assoc()): ?>
            <div style="background: white; border-radius: 10px; padding: 15px; width: 220px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <a href="product.php?id=<?= $related['id']; ?>" style="text-decoration: none; color: #333;">
                    <img src="assets/images/<?= $related['image']; ?>" alt="<?= $related['name']; ?>" style="width: 100%; height: 160px; object-fit: contain; border-radius: 8px;">
                    <h4 style="margin-top: 10px; font-size: 16px;"><?= htmlspecialchars($related['name']); ?></h4>
                    <p style="color: green; font-weight: bold;">₹<?= number_format($related['price'], 2); ?></p>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php
if (isset($_SESSION['user_id']) && isset($_SESSION['viewed_products'])) {
    // Remove current product from viewed list
    $interested_ids = array_diff($_SESSION['viewed_products'], [$product_id]);

    if (!empty($interested_ids)) {
        $placeholders = implode(',', array_fill(0, count($interested_ids), '?'));
        $types = str_repeat('i', count($interested_ids));
        $interested_stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $interested_stmt->bind_param($types, ...$interested_ids);
        $interested_stmt->execute();
        $interested_result = $interested_stmt->get_result();
    }
}
?>


<!-- interested Products -->

<?php if (!empty($interested_result) && $interested_result->num_rows > 0): ?>
    <h3 style="margin-top: 40px;  margin-left: 20px; color: #2b6777;">🔍 Interested Products</h3>
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px; margin-left: 20px;">
        <?php while ($item = $interested_result->fetch_assoc()): ?>
            <div style="background: white; border-radius: 10px; padding: 15px; width: 220px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <a href="product.php?id=<?= $item['id']; ?>" style="text-decoration: none; color: #333;">
                    <img src="assets/images/<?= $item['image']; ?>" alt="<?= $item['name']; ?>" style="width: 100%; height: 160px; object-fit: contain; border-radius: 8px;">
                    <h4 style="margin-top: 10px; font-size: 16px;"><?= htmlspecialchars($item['name']); ?></h4>
                    <p style="color: green; font-weight: bold;">₹<?= number_format($item['price'], 2); ?></p>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>


<?php if (count($images) > 1): ?>
    <script>
        // JS to swap gallery images
        document.querySelectorAll('.thumb-strip img').forEach(t=>{
            t.addEventListener('click',()=>{
                document.getElementById('mainImage').src = t.dataset.big;
                document.querySelectorAll('.thumb-strip img').forEach(x=>x.classList.remove('active'));
                t.classList.add('active');
            });
        });

        // Color selection restriction for add-to-cart and buy-now
        function validateColor(){
            const radios = document.querySelectorAll('input[name="selected_color"]');
            const msgBox = document.getElementById('form-msg');
            
            if (radios.length > 0) {
                const selected = document.querySelector('input[name="selected_color"]:checked');
                if (!selected) {
                    msgBox.textContent = "⚠️ Please select a color before proceeding.";
                    msgBox.style.color = "#d33";
                    return false; // Prevent form submission
                }
            }

            return true; // Allow form submission
        }
    </script>
<?php endif; ?>


</body>
</html>

<?php include 'includes/footer.php'; ?>
