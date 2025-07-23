<?php
    include '../config/db.php';
    session_start();

    $msg = "";
    $is_success = false;

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
        $name         = trim($_POST['name']);
        $price        = floatval($_POST['price']);
        $market_price = $_POST['market_price'] !== '' ? floatval($_POST['market_price']) : $price;
        $stock        = intval($_POST['stock']);
        $category     = trim($_POST['category']);
        $description  = trim($_POST['description']);

        $colors_arr = isset($_POST['colors']) ? array_map('strtoupper', $_POST['colors']) : [];
        $colors     = implode(',', $colors_arr);

        $image_name = "";
        $additional_image_names = [];

        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
                if (!empty($_FILES['images']['name'][$idx])) {
                    $unique = uniqid() . '_' . basename($_FILES['images']['name'][$idx]);
                    $target = "../assets/images/" . $unique;
                    if (move_uploaded_file($tmp, $target)) {
                        if ($image_name === "") {
                            $image_name = $unique;
                        } else {
                            $additional_image_names[] = $unique;
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
                $msg        = "✅ Product added successfully!";
                $is_success = true;
            } else {
                $msg = "❌ DB Error: " . $conn->error;
            }
        }
    }

    $result = $conn->prepare("SELECT * FROM products ORDER BY id DESC");
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
        font-family:Arial,sans-serif;
        padding:30px;
        background:#f5f5f5
    }
    h2 {
        text-align:center;
        color:#2b6777
    }
    .msg {
        text-align:center;
        font-weight:bold;
        color:<?= $is_success ? '#28a745' : '#dc3545' ?>
    }
    .form-box{
        background:#fff;
        padding:20px;
        margin-bottom:40px;
        border-radius:8px;
        box-shadow:0 0 10px rgba(0,0,0,.05);
        max-width:600px;margin:auto
    }
    .form-box input,.form-box select,.form-box textarea{
        width:93%;
        padding:10px;
        margin-bottom:15px;
        border:1px solid #ccc;
        border-radius:6px
    }
    .form-box select{
        width:97%;
        padding:10px;
        margin-bottom:15px;
        border:1px solid #ccc;
        border-radius:6px
    }
    .form-box button{
        background:#2b6777;
        color:#fff;
        border:none;
        padding:10px 15px;
        border-radius:6px;
        margin-bottom: 15px;
        cursor:pointer
    }
    .form-box button:hover{
        background:#1f4f5a
    }
    table{
        width:100%;
        border-collapse:collapse;
        background:#fff;
        box-shadow:0 0 10px rgba(0,0,0,.1)
    }
    th,td{
        padding:12px;
        border:1px solid #ccc;
        text-align:center
    }
    th{
        background:#2b6777;
        color:#fff
    }
    img.thumb{
        width:60px;
        height:60px;
        object-fit:cover;
        border-radius:6px
    }
    .color-box{
        display:inline-block;
        width:20px;
        height:20px;
        border-radius:4px;
        margin:0 3px;
        border:1px solid #ccc
    }
    .action-links a{
        margin:0 5px;
        color:#2b6777;
        text-decoration:none;
        font-weight:bold
    }
    .action-links a:hover{
        text-decoration:underline
    }

    /* 🔍 Modal Styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 999;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.7);
        overflow: auto;
    }
    .modal-content {
        background-color: #fff;
        margin: 80px auto;
        padding: 20px;
        border-radius: 10px;
        width: 80%;
        max-width: 700px;
    }
    .modal-content h4 {
        margin-top: 0; 
        color: #2b6777;
    }
    .modal-images {
        display:flex;
        flex-wrap:wrap;
        gap:15px;
        justify-content:center;
        margin-top:15px
    }
    .modal-images img {
        width:120px;
        height:120px;
        object-fit:cover;
        border-radius:8px
    }
    .modal-close {
        float:right;
        font-size:22px;
        font-weight:bold;
        color:#888;
        cursor:pointer
    }

    .btn-view-orders {
        background-color: #2b6777;
        color: white;
        padding: 10px 18px;
        text-decoration: none;
        font-weight: bold;
        border-radius: 6px;
        display: inline-block;
        transition: background 0.3s ease;
    }

    .btn-view-orders:hover {
        background-color: #1c4a5a;
    }

</style>

<!-- View all Orders -->
<div style="text-align:center; margin: 20px 0;">
    <a href="admin_orders.php" class="btn-view-orders">📄 View All Orders</a>
</div>

<h2>📦 Manage Your Products</h2>
<?php if ($msg): ?><p class="msg"><?= $msg ?></p><?php endif; ?>

<!-- Add Products -->
<div class="form-box">
    <h3>Add New Product</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" required placeholder="Product Name">
        <input type="number" name="price" step="0.01" required placeholder="Selling Price (₹)">
        <input type="number" name="market_price" step="0.01" placeholder="Market Price (₹)">
        <input type="number" name="stock" min="1" required placeholder="Total Stock">

        <label><strong>Available Colours:</strong></label>
        <div id="color-wrapper" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px">
            <input type="color" name="colors[]" value="#000000">
        </div>
        <button type="button" id="addColor">➕ Add Colour</button>

        <select name="category" required>
            <option value="" disabled selected>Select Category</option>
            <option value="electronics">Electronics</option>
            <option value="home">Home</option>
            <option value="appliance">Appliance</option>
            <option value="furniture">Furniture</option>
            <option value="other">Other</option>
        </select>

        <label><strong>Product Images (first one = main image)</strong></label>
        <div id="image-wrapper">
            <input type="file" name="images[]" accept="image/*" required>
        </div>
        <button type="button" id="addImage">➕ Add Image</button>

        <textarea name="description" rows="4" placeholder="Description (optional)"></textarea>
        <button type="submit" name="add_product">Add Product</button>
    </form>
</div>


<!-- Show Uploaded Products -->
<h3 style="text-align:center;">🛍️ Your Uploaded Products</h3>
<?php if ($products->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID</th><th>Thumbnail</th><th>Name</th><th>Price</th><th>Market</th>
            <th>Discount</th><th>Stock</th><th>Colors</th><th>Category</th><th>Images</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $products->fetch_assoc()):
            $discount = ($row['market_price'] > 0) ? round((($row['market_price'] - $row['price']) / $row['market_price']) * 100) : 0;
            $colors = explode(',', $row['colors']);
            $all_images = array_filter(array_merge([$row['image']], explode(',', $row['additional_images'])));
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><img src="../assets/images/<?= $row['image'] ?>" class="thumb"></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= number_format($row['price'], 2) ?></td>
            <td><?= $row['market_price'] ? number_format($row['market_price'], 2) : '-' ?></td>
            <td><?= $discount ? "$discount%" : '-' ?></td>
            <td><?= $row['stock'] ?></td>
            <td>
                <?php foreach ($colors as $color): ?>
                    <span class="color-box" style="background:<?= $color ?>"></span>
                <?php endforeach; ?>
            </td>
            <td><?= ucfirst($row['category']) ?></td>
            <td>
                <button onclick='showImages(<?= json_encode($all_images) ?>)'>🔍 View All</button>
            </td>
            <td class="action-links">
                <a href="edit_product.php?id=<?= $row['id'] ?>">✏️</a>
                <a href="delete_product.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this product?')">🗑️</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center;color:#555;">No products uploaded yet.</p>
<?php endif; ?>



<!-- 🔍 MODAL for viewing images -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h4>All Product Images</h4>
        <div id="modalImages" class="modal-images"></div>
    </div>
</div>

<!-- JS: Add color/image + Modal -->
<script>
document.getElementById('addColor').onclick = () => {
    const wrap = document.getElementById('color-wrapper');
    const inp = document.createElement('input');
    inp.type = 'color'; inp.name = 'colors[]'; inp.value = '#000000';
    wrap.appendChild(inp);
};

document.getElementById('addImage').onclick = () => {
    const wrap = document.getElementById('image-wrapper');
    const inp = document.createElement('input');
    inp.type = 'file'; inp.name = 'images[]'; inp.accept = 'image/*';
    inp.style.marginTop = "10px";
    wrap.appendChild(inp);
};

function showImages(images) {
    const modal = document.getElementById("imageModal");
    const container = document.getElementById("modalImages");
    container.innerHTML = "";
    images.forEach(img => {
        const tag = document.createElement('img');
        tag.src = "../assets/images/" + img;
        container.appendChild(tag);
    });
    modal.style.display = "block";
}

function closeModal() {
    document.getElementById("imageModal").style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById("imageModal");
    if (event.target === modal) closeModal();
};
</script>

</body>
</html>
