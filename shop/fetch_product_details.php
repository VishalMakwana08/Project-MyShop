<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['shop_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Session expired. Please login again."
    ]);
    exit;
}

$shop_id = $_SESSION['shop_id'];
$product_id = strtoupper(trim($_POST['product_id'] ?? ''));

if (empty($product_id)) {
    echo json_encode([
        "success" => false,
        "message" => "Product ID is required."
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT name, price, stock_quantity, is_decimal_quantity FROM products WHERE shop_id = ? AND product_id = ?");
$stmt->bind_param("is", $shop_id, $product_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Product not found for this shop."
    ]);
    exit;
}

$stmt->bind_result($name, $price, $stock_quantity, $is_decimal_quantity);
$stmt->fetch();

echo json_encode([
    "success" => true,
    "data" => [
        "name" => $name,
        "price" => floatval($price),
        "stock" => floatval($stock_quantity),
        "is_decimal_quantity" => boolval($is_decimal_quantity)
    ]
]);
?>
