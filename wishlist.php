<?php include 'includes/header.php'; ?>
<?php
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// ❌ Remove item from wishlist
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $remove_id);
    $stmt->execute();
    $msg = "❌ Removed from wishlist.";
}

// 📦 Fetch wishlist items
$stmt = $conn->prepare("
    SELECT products.* 
    FROM wishlist 
    JOIN products ON wishlist.product_id = products.id 
    WHERE wishlist.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Wishlist - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .wishlist-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            color: #2b6777;
            text-align: center;
            margin-bottom: 30px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            border-radius: 6px;
        }

        .card h3 {
            margin: 12px 0 8px;
            font-size: 18px;
            color: #333;
        }

        .card p {
            color: #2b6777;
            font-weight: bold;
            font-size: 16px;
        }

        .card a {
            text-decoration: none;
            font-size: 14px;
            color: red;
            display: inline-block;
            margin-top: 10px;
        }

        .msg {
            text-align: center;
            color: green;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #888;
            font-size: 16px;
            padding: 40px 0;
        }
    </style>
</head>
<body>

<div class="wishlist-container">
    <h2>❤️ Your Wishlist</h2>
    <?php if ($msg): ?><p class="msg"><?= $msg ?></p><?php endif; ?>

    <?php if ($wishlist->num_rows > 0): ?>
        <div class="grid">
            <?php while ($item = $wishlist->fetch_assoc()): ?>
                <div class="card">
                    <img src="assets/images/<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                    <h3><?= htmlspecialchars($item['name']); ?></h3>
                    <p>₹<?= htmlspecialchars($item['price']); ?></p>
                    <a href="wishlist.php?remove=<?= $item['id']; ?>" onclick="return confirm('Remove from wishlist?')">🗑️ Remove</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="empty">📝 No items in wishlist yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>
