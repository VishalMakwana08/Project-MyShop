<?php
require_once '../config.php';
$shop_id = intval($_GET['shop_id']);
$product_ids = [];
$result = mysqli_query($conn, "SELECT product_id FROM products WHERE shop_id = $shop_id");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $product_ids[] = strtoupper($row['product_id']);
    }
}
header('Content-Type: application/json');
echo json_encode($product_ids);