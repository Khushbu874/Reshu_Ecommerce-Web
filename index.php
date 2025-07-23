<?php include 'includes/header.php'; ?>
<?php include 'config/db.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }

        .search-bar {
            text-align: center;
            margin: 20px auto;
        }

        .search-bar input[type="text"], .search-bar select {
            padding: 10px;
            width: 250px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }

        .search-bar button {
            padding: 10px 15px;
            background-color: #2b6777;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .container {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
            transition: 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .product-card:hover {
            transform: scale(1.03);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .product-card h3 {
            margin: 15px 0 10px;
            color: #333;
        }

        .product-card p {
            font-size: 16px;
            color: #2b6777;
            font-weight: bold;
        }

        .product-card .discount {
            color: #d33;
            font-size: 14px;
        }
    </style>
</head>
<body>

<!-- 🔍 Search and Category Filter -->
<div class="search-bar">
    <form method="GET" action="index.php">
        <input type="text" name="search" placeholder="Search products..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <select name="category">
            <option value="">All Categories</option>
            <option value="clothing" <?= (isset($_GET['category']) && $_GET['category'] == 'clothing') ? 'selected' : '' ?>>Clothing</option>
            <option value="electronics" <?= (isset($_GET['category']) && $_GET['category'] == 'electronics') ? 'selected' : '' ?>>Electronics</option>
            <option value="gadgets" <?= (isset($_GET['category']) && $_GET['category'] == 'gadgets') ? 'selected' : '' ?>>Gadgets</option>
        </select>
        <button type="submit">Search</button>
    </form>
</div>

<!-- 🛍 Product Grid -->
<div class="container">
    <?php
    // Handle search and category filter
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

    $query = "SELECT * FROM products WHERE 1=1";
    if ($search !== '') {
        $query .= " AND name LIKE '%$search%'";
    }
    if ($category !== '') {
        $query .= " AND category = '$category'";
    }

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $selling_price = $row['price'];
            $market_price = $row['market_price'];

            // Calculate discount percentage
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

            echo "</a>"; // end product-card
        }
    } else {
        echo "<p style='text-align:center; color:#555;'>No products found.</p>";
    }
    ?>
</div>

<?php
if (isset($_SESSION['user_id']) && isset($_SESSION['viewed_products'])) {
    $interested_ids = array_unique(array_slice($_SESSION['viewed_products'], 0, 6));
    
    if (!empty($interested_ids)) {
        $placeholders = implode(',', array_fill(0, count($interested_ids), '?'));
        $types = str_repeat('i', count($interested_ids));
        $interested_stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $interested_stmt->bind_param($types, ...$interested_ids);
        $interested_stmt->execute();
        $interested_result = $interested_stmt->get_result();
        
        if ($interested_result->num_rows > 0): ?>
            <h2 style="margin: 60px 30px 20px; color: #2b6777;">🔍 Interested Products</h2>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; padding: 0 30px 40px;">
                <?php while ($item = $interested_result->fetch_assoc()): ?>
                    <a href="product.php?id=<?= $item['id']; ?>" class="product-card" style="width: 220px; text-decoration: none; color: inherit;">
                        <img src="assets/images/<?= $item['image']; ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                        <h3><?= htmlspecialchars($item['name']); ?></h3>
                        <p>₹<?= number_format($item['price'], 2); ?></p>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif;
    }
}
?>


</body>
</html>

<?php include 'includes/footer.php'; ?>
