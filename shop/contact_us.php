<?php
// include 'check_internet.php';
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$shop_id = $_SESSION["shop_id"];
$owner_name = $_SESSION["owner_name"];
$owner_email = $_SESSION["owner_email"];

$subject = $message = "";
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs using mysqli_real_escape_string
    // NOTE: This approach is less secure than prepared statements against SQL Injection.
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    
    // Escaping fixed values like $owner_name and $owner_email for consistency,
    // though they come from session and are generally safe.
    $escaped_owner_name = mysqli_real_escape_string($conn, $owner_name);
    $escaped_owner_email = mysqli_real_escape_string($conn, $owner_email);
    $escaped_shop_id = intval($shop_id); // intval for integers is safer than string escaping

    if (empty($subject) || empty($message)) {
        $error = "❌ Please fill all fields.";
    } else {
        // Construct the SQL query directly
        $sql = "INSERT INTO contact_requests (shop_id, owner_name, owner_email, subject, message)
                VALUES ('$escaped_shop_id', '$escaped_owner_name', '$escaped_owner_email', '$subject', '$message')";
        
        // Execute the query
        if (mysqli_query($conn, $sql)) {
            $success = "✅ Your request has been sent to the admin. We'll get back to you soon!";
            $subject = $message = ""; // clear form fields
        } else {
            $error = "❌ Failed to send request. Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Contact Admin - RetailFlow POS</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
       <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        /* Custom Keyframe Animations */
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.5s ease-out forwards;
        }
        /* For messages to fade out */
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased text-gray-800">



<main class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 border border-gray-100 animate-fade-in">
       <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between">
    
   <!-- Heading -->
<h1 class="text-3xl font-extrabold text-indigo-700 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" 
         class="h-8 w-8 text-indigo-700" fill="none" 
         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" 
              d="M8 10h.01M12 10h.01M16 10h.01M21 
                 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4-.8L3 
                 20l1.8-3.6A7.94 7.94 0 013 12c0-4.418 
                 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    Contact Us
</h1>


    <!-- Buttons -->
    <div class="flex gap-3">
         <a href="dashboard.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg shadow-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition duration-200 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    Dashboard
                </a>
        <a href="logout.php" class="inline-flex items-center bg-red-500 text-white px-4 py-2 rounded-lg shadow hover:bg-red-600 transition">
            Logout
        </a>
    </div></div>
        <?php if ($success): ?>
            <div id="successMessage" class="bg-green-100 text-green-800 px-5 py-3 rounded-lg mb-6 flex items-center space-x-3 border border-green-200 shadow-sm">
                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium"><?= $success ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div id="errorMessage" class="bg-red-100 text-red-800 px-5 py-3 rounded-lg mb-6 flex items-center space-x-3 border border-red-200 shadow-sm">
                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                <input type="text" id="owner_name" value="<?= htmlspecialchars($owner_name) ?>" disabled class="w-full border border-gray-200 px-4 py-2 rounded-md shadow-sm bg-gray-50 text-gray-600 cursor-not-allowed">
            </div>
            <div>
                <label for="owner_email" class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                <input type="email" id="owner_email" value="<?= htmlspecialchars($owner_email) ?>" disabled class="w-full border border-gray-200 px-4 py-2 rounded-md shadow-sm bg-gray-50 text-gray-600 cursor-not-allowed">
            </div>
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($subject) ?>" 
                       class="w-full border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base placeholder-gray-400" 
                       placeholder="e.g., Issue with billing, Feature request" required>
            </div>
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                <textarea name="message" id="message" rows="6" 
                          class="w-full border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base placeholder-gray-400" 
                          placeholder="Describe your issue or request in detail..." required><?= htmlspecialchars($message) ?></textarea>
            </div>
            <div>
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 ease-in-out font-semibold text-lg">
                    <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Send Message
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    // JavaScript to fade out messages after a few seconds
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');

        if (successMessage) {
            setTimeout(() => {
                successMessage.classList.add('fade-out');
                successMessage.addEventListener('transitionend', () => successMessage.remove());
            }, 5000); // 5 seconds before starting fade
        }
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.classList.add('fade-out');
                errorMessage.addEventListener('transitionend', () => errorMessage.remove());
            }, 7000); // 7 seconds for error messages
        }
    });
</script>

</body>
</html>