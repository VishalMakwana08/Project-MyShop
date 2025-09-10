<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_management_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the last check time from the request
$last_check_time = isset($_GET['last_check']) ? (int)$_GET['last_check'] : 0;
$last_check_date = date('Y-m-d H:i:s', $last_check_time / 1000); // Convert from milliseconds to seconds

// Initialize response
$response = [
    'success' => true,
    'new_shops' => 0,
    'new_requests' => 0,
    'last_check' => $last_check_time,
    'server_time' => time() * 1000 // Return in milliseconds for consistency
];

try {
    // Check for new shops
    $shop_query = "SELECT COUNT(*) as count FROM shops WHERE created_at > ?";
    $stmt = $conn->prepare($shop_query);
    $stmt->bind_param("s", $last_check_date);
    $stmt->execute();
    $shop_result = $stmt->get_result();
    
    if ($shop_result) {
        $shop_data = $shop_result->fetch_assoc();
        $response['new_shops'] = (int)$shop_data['count'];
    }
    $stmt->close();

    // Check for new contact requests
    $request_query = "SELECT COUNT(*) as count FROM contact_requests WHERE created_at > ?";
    $stmt = $conn->prepare($request_query);
    $stmt->bind_param("s", $last_check_date);
    $stmt->execute();
    $request_result = $stmt->get_result();
    
    if ($request_result) {
        $request_data = $request_result->fetch_assoc();
        $response['new_requests'] = (int)$request_data['count'];
    }
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error checking for new data: ' . $e->getMessage();
}

// Close connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>