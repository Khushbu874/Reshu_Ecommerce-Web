<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<style>
    header {
        background: #2b6777;
        color: white;
        font-family: 'Segoe UI', sans-serif;
    }

    .header-top {
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-top h2 {
        margin: 0;
        font-size: 24px;
    }

    .search-form {
        display: flex;
        gap: 10px;
        padding: 15px 30px;
        background-color: #245a68;
    }

    .search-form input[type="text"],
    .search-form select {
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }

    .search-form button {
        padding: 8px 16px;
        background: #1f4f5a;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .search-form button:hover {
        background: #163c43;
    }

    .nav-row {
        display: flex;
        border-top: 2px solid #1c4a5a;
        border-bottom: 2px solid #1c4a5a;
    }

    .nav-box {
        flex: 1;
        text-align: center;
        padding: 14px;
        border-right: 1px solid #1c4a5a;
        background: #2b6777;
    }

    .nav-box:last-child {
        border-right: none;
    }

    .nav-box a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        display: block;
    }

    .nav-box:hover {
        background-color: #1f4f5a;
    }

    @media (max-width: 700px) {
        .nav-row {
            flex-direction: column;
        }
        .nav-box {
            border-right: none;
            border-bottom: 1px solid #1c4a5a;
        }
        .nav-box:last-child {
            border-bottom: none;
        }
    }
</style>

<header>
    <div class="header-top">
        <h2>🛍 Reshu eCommerce</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <span>Hello, <?= htmlspecialchars($_SESSION['name']); ?></span>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['name'])): ?>
    <form action="search.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search products..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <select name="category">
            <option value="">All Categories</option>
            <option value="electronics" <?= (isset($_GET['category']) && $_GET['category'] == 'electronics') ? 'selected' : '' ?>>Electronics</option>
            <option value="home" <?= (isset($_GET['category']) && $_GET['category'] == 'home') ? 'selected' : '' ?>>Home</option>
            <option value="appliance" <?= (isset($_GET['category']) && $_GET['category'] == 'appliance') ? 'selected' : '' ?>>Appliance</option>
            <option value="furniture" <?= (isset($_GET['category']) && $_GET['category'] == 'furniture') ? 'selected' : '' ?>>Furniture</option>
            <option value="other" <?= (isset($_GET['category']) && $_GET['category'] == 'other') ? 'selected' : '' ?>>Other</option>
        </select>
        <button type="submit">Search</button>
    </form>
    <?php endif; ?>

    <div class="nav-row">
        <div class="nav-box"><a href="index.php">🏠 Home</a></div>

        <?php if (!isset($_SESSION['name'])): ?>
            <div class="nav-box"><a href="login.php">🔐 Login</a></div>
            <div class="nav-box"><a href="register.php">📝 Register</a></div>
        <?php else: ?>
            <div class="nav-box"><a href="cart.php">🛒 Cart</a></div>
            <!-- <div class="nav-box"><a href="wishlist.php">❤️ Wishlist</a></div> -->
            <div class="nav-box"><a href="my_order.php">📦 Orders</a></div>
            <div class="nav-box"><a href="profile.php">Profile</a></div>
            <div class="nav-box"><a href="products.php"> All Products</a></div>
            <!-- <div class="nav-box"><a href="../admin/admin_orders.php">Manage Orders</a></div> -->
            <div class="nav-box"><a href="logout.php">Logout</a></div>
        <?php endif; ?>
    </div>
</header>
