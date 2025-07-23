<?php include 'includes/header.php'; ?>
<?php include 'config/db.php'; ?>

<?php
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$products = [];

if ($search !== '' || $category !== '') {
    $query = "SELECT * FROM products WHERE 1=1";
    $params = [];
    $types = "";

    if ($search !== '') {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $params[] = &$searchTerm;
        $params[] = &$searchTerm;
        $types .= "ss";
    }

    if ($category !== '') {
        $query .= " AND category = ?";
        $params[] = &$category;
        $types .= "s";
    }

    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $products = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f7fa;
        }

        h2 {
            text-align: center;
            color: #2b6777;
            margin-top: 30px;
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

        .no-result {
            text-align: center;
            color: #888;
            font-size: 18px;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<h2>🔍 Search Results<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?><?= $category ? ' in "' . htmlspecialchars(ucfirst($category)) . '"' : '' ?></h2>

<!-- 🛍 Product Grid -->
<div class="container">
    <?php if ($products && $products->num_rows > 0): ?>
        <?php while ($row = $products->fetch_assoc()):
            $selling_price = $row['price'];
            $market_price = $row['market_price'];
            $discount = ($market_price > $selling_price && $market_price > 0)
                ? round((($market_price - $selling_price) / $market_price) * 100)
                : 0;
        ?>
            <a href="product.php?id=<?= $row['id']; ?>" class="product-card">
                <img src="assets/images/<?= $row['image']; ?>" alt="<?= htmlspecialchars($row['name']); ?>">
                <h3><?= htmlspecialchars($row['name']); ?></h3>
                <?php if ($discount > 0): ?>
                    <p>
                        <span style="text-decoration: line-through; color: #999;">₹<?= number_format($market_price, 2); ?></span>
                        <span style="color: #2b6777;"> ₹<?= number_format($selling_price, 2); ?></span>
                        <span class="discount"> (<?= $discount; ?>% off)</span>
                    </p>
                <?php else: ?>
                    <p>₹<?= number_format($selling_price, 2); ?></p>
                <?php endif; ?>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-result">No products found matching your criteria.</p>
    <?php endif; ?>
</div>

<!-- 👀 Interested Products Section (same as index.php) -->
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
            <h2 style="margin: 60px 30px 20px; color: #2b6777;">👀 Interested Products</h2>
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

<?php include 'includes/footer.php'; ?>
</body>
</html>
