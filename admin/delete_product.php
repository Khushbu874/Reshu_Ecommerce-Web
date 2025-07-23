<?php
include '../config/db.php';
session_start();

// if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
//     header("Location: login.php");
//     exit();
// }

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // Get image name to delete file too
    $img_stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $img_stmt->bind_param("i", $product_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();

    if ($img_result->num_rows > 0) {
        $row = $img_result->fetch_assoc();
        $image_path = "../assets/images/" . $row['image'];

        // Delete the product from DB
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $product_id);
        if ($delete_stmt->execute()) {
            // Delete the image file (optional)
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $_SESSION['success_msg'] = "🗑️ Product deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "❌ Failed to delete product.";
        }
    } else {
        $_SESSION['error_msg'] = "⚠️ Product not found.";
    }
}

header("Location: admin_products.php"); // Or your product listing/admin dashboard
exit();
