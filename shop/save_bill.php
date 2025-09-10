<?php
require_once '../config.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['shop_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['customer_name'], $data['customer_mobile'], $data['items']) || !is_array($data['items'])) {
    echo json_encode(["success" => false, "error" => "Invalid request data"]);
    exit;
}

$customer_name   = mysqli_real_escape_string($conn, trim($data['customer_name']));
$customer_mobile = mysqli_real_escape_string($conn, trim($data['customer_mobile']));
$items           = $data['items'];

$total_amount = 0;
$processed_items = [];

foreach ($items as $item) {
    $product_id = mysqli_real_escape_string($conn, $item['product_id']);

    // Fetch product info, including is_decimal_quantity
    $product_query = mysqli_query($conn, "
        SELECT name, price, stock_quantity, COALESCE(discount_percent, 0) AS discount, is_decimal_quantity
        FROM products 
        WHERE shop_id = $shop_id AND product_id = '$product_id' LIMIT 1
    ");

    if (!$product_query || mysqli_num_rows($product_query) === 0) {
        echo json_encode(["success" => false, "error" => "Product '$product_id' not found"]);
        exit;
    }

    $product = mysqli_fetch_assoc($product_query);

    // Parse quantity based on product setting
    $is_decimal = (int)$product['is_decimal_quantity'] === 1;
    $quantity = $is_decimal ? floatval($item['quantity']) : intval($item['quantity']);

    if ($quantity <= 0) {
        echo json_encode(["success" => false, "error" => "Invalid quantity for product '$product_id'"]);
        exit;
    }

    if ($quantity > $product['stock_quantity']) {
        echo json_encode(["success" => false, "error" => "Insufficient stock for product '$product_id'"]);
        exit;
    }

    $price = floatval($product['price']);
    $discount_percent = floatval($product['discount']);
    $discounted_price = $price;

    if ($discount_percent > 0) {
        $discounted_price = $price - ($price * $discount_percent / 100);
    }

    $line_total = round($discounted_price * $quantity, 2);
    $total_amount += $line_total;

    $processed_items[] = [
        'product_id' => $product_id,
        'name'       => $product['name'],
        'price'      => round($discounted_price, 2),
        'quantity'   => $quantity,
        'total'      => $line_total
    ];
}

$conn->begin_transaction();

try {
    // Insert into bills
    $stmt = $conn->prepare("INSERT INTO bills (shop_id, customer_name, customer_mobile, total_amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $shop_id, $customer_name, $customer_mobile, $total_amount);
    $stmt->execute();
    $bill_id = $stmt->insert_id;
    $stmt->close();

    // Insert items and update stock
    $stmt = $conn->prepare("INSERT INTO bill_items (bill_id, product_id, product_name, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
    $stock_stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE shop_id = ? AND product_id = ?");

    foreach ($processed_items as $item) {
        $stmt->bind_param("issddd", $bill_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $item['total']);
        $stmt->execute();

        $stock_stmt->bind_param("dsi", $item['quantity'], $shop_id, $item['product_id']);
        $stock_stmt->execute();
    }

    $stmt->close();
    $stock_stmt->close();
    $conn->commit();

    echo json_encode(["success" => true, "bill_id" => $bill_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Failed to save bill: " . $e->getMessage()]);
}
