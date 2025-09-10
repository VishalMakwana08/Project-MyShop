<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['shop_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$shop_id = $_SESSION['shop_id'];
$name = trim($_POST['name'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Category name required']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO categories (shop_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $shop_id, $name);
    $stmt->execute();
    $id = $stmt->insert_id;

    echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
} catch (mysqli_sql_exception $e) {
    // If duplicate category
    if ($e->getCode() === 1062) {
        echo json_encode(['success' => false, 'message' => 'Category already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
