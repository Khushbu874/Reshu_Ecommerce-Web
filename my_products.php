<?php include 'includes/header.php'; ?>
<?php
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Fetch user's products
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Products</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #f2f2f2;
            font-family: 'Segoe UI', sans-serif;
            padding: 40px;
        }

        .product-list {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }

        .product-item img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
            background: #fafafa;
        }

        .product-details {
            flex: 1;
        }

        .product-details h3 {
            margin: 0;
            color: #2b6777;
        }

        .product-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        .product-actions a {
            text-decoration: none;
            margin-right: 10px;
            color: #2b6777;
            font-weight: bold;
        }

        .product-actions a:hover {
            text-decoration: underline;
        }

        h2 {
            text-align: center;
            color: #2b6777;
        }

        .msg {
            text-align: center;
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<h2>📦 My Listed Products</h2>

<div class="product-list">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="product-item">
                <img src="assets/images/<?= htmlspecialchars($row['image']); ?>" alt="<?= htmlspecialchars($row['name']); ?>">
                <div class="product-details">
                    <h3><?= htmlspecialchars($row['name']); ?></h3>
                    <p>₹<?= htmlspecialchars($row['price']); ?></p>
                    <p><?= ucfirst(htmlspecialchars($row['category'])); ?></p>
                    <div class="product-actions">
                        <a href="edit_product.php?id=<?= $row['id']; ?>">✏️ Edit</a>
                        <a href="delete_product.php?id=<?= $row['id']; ?>" onclick="return confirm('Delete this product?')">🗑️ Delete</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">You haven't listed any products yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>
