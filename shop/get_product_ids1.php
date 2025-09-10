<?php
// get_product_ids.php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_GET['product_id'])) {
    echo json_encode(['exists' => false]);
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$product_id = trim($_GET['product_id']);

header('Content-Type: application/json');

if (empty($product_id)) {
    echo json_encode(['exists' => false]);
    exit;
}

$sql = "SELECT product_id FROM products WHERE shop_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $shop_id, $product_id);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

echo json_encode(['exists' => $exists]);

$conn->close();
?>