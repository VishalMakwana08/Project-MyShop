<?php
// get_all_contact_requests.php
// Fetches ALL contact requests from the database.

// Enable error reporting for debugging. REMOVE THESE LINES IN PRODUCTION!
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
    error_log("Database connection failed in get_all_contact_requests.php: " . $conn->connect_error);
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit(); // Stop execution
}

// Fetch all contact requests, ordered by creation date (newest first)
// Ensure 'shop_id' is included so the JavaScript can map it to shop names.
$sql = "SELECT id, shop_id, owner_name, owner_email, subject, message, created_at FROM contact_requests ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result === false) {
    // Log the SQL error and send a JSON response
    error_log("SQL query failed for get_all_contact_requests.php: " . $conn->error . " Query: " . $sql);
    echo json_encode(["success" => false, "message" => "SQL query failed: " . $conn->error]);
    $conn->close();
    exit(); // Stop execution
}

$contactRequests = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $contactRequests[] = $row;
    }
}

echo json_encode(["success" => true, "contact_requests" => $contactRequests]);

$conn->close();
?>
