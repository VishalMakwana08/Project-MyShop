<?php
// include 'check_internet.php';
// register.php
// This file contains the HTML for the registration form,
// JavaScript for AJAX submission, and PHP for processing the registration.

// Start the session (needed if you plan to extend with session-based login later,
// though this specific file only handles registration for now).
session_start();

// Set HTTP headers for JSON content type and CORS (Cross-Origin Resource Sharing)
// This is important for AJAX requests from different origins during development.
header('Content-Type: text/html'); // Default to HTML for initial page load
header('Access-Control-Allow-Origin: *'); // Allows requests from any origin (for development)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allowed HTTP methods
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allowed headers

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // Respond to OPTIONS request and exit
}

// Database connection details (!!! IMPORTANT: REPLACE WITH YOUR ACTUAL CREDENTIALS !!!)
$servername = "localhost";
$username = "root"; // Example: Your MySQL username (e.g., 'root' for XAMPP default)
$password = "";     // Example: Your MySQL password (e.g., '' for XAMPP default)
$dbname = "shop_management_db"; // Your database name

// --- PHP Logic for Handling POST Request (Form Submission via AJAX) ---
// This part will only execute if an AJAX POST request is made to this file.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set header to JSON because we are sending a JSON response back to JavaScript.
    header('Content-Type: application/json');

    // Create database connection for POST request processing
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check database connection
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
        exit();
    }

    // --- Check if any admin user already exists (for POST request) ---
    // This check is duplicated here to ensure consistency, even though
    // the GET request might redirect. It catches cases where someone
    // might bypass the initial GET redirect or if the database state changes.
    $countStmt = $conn->prepare("SELECT COUNT(*) AS user_count FROM admin_users");
    if ($countStmt === false) {
        echo json_encode(["success" => false, "message" => "Failed to prepare user count check: " . $conn->error]);
        $conn->close();
        exit();
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $userCount = $countResult->fetch_assoc()['user_count'];
    $countStmt->close();

    if ($userCount > 0) {
        echo json_encode(["success" => false, "message" => "Registration is not allowed. An admin user already exists."]);
        $conn->close();
        exit(); // Stop execution if registration is not allowed
    }
    // --- END POST CHECK ---

    // Get the raw POST data (which will be JSON from the JavaScript fetch request)
    $input = json_decode(file_get_contents('php://input'), true);

    // Extract data from the decoded JSON input
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    // Basic server-side validation
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        $conn->close(); // Close connection before exiting
        exit();
    }

    // Hash the password securely before storing it in the database
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        $conn->close();
        exit();
    }
    // Bind parameters: 'sss' means three string parameters
    $stmt->bind_param("sss", $username, $email, $passwordHash);

    // Execute the prepared statement
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Registration successful! You can now log in."]);
    } else {
        // Check for specific MySQL error code for duplicate entry (e.g., unique constraint violation)
        if ($conn->errno == 1062) { // MySQL error code for duplicate entry
            echo json_encode(["success" => false, "message" => "Username or Email already exists. Please use a different one."]);
        } else {
            echo json_encode(["success" => false, "message" => "Registration failed: " . $stmt->error]);
        }
    }

    // Close the statement and database connection
    $stmt->close();
    $conn->close();
    exit(); // Stop execution after sending JSON response for POST request
}

// --- PHP Logic for Initial GET Request (Page Load) ---
// This part will only execute if the file is accessed directly via a GET request (e.g., in browser URL bar).

// Create database connection for GET request processing
$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    // If database connection fails on initial load, display a simple error or redirect
    echo "Error: Could not connect to the database. Please check your configuration.";
    exit();
}

// --- NEW: Check if any admin user already exists (for GET request) ---
$countStmt = $conn->prepare("SELECT COUNT(*) AS user_count FROM admin_users");
if ($countStmt === false) {
    echo "Error: Failed to prepare user count check on page load.";
    $conn->close();
    exit();
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$userCount = $countResult->fetch_assoc()['user_count'];
$countStmt->close();
$conn->close(); // Close connection after check

if ($userCount > 0) {
    // If an admin user already exists, redirect to the login page
    header('Location: login.php'); // Redirect to your login page
    exit(); // Important: Stop script execution after redirection
}
// --- END NEW CHECK ---

// If no admin user exists, proceed to display the registration form HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <!-- Tailwind CSS CDN for modern, responsive styling -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <!-- Google Fonts - Inter for a clean look -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> -->
      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        /* Custom CSS for a better visual experience and responsiveness */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Full viewport height */
            margin: 0;
            padding: 1rem; /* Add some padding for smaller screens */
            box-sizing: border-box;
        }
        .form-container {
            background-color: #ffffff; /* White background for the form card */
            padding: 2.5rem; /* Ample padding inside the card */
            border-radius: 0.75rem; /* Rounded corners for the card */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Subtle shadow */
            width: 100%;
            max-width: 420px; /* Max width for larger screens */
            box-sizing: border-box; /* Include padding in element's total width */
        }
        .input-field {
            width: 100%; /* Full width input fields */
            padding: 0.75rem; /* Padding inside input */
            margin-bottom: 1rem; /* Space below each input */
            border: 1px solid #d1d5db; /* Light gray border */
            border-radius: 0.375rem; /* Rounded input corners */
            font-size: 1rem; /* Standard font size */
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* Smooth transition on focus */
        }
        .input-field:focus {
            outline: none; /* Remove default outline */
            border-color: #3b82f6; /* Blue border on focus */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); /* Blue shadow on focus */
        }
        .btn-primary {
            width: 100%; /* Full width button */
            padding: 0.75rem; /* Padding inside button */
            background-color: #3b82f6; /* Primary blue background */
            color: white; /* White text */
            border-radius: 0.375rem; /* Rounded button corners */
            font-weight: 600; /* Semi-bold text */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.2s ease-in-out; /* Smooth transition on hover */
            border: none; /* Remove default button border */
        }
        .btn-primary:hover {
            background-color: #2563eb; /* Darker blue on hover */
        }
        .message-box {
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        .message-success {
            background-color: #d1fae5; /* Light green background */
            color: #065f46; /* Dark green text */
        }
        .message-error {
            background-color: #fee2e2; /* Light red background */
            color: #991b1b; /* Dark red text */
        }
        .hidden {
            display: none !important; /* Utility class to hide elements */
        }
        /* Style for the password toggle container */
        .password-input-container {
            position: relative;
            margin-bottom: 1rem; /* Adjust as needed */
        }
        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.25rem; /* Adjust icon size */
            color: #6b7280; /* Gray color for the icon */
            padding: 0.25rem;
            line-height: 1; /* Ensure icon is vertically centered */
        }
        .password-toggle-btn:hover {
            color: #374151; /* Darker gray on hover */
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Admin Registration</h2>

        <!-- Message box for displaying success/error messages -->
        <div id="message" class="message-box hidden"></div>

        <!-- Registration Form -->
        <form id="registerForm" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" class="input-field" required autocomplete="username">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" class="input-field" required autocomplete="email">
            </div>
            <div class="password-input-container">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" class="input-field pr-10" required autocomplete="new-password">
                <button type="button" id="togglePassword" class="password-toggle-btn">
                    👁️
                </button>
            </div>
            <button type="submit" class="btn-primary">Register</button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-4">
            Already have an account? <a href="login.php" class="text-blue-600 hover:underline">Login here</a>
            <!-- Assuming you will create a separate login.php file -->
        </p>
    </div>

    <script>
        // Get references to HTML elements
        const registerForm = document.getElementById('registerForm');
        const messageBox = document.getElementById('message');
        const passwordInput = document.getElementById('password');
        const togglePasswordButton = document.getElementById('togglePassword');

        // --- Function to display messages ---
        // Shows a message in the messageBox element with appropriate styling.
        function showMessage(msg, type) {
            messageBox.textContent = msg; // Set message text
            // Apply CSS classes based on message type (success or error)
            messageBox.className = `message-box ${type === 'success' ? 'message-success' : 'message-error'}`;
            messageBox.classList.remove('hidden'); // Make message box visible
            // Hide the message after 5 seconds
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 5000);
        }

        // --- Password Toggle Functionality ---
        togglePasswordButton.addEventListener('click', () => {
            // Toggle the type attribute between 'password' and 'text'
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Change the eye icon based on visibility
            togglePasswordButton.textContent = type === 'password' ? '👁️' : '🔒'; // You can use different icons/emojis
        });


        // --- Event Listener for Form Submission ---
        // This function runs when the registration form is submitted.
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevent the default form submission (which would cause a page reload)

            // Get values from the form input fields
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                // Send an AJAX POST request to this same PHP file (register.php)
                const response = await fetch('register.php', {
                    method: 'POST', // Specify the HTTP method as POST
                    headers: {
                        'Content-Type': 'application/json' // Tell the server we are sending JSON data
                    },
                    // Convert the JavaScript object to a JSON string and send it as the request body
                    body: JSON.stringify({ username, email, password })
                });

                // Parse the JSON response from the PHP script
                const data = await response.json();

                // Check the 'success' status from the PHP response
                if (data.success) {
                    showMessage(data.message, 'success'); // Display success message
                    registerForm.reset(); // Clear the form fields
                    // Optionally, you might redirect to a login page or show login form here
                    // window.location.href = 'login.php'; // Example redirect
                } else {
                    showMessage(data.message, 'error'); // Display error message
                }
            } catch (error) {
                // Catch any network errors or issues with the fetch request
                console.error('Registration error:', error);
                showMessage('An unexpected error occurred during registration. Please try again.', 'error');
            }
        });
    </script>
</body>
</html>
