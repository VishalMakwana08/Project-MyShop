<?php
// get_all_shops.php
// Fetches all shop details from the database.

// Enable error reporting for debugging. REMOVE IN PRODUCTION!
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests (for development)
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection details (REPLACE WITH YOUR ACTUAL CREDENTIALS)
$servername = "localhost";
$username = "root"; // Your MySQL username
$password = "";     // Your MySQL password (often empty for XAMPP root)
$dbname = "shop_management_db"; // Your exact database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error and send a JSON response
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit(); // Stop execution
}

$sql = "SELECT id, shop_name, address, owner_name, owner_email, owner_mobile, registration_date, gst_number, shop_license, auto_discount, show_gst_license FROM shops";
$result = $conn->query($sql);

if ($result === false) {
    // Log the SQL error and send a JSON response
    error_log("SQL query failed for get_all_shops.php: " . $conn->error . " Query: " . $sql);
    echo json_encode(["success" => false, "message" => "SQL query failed: " . $conn->error]);
    $conn->close();
    exit(); // Stop execution
}

$shops = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
}

echo json_encode(["success" => true, "shops" => $shops]);

$conn->close();
?>
