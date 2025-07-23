<?php
include '../config/db.php';
session_start();

$msg = "";
$is_success = false;

if (!isset($_GET['id'])) {
    header("Location: admin_products.php");
    exit();
}

$product_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("<p style='text-align:center; color:red;'>❌ You are not authorized to edit this product.</p>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name         = trim($_POST['name']);
    $price        = floatval($_POST['price']);
    $market_price = !empty($_POST['market_price']) ? floatval($_POST['market_price']) : NULL;
    $stock        = intval($_POST['stock']);
    $category     = trim($_POST['category']);
    $description  = trim($_POST['description']);

    // Process colors
    $colors_arr = isset($_POST['colors']) ? array_map(function($c) {
        $clean = strtoupper(trim($c));
        return (strpos($clean, '#') === 0) ? $clean : '#' . $clean;
    }, $_POST['colors']) : [];
    $colors = implode(',', $colors_arr);

    $image = $product['image'];

    // Handle main image
    if (!empty($_FILES['image']['name'])) {
        $newImage = uniqid() . "_" . basename($_FILES['image']['name']);
        $temp     = $_FILES['image']['tmp_name'];
        $target   = "../assets/images/" . $newImage;

        if (move_uploaded_file($temp, $target)) {
            if (file_exists("../assets/images/" . $product['image'])) {
                unlink("../assets/images/" . $product['image']);
            }
            $image = $newImage;
        } else {
            $msg = "❌ Failed to upload new main image.";
        }
    }

    // Process additional images
    $existing_images = isset($_POST['existing_images']) ? $_POST['existing_images'] : [];
    $additional_images = $existing_images;

    if (!empty($_FILES['additional_images']['name'][0])) {
        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
            $filename = uniqid() . "_" . basename($_FILES['additional_images']['name'][$key]);
            $target = "../assets/images/" . $filename;
            if (move_uploaded_file($tmp_name, $target)) {
                $additional_images[] = $filename;
            }
        }
    }

    $additional_images_str = implode(',', $additional_images);

    // Update DB
    $update_stmt = $conn->prepare("UPDATE products SET name=?, price=?, market_price=?, image=?, additional_images=?, description=?, category=?, stock=?, colors=? WHERE id=?");
    $update_stmt->bind_param("sddssssdsi", $name, $price, $market_price, $image, $additional_images_str, $description, $category, $stock, $colors, $product_id);

    if ($update_stmt->execute()) {
        $msg = "✅ Product updated successfully!";
        $is_success = true;

        // RE-FETCH updated product data
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
    } else {
        $msg = "❌ DB Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f1f1f1;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            padding: 50px;
        }

        .form-box {
            background: white;
            padding: 30px;
            width: 480px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .form-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2b6777;
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

        .form-box button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #2b6777;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .form-box button[type="submit"]:hover {
            background-color: #1f4f5a;
        }

        .msg {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            color: <?= $is_success ? '#28a745' : '#dc3545' ?>;
        }

        .thumb-small {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            margin: 5px 5px 10px 0;
        }

        .color-block {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
            height: 22px;
            width: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        #addColor, #addImage {
            background: #6c757d;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        #addColor:hover, #addImage:hover {
            background: #5a6268;
        }
    </style>

<div class="form-box">
    <h2>Edit Product</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']); ?>" required>
        <input type="number" step="0.01" name="market_price" value="<?= htmlspecialchars($product['market_price']); ?>" placeholder="Market Price (optional)">
        <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']); ?>" required>

        <label><strong>Available Colors:</strong></label>
        <div id="color-wrapper">
            <?php
            $color_values = explode(',', $product['colors']);
            foreach ($color_values as $color):
                $hex = htmlspecialchars($color);
                if (strpos($hex, '#') !== 0) {
                    $hex = '#' . $hex;
                }
            ?>
                <div class="color-block">
                    <input type="color" name="colors[]" value="<?= $hex ?>">
                    <button type="button" class="remove-btn" title="Remove" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="addColor">➕ Add Color</button>

        <select name="category" required>
            <option value="" disabled>Select Category</option>
            <option value="electronics" <?= $product['category'] == 'electronics' ? 'selected' : '' ?>>Electronics</option>
            <option value="home" <?= $product['category'] == 'home' ? 'selected' : '' ?>>Home</option>
            <option value="appliance" <?= $product['category'] == 'appliance' ? 'selected' : '' ?>>Appliance</option>
            <option value="furniture" <?= $product['category'] == 'furniture' ? 'selected' : '' ?>>Furniture</option>
            <option value="other" <?= $product['category'] == 'other' ? 'selected' : '' ?>>Other</option>
        </select>

        <label>Current Image:</label><br>
        <img src="../assets/images/<?= htmlspecialchars($product['image']); ?>" alt="Main" class="thumb-small"><br>
        <input type="file" name="image" accept="image/*">

        <?php if (!empty($product['additional_images'])): ?>
            <label>Current Additional Images:</label><br>
            <div id="existing-image-wrapper">
                <?php
                $extra_images = explode(',', $product['additional_images']);
                foreach ($extra_images as $img):
                ?>
                    <div class="color-block">
                        <img src="../assets/images/<?= $img ?>" alt="Extra" class="thumb-small">
                        <input type="hidden" name="existing_images[]" value="<?= htmlspecialchars($img) ?>">
                        <button type="button" class="remove-btn" title="Remove" onclick="this.parentElement.remove()">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label>Add More Images (optional):</label>
        <div id="image-wrapper">
            <input type="file" name="additional_images[]" accept="image/*">
        </div>
        <button type="button" id="addImage">➕ Add Image</button>

        <textarea name="description" rows="4"><?= htmlspecialchars($product['description']); ?></textarea>
        <button type="submit">Update Product</button>
    </form>
    <div class="msg"><?= $msg ?></div>
</div>

<script>
document.getElementById('addColor').onclick = () => {
    const wrap = document.getElementById('color-wrapper');
    const div = document.createElement('div');
    div.className = 'color-block';
    div.innerHTML = `<input type="color" name="colors[]" value="#000000">
                    <button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>`;
    wrap.appendChild(div);
};

document.getElementById('addImage').onclick = () => {
    const wrap = document.getElementById('image-wrapper');
    const inp = document.createElement('input');
    inp.type = 'file';
    inp.name = 'additional_images[]';
    inp.accept = 'image/*';
    inp.style.marginTop = '10px';
    wrap.appendChild(inp);
};
</script>

</body>
</html>
