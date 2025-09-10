<?php
// Database credentials
define('DB_SERVER', 'localhost'); // Your database server
define('DB_USERNAME', 'root');     // Your database username
define('DB_PASSWORD', '');         // Your database password
define('DB_NAME', 'shop_management_db'); // Your database name


// ... (rest of your config.php) ...

// Example: Session handling


// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}   

// Set charset to utf8mb4 for proper character handling
$conn->set_charset("utf8mb4");

// Start session (optional for this page, but good practice for future login/user management)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// You can uncomment the line below for debugging purposes to confirm connection
// echo "Database connected successfully!";
?>