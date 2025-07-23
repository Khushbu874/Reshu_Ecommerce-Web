<?php include 'includes/header.php'; ?>
<?php include 'config/db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Products - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f9f9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .title {
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
            color: #2b6777;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 6px;
        }

        .product-card h3 {
            margin: 12px 0 6px;
            font-size: 18px;
            color: #333;
        }

        .product-card p {
            color: #2b6777;
            font-weight: bold;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .product-card .discount {
            color: #d33;
            font-size: 14px;
        }

        .product-card a.view-button {
            text-decoration: none;
            padding: 8px 14px;
            background: #2b6777;
            color: white;
            border-radius: 6px;
            display: inline-block;
            font-weight: bold;
        }

        .product-card a.view-button:hover {
            background-color: #1f4e5e;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="title">📦 All Products</h2>

    <div class="product-grid">
        <?php
        $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
        while ($row = $result->fetch_assoc()) {
            $selling_price = $row['price'];
            $market_price = $row['market_price'];

            // Calculate discount percentage if market price is valid
            $discount = ($market_price > $selling_price && $market_price > 0)
                ? round((($market_price - $selling_price) / $market_price) * 100)
                : 0;

            echo "<a href='product.php?id={$row['id']}' class='product-card'>";
            echo "<img src='assets/images/{$row['image']}' alt='Product'>";
            echo "<h3>" . htmlspecialchars($row['name']) . "</h3>";

            if ($discount > 0) {
                echo "<p>
                        <span style='text-decoration: line-through; color: #999;'>₹" . number_format($market_price, 2) . "</span>
                        <span style='color: #2b6777;'> ₹" . number_format($selling_price, 2) . "</span>
                        <span class='discount'> ({$discount}% off)</span>
                    </p>";
            } else {
                echo "<p>₹" . number_format($selling_price, 2) . "</p>";
            }

            echo "</a>"; // end .product-card
        }
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
