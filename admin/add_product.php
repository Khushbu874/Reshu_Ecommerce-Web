<?php
include '../config/db.php';
session_start();

$msg = "";
$is_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name         = trim($_POST['name']);
    $price        = floatval($_POST['price']);
    $market_price = !empty($_POST['market_price']) ? floatval($_POST['market_price']) : NULL;
    $stock        = intval($_POST['stock']);
    $category     = trim($_POST['category']);
    $description  = trim($_POST['description']);

    $colors_arr = isset($_POST['colors']) ? array_map('strtoupper', $_POST['colors']) : [];
    $colors     = implode(',', $colors_arr);

    $image_name = "";
    $additional_image_names = [];

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
            if (!empty($_FILES['images']['name'][$index])) {
                $unique_name = uniqid() . "_" . basename($_FILES['images']['name'][$index]);
                $target_path = "../assets/images/" . $unique_name;

                if (move_uploaded_file($tmp_name, $target_path)) {
                    if ($image_name == "") {
                        $image_name = $unique_name; // First image is main image
                    } else {
                        $additional_image_names[] = $unique_name;
                    }
                }
            }
        }
    } else {
        $msg = "❌ Please upload at least one product image.";
    }

    if ($image_name && $msg === "") {
        $additional_images_str = implode(',', $additional_image_names);

        $stmt = $conn->prepare(
            "INSERT INTO products
             (name, price, market_price, stock, colors, category, image, additional_images, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sddisssss",
            $name,
            $price,
            $market_price,
            $stock,
            $colors,
            $category,
            $image_name,
            $additional_images_str,
            $description
        );

        if ($stmt->execute()) {
            $msg = "✅ Product added successfully!";
            $is_success = true;
        } else {
            $msg = "❌ DB Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product – Admin</title>
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
            width: 450px;
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
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .form-box button {
            width: 100%;
            padding: 12px;
            background-color: #2b6777;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .form-box button:hover {
            background-color: #1f4f5a;
        }

        .msg {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            color: <?= $is_success ? '#28a745' : '#dc3545' ?>;
        }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Add New Product</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="number" name="price" step="0.01" placeholder="Selling Price (₹)" required>
        <input type="number" name="market_price" step="0.01" placeholder="Market Price (optional)">
        <input type="number" name="stock" min="1" placeholder="Total Quantity Available" required>

        <!-- ✅ Colour Picker Section -->
        <label><strong>Available Colours:</strong></label>
        <div id="color-wrapper" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px">
            <input type="color" name="colors[]" value="#000000">
        </div>
        <button type="button" id="addColor" style="margin-bottom:15px;background:#2b6777;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer">
            ➕ Add Colour
        </button>

        <select name="category" required>
            <option value="" disabled selected>Select Category</option>
            <option value="electronics">Electronics</option>
            <option value="home">Home</option>
            <option value="appliance">Appliance</option>
            <option value="furniture">Furniture</option>
            <option value="other">Other</option>
        </select>

        <!-- ✅ Dynamic Image Upload Section -->
        <label><strong>Product Images (first image will be main)</strong></label>
        <div id="image-wrapper" style="margin-bottom:15px">
            <input type="file" name="images[]" accept="image/*" required>
        </div>
        <button type="button" id="addImage" style="margin-bottom:15px;background:#2b6777;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer">
            ➕ Add Image
        </button>

        <textarea name="description" rows="4" placeholder="Product Description (optional)"></textarea>
        <button type="submit">Add Product</button>
    </form>
    <div class="msg"><?= $msg ?></div>
</div>

<!-- ✅ JS Scripts -->
<script>
document.getElementById('addColor').onclick = () => {
    const wrap = document.getElementById('color-wrapper');
    const inp = document.createElement('input');
    inp.type = 'color';
    inp.name = 'colors[]';
    inp.value = '#000000';
    inp.style.marginRight = '8px';
    wrap.appendChild(inp);
};

document.getElementById('addImage').onclick = () => {
    const wrap = document.getElementById('image-wrapper');
    const inp = document.createElement('input');
    inp.type = 'file';
    inp.name = 'images[]';
    inp.accept = 'image/*';
    inp.style.marginBottom = '10px';
    wrap.appendChild(inp);
};
</script>

</body>
</html>
