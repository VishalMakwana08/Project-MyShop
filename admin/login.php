<?php
// include 'check_internet.php';
// login.php
// This file contains the HTML for the login form,
// JavaScript for AJAX submission, and PHP for processing the login.

// Start the session to manage login state.
// This must be at the very top of the script before any output.
session_start();

// Set HTTP headers for JSON content type and CORS (Cross-Origin Resource Sharing)
header('Content-Type: text/html'); // Default to HTML for initial page load
header('Access-Control-Allow-Origin: *'); // Allows requests from any origin (for development)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allowed HTTP methods
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allowed headers

// Handle preflight OPTIONS request for CORS.
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

    // Get the raw POST data (which will be JSON from the JavaScript fetch request)
    $input = json_decode(file_get_contents('php://input'), true);

    // Extract data from the decoded JSON input
    $identifier = $input['identifier'] ?? ''; // Can be username or email
    $password = $input['password'] ?? '';

    // Basic server-side validation
    if (empty($identifier) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Username/Email and Password are required."]);
        $conn->close();
        exit();
    }

    // Prepare SQL statement to fetch user by username or email
    // Using prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, email, password_hash FROM admin_users WHERE username = ? OR email = ?");
    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Failed to prepare statement: " . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt->bind_param("ss", $identifier, $identifier); // Bind the identifier to both username and email placeholders
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify the provided password against the hashed password from the database
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct, set session variables for authentication
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];

            echo json_encode(["success" => true, "message" => "Login successful!"]);
        } else {
            // Password does not match
            echo json_encode(["success" => false, "message" => "Invalid credentials."]);
        }
    } else {
        // No user found with the given username or email
        echo json_encode(["success" => false, "message" => "Invalid credentials."]);
    }

    // Close the statement and database connection
    $stmt->close();
    $conn->close();
    exit(); // Stop execution after sending JSON response for POST request
}

// --- PHP Logic for Initial GET Request (Page Load) ---
// This part will only execute if the file is accessed directly via a GET request (e.g., in browser URL bar).

// Check if the admin is already logged in. If so, redirect to the dashboard.
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php '); // Redirect to your admin dashboard
    exit(); // Important: Stop script execution after redirection
}

// If not logged in, proceed to display the login form HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <!-- Tailwind CSS CDN for modern, responsive styling -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <!-- Google Fonts - Inter for a clean look -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> -->
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
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Admin Login</h2>

        <!-- Message box for displaying success/error messages -->
        <div id="message" class="message-box hidden"></div>

        <!-- Login Form -->
        <form id="loginForm" class="space-y-4">
            <div>
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">Username or Email</label>
                <input type="text" id="identifier" name="identifier" class="input-field" required autocomplete="username">
            </div>
            <div class="password-input-container">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" class="input-field pr-10" required autocomplete="current-password">
                <button type="button" id="togglePassword" class="password-toggle-btn">
                    👁️
                </button>
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-4">
            Don't have an account? <a href="index.php" class="text-blue-600 hover:underline">Register here</a>
        </p>
    </div>

    <script>
        // Get references to HTML elements
        const loginForm = document.getElementById('loginForm');
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
        // This function runs when the login form is submitted.
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevent the default form submission (which would cause a page reload)

            // Get values from the form input fields
            const identifier = document.getElementById('identifier').value;
            const password = document.getElementById('password').value;

            try {
                // Send an AJAX POST request to this same PHP file (login.php)
                const response = await fetch('login.php', {
                    method: 'POST', // Specify the HTTP method as POST
                    headers: {
                        'Content-Type': 'application/json' // Tell the server we are sending JSON data
                    },
                    // Convert the JavaScript object to a JSON string and send it as the request body
                    body: JSON.stringify({ identifier, password })
                });

                // Parse the JSON response from the PHP script
                const data = await response.json();

                // Check the 'success' status from the PHP response
                if (data.success) {
                    showMessage(data.message, 'success'); // Display success message
                    loginForm.reset(); // Clear the form fields
                    // Redirect to the admin dashboard on successful login
                    window.location.href = 'dashboard.php'; // Adjust this path if dashboard.php is elsewhere
                } else {
                    showMessage(data.message, 'error'); // Display error message
                }
            } catch (error) {
                // Catch any network errors or issues with the fetch request
                console.error('Login error:', error);
                showMessage('An unexpected error occurred during login. Please try again.', 'error');
            }
        });
    </script>
</body>
</html>
