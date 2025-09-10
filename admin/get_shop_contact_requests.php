<?php
// get_shop_contact_requests.php
// Fetches contact requests for a SPECIFIC shop from the database.

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
    error_log("Database connection failed in get_shop_contact_requests.php: " . $conn->connect_error);
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit(); // Stop execution
}

if (isset($_GET['shop_id'])) {
    $shopId = intval($_GET['shop_id']); // Ensure it's an integer for security

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, owner_name, owner_email, subject, message, created_at FROM contact_requests WHERE shop_id = ? ORDER BY created_at DESC");

    if ($stmt === false) {
        // Log the SQL prepare error and send a JSON response
        error_log("Prepare failed in get_shop_contact_requests.php: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Failed to prepare statement: " . $conn->error]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("i", $shopId); // 'i' for integer type

    if (!$stmt->execute()) {
        // Log the SQL execute error and send a JSON response
        error_log("Execute failed in get_shop_contact_requests.php: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Failed to execute statement: " . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }

    $result = $stmt->get_result();

    $requests = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    echo json_encode(["success" => true, "contact_requests" => $requests]);
    $stmt->close();

} else {
    echo json_encode(["success" => false, "message" => "Shop ID not provided."]);
}

$conn->close();
?>
