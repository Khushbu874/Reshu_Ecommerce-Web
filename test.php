<?php
// include 'includes/header.php';
include 'config/db.php';
session_start();

$msg = "";
$is_success = false;

// Check if admin/seller is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

$user_id = $_SESSION['user_id'];

// Handle new product submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name        = trim($_POST['name']);
    $price       = floatval($_POST['price']);
    $category    = trim($_POST['category']);
    $description = trim($_POST['description']);

    if (!empty($_FILES['image']['name'])) {
        $original_name = basename($_FILES['image']['name']);
        $image_name = uniqid() . "_" . $original_name;
        $target_path = "assets/images/" . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $stmt = $conn->prepare("INSERT INTO products (name, price, image, description, category, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsssi", $name, $price, $image_name, $description, $category, $user_id);
            if ($stmt->execute()) {
                $msg = "✅ Product added successfully!";
                $is_success = true;
            } else {
                $msg = "❌ DB Error: " . $conn->error;
            }
        } else {
            $msg = "❌ Failed to upload image.";
        }
    } else {
        $msg = "❌ Please select a product image.";
    }
}

// Fetch all products uploaded by this admin/seller
$result = $conn->prepare("SELECT * FROM products WHERE user_id = ?");
$result->bind_param("i", $user_id);
$result->execute();
$products = $result->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Products</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background: #f5f5f5;
        }

        h2 {
            color: #2b6777;
            text-align: center;
        }

        .msg {
            text-align: center;
            font-weight: bold;
            color: <?= $is_success ? '#28a745' : '#dc3545' ?>;
        }

        .form-box {
            background: #fff;
            padding: 20px;
            margin-bottom: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: auto;
        }

        .form-box input,
        .form-box select,
        .form-box textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .form-box button {
            background: #2b6777;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
        }

        .form-box button:hover {
            background: #1f4f5a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #2b6777;
            color: white;
        }

        img.thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

        .action-links a {
            margin: 0 5px;
            color: #2b6777;
            text-decoration: none;
            font-weight: bold;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

<h2>📦 Manage Your Products</h2>

<?php if ($msg): ?>
    <p class="msg"><?= $msg ?></p>
<?php endif; ?>

<div class="form-box">
    <h3>Add New Product</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="number" name="price" step="0.01" placeholder="Price (₹)" required>
        <select name="category" required>
            <option value="" disabled selected>Select Category</option>
            <option value="clothing">Clothing</option>
            <option value="electronics">Electronics</option>
            <option value="gadgets">Gadgets</option>
            <option value="books">Books</option>
            <option value="other">Other</option>
        </select>
        <input type="file" name="image" accept="image/*" required>
        <textarea name="description" rows="4" placeholder="Description (optional)"></textarea>
        <button type="submit" name="add_product">Add Product</button>
    </form>
</div>

<h3 style="text-align:center;">🛍️ Your Uploaded Products</h3>

<?php if ($products->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Thumbnail</th>
                <th>Name</th>
                <th>Price (₹)</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $products->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><img src="assets/images/<?= $row['image']; ?>" class="thumb" alt=""></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= number_format($row['price'], 2); ?></td>
                    <td><?= ucfirst($row['category']); ?></td>
                    <td class="action-links">
                        <a href="edit_product.php?id=<?= $row['id']; ?>">✏️ Edit</a>
                        <a href="delete_product.php?id=<?= $row['id']; ?>" onclick="return confirm('Are you sure to delete?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center; color: #555;">No products uploaded yet.</p>
<?php endif; ?>

</body>
</html>
