<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['shop_id'])) {
    echo json_encode(["success" => false, "message" => "Session expired."]);
    exit;
}

$shop_id = $_SESSION['shop_id'];
$product_id = strtoupper(trim($_POST['product_id'] ?? ''));
$quantity = floatval($_POST['quantity'] ?? 0);

if (!$product_id || $quantity <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid product ID or quantity."]);
    exit;
}

// Fetch product info
$stmt = $conn->prepare("SELECT name, price, stock_quantity, is_decimal_quantity FROM products WHERE shop_id = ? AND product_id = ?");
$stmt->bind_param("is", $shop_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Product not found."]);
    exit;
}

$product = $result->fetch_assoc();

// Check stock
if ($quantity > $product['stock_quantity']) {
    echo json_encode(["success" => false, "message" => "Not enough stock available."]);
    exit;
}

$total = $product['price'] * $quantity;

$_SESSION['current_bill'] = $_SESSION['current_bill'] ?? [];
$_SESSION['current_bill'][] = [
    'product_id' => $product_id,
    'name' => $product['name'],
    'price' => $product['price'],
    'quantity' => $quantity,
    'total' => $total
];

// Recalculate grand total
$total_bill_amount = array_reduce($_SESSION['current_bill'], function ($carry, $item) {
    return $carry + $item['total'];
}, 0);

echo json_encode([
    "success" => true,
    "message" => "Item added to bill.",
    "current_bill_items" => $_SESSION['current_bill'],
    "total_bill_amount" => $total_bill_amount
]);
mysqli_close($conn);
exit;

