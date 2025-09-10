<?php
// This script provides live data for the dashboard without a full page reload.

// Include necessary files and start session
require_once '../config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    // Return an error if not logged in
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$shop_id = intval($_SESSION['shop_id']);

// Set content type to JSON
header('Content-Type: application/json');

// Prepare an array to hold the data
$stats = [
    'total_products' => 0,
    'low_stock_products' => 0,
    'total_sales' => 0,
    'sales_today' => 0,
    'success' => true
];

// Fetch total products
$q1 = mysqli_query($conn, "SELECT COUNT(id) as count FROM products WHERE shop_id = $shop_id AND deleted_at IS NULL");
if ($r = mysqli_fetch_assoc($q1)) {
    $stats['total_products'] = $r['count'];
}

// Fetch low stock products
$q2 = mysqli_query($conn, "SELECT COUNT(id) as count FROM products WHERE shop_id = $shop_id AND stock_quantity < low_stock_threshold AND deleted_at IS NULL");
if ($r = mysqli_fetch_assoc($q2)) {
    $stats['low_stock_products'] = $r['count'];
}

// Fetch total sales
$q3 = mysqli_query($conn, "SELECT IFNULL(SUM(total_amount), 0) as total FROM bills WHERE shop_id = $shop_id");
if ($r = mysqli_fetch_assoc($q3)) {
    $stats['total_sales'] = number_format($r['total'], 2, '.', ''); // Format as a decimal string
}

// Fetch sales today
$q4 = mysqli_query($conn, "SELECT IFNULL(SUM(total_amount), 0) as total FROM bills WHERE shop_id = $shop_id AND DATE(created_at) = CURDATE()");
if ($r = mysqli_fetch_assoc($q4)) {
    $stats['sales_today'] = number_format($r['total'], 2, '.', ''); // Format as a decimal string
}

// Return the data as a JSON object
echo json_encode($stats);

mysqli_close($conn);
?>