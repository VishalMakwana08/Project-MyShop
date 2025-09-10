<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header("Location: login.php");
    exit;
}

$shop_id = $_SESSION['shop_id'];
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate ID
if ($product_id <= 0) {
    $_SESSION['product_error'] = "Invalid product ID.";
    header("Location: products.php");
    exit;
}

// Fetch product to get image path
$stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $product_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['product_error'] = "Product not found or unauthorized access.";
    $stmt->close();
    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();
$image_path = $product['image_path'];
$stmt->close();

// Delete the product
$stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $product_id, $shop_id);

if ($stmt->execute()) {
    // Remove image if exists
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }
    $_SESSION['product_success'] = "✅ Product deleted successfully.";
} else {
    $_SESSION['product_error'] = "❌ Failed to delete product.";
}

$stmt->close();
header("Location: products.php");
exit;
?>
