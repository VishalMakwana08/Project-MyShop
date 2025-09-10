<?php
require_once '../config.php';

// Check login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$shop_id = $_SESSION['shop_id']; // Get shop_id from session

// Initialize variables
$shop_name = $address = $owner_name = $owner_email = $owner_mobile = $gst_number = $shop_license = $shop_image_path = "";
$shop_name_err = $address_err = $owner_name_err = $owner_email_err = $owner_mobile_err = $shop_image_err = $shop_type_err = "";
$update_success = $update_error = "";
$target_dir = "uploads/shop_images/"; // Directory for shop images

// Create uploads directory if it doesn't exist
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Fetch shop data using pure mysqli
// NOTE: For integer IDs, intval() is generally safer than mysqli_real_escape_string()
$escaped_shop_id_fetch = intval($shop_id);
$sql_fetch = "SELECT shop_name, address, owner_name, owner_email, owner_mobile, gst_number, shop_license, shop_image_path FROM shops WHERE id = $escaped_shop_id_fetch";
$result_fetch = mysqli_query($conn, $sql_fetch);

if ($result_fetch && mysqli_num_rows($result_fetch) > 0) {
    $row = mysqli_fetch_assoc($result_fetch);
    $shop_name = $row['shop_name'];
    $address = $row['address'];
    $owner_name = $row['owner_name'];
    $owner_email = $row['owner_email'];
    $owner_mobile = $row['owner_mobile'];

    $gst_number = $row['gst_number'];
    $shop_license = $row['shop_license'];
    $shop_image_path = $row['shop_image_path'];
} else {
    // Handle case where shop data might not be found (unlikely if logged in)
    $update_error = "Could not retrieve shop data.";
}

// Handle POST request for updating profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    // Using mysqli_real_escape_string for all string inputs
    // NOTE: This approach is less secure than prepared statements against SQL Injection.
    $shop_name = mysqli_real_escape_string($conn, trim($_POST["shop_name"]));
    $address = mysqli_real_escape_string($conn, trim($_POST["address"]));
    $owner_name = mysqli_real_escape_string($conn, trim($_POST["owner_name"]));
    $owner_email = mysqli_real_escape_string($conn, trim($_POST["owner_email"]));
    $owner_mobile = mysqli_real_escape_string($conn, trim($_POST["owner_mobile"]));
    $gst_number = mysqli_real_escape_string($conn, trim($_POST["gst_number"]));
    $shop_license = mysqli_real_escape_string($conn, trim($_POST["shop_license"]));

    // Validation checks
    if (empty($shop_name))
        $shop_name_err = "Shop name is required.";
    if (empty($address))
        $address_err = "Address is required.";
    if (empty($owner_name))
        $owner_name_err = "Owner name is required.";
    if (!filter_var($owner_email, FILTER_VALIDATE_EMAIL))
        $owner_email_err = "Invalid email format.";
    if (!preg_match("/^[0-9]{10}$/", $owner_mobile))
        $owner_mobile_err = "Mobile number must be 10 digits.";

    // Handle image upload
    if (isset($_FILES["shop_image"]) && $_FILES["shop_image"]["error"] == 0) {
        $imageFileType = strtolower(pathinfo($_FILES["shop_image"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($imageFileType, $allowed)) {
            $shop_image_err = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif ($_FILES["shop_image"]["size"] > 5 * 1024 * 1024) { // 5MB limit
            $shop_image_err = "File is too large. Maximum 5MB allowed.";
        } else {
            // Generate a unique filename
            $new_file_name = uniqid("shop_") . "." . $imageFileType;
            $final_path_upload = $target_dir . $new_file_name;

            // Move uploaded file
            if (move_uploaded_file($_FILES["shop_image"]["tmp_name"], $final_path_upload)) {
                // If there was an old image, delete it
                if (!empty($shop_image_path) && file_exists($shop_image_path)) {
                    unlink($shop_image_path);
                }
                $shop_image_path = $final_path_upload; // Update path for database
            } else {
                $shop_image_err = "Failed to upload image.";
            }
        }
    }

    // If no validation errors, proceed with update
    if (empty($shop_name_err) && empty($address_err) && empty($owner_name_err) && empty($owner_email_err) && empty($owner_mobile_err) && empty($shop_type_err) && empty($shop_image_err)) {
        // Build SQL UPDATE query string
        $sql_update = "UPDATE shops SET 
                       shop_name = '$shop_name', 
                       address = '$address', 
                       owner_name = '$owner_name', 
                       owner_email = '$owner_email', 
                       owner_mobile = '$owner_mobile', 
                      
                       gst_number = '$gst_number', 
                       shop_license = '$shop_license', 
                       shop_image_path = '$shop_image_path' 
                       WHERE id = $escaped_shop_id_fetch"; // Re-using the intval'd shop_id

        // Execute the update query
        if (mysqli_query($conn, $sql_update)) {
            $update_success = "Profile updated successfully!";
            // Update session variables if they might have changed and are used elsewhere
            $_SESSION['shop_name'] = htmlspecialchars($shop_name);
            $_SESSION['owner_name'] = htmlspecialchars($owner_name);
            $_SESSION['owner_email'] = htmlspecialchars($owner_email);

        } else {
            $update_error = "Error updating profile: " . mysqli_error($conn);
        }
    } else {
        $update_error = "Please fix the errors in the form.";
    }
}
mysqli_close($conn); // Close connection at the end
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Shop Profile - RetailFlow POS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
                   <!-- <link rel="stylesheet" href="../tailwind/src/output.css" /> -->
                      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">

    <style>
        /* Custom Keyframe Animations */
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

<body class="bg-gray-50 font-sans antialiased text-gray-800 min-h-screen flex flex-col">

    <header class="bg-white shadow-md">
    <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between items-center">
        <!-- Left: Heading -->
        <h1 class="text-2xl font-bold text-indigo-700">⚙️ Edit Shop Profile</h1>

        <!-- Right: Buttons -->
        <div class="flex space-x-3">
          <a href="dashboard.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg shadow-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition duration-200 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    Dashboard
                </a>
        <a href="logout.php" class="inline-flex items-center bg-red-500 text-white px-4 py-2 rounded-lg shadow hover:bg-red-600 transition">
            Logout
        </a>
        </div>
    </div>
</header>

    <main class="flex-grow max-w-3xl mx-auto px-4 py-8 w-full">
        <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 border border-gray-100 animate-fade-in">
            <h2 class="text-2xl font-bold text-gray-700 mb-6 text-center">Your Shop Details</h2>

            <?php if ($update_success): ?>
                <div id="successMessage"
                    class="bg-green-100 text-green-800 px-5 py-3 rounded-lg mb-6 flex items-center space-x-3 border border-green-200 shadow-sm">
                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium"><?= $update_success ?></p>
                </div>
            <?php endif; ?>
            <?php if ($update_error): ?>
                <div id="errorMessage"
                    class="bg-red-100 text-red-800 px-5 py-3 rounded-lg mb-6 flex items-center space-x-3 border border-red-200 shadow-sm">
                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium"><?= $update_error ?></p>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-5">
                <div class="text-center mb-6">
                    <label for="shop_image" class="cursor-pointer inline-block group">
                        <?php if (!empty($shop_image_path) && file_exists($shop_image_path)): ?>
                            <img id="previewImage" src="<?= htmlspecialchars($shop_image_path) ?>" alt="Shop Image"
                                class="w-32 h-32 rounded-full object-cover border-4 border-indigo-300 group-hover:border-indigo-500 transition-colors duration-200 mx-auto shadow-md">
                        <?php else: ?>
                            <div
                                class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center mx-auto border-4 border-indigo-300 group-hover:border-indigo-500 transition-colors duration-200 text-gray-500 text-sm font-semibold shadow-md">
                                <svg class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5 1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                        <div
                            class="text-indigo-600 group-hover:text-indigo-800 mt-2 text-sm font-medium transition-colors duration-200">
                            Click to update image</div>
                    </label>
                    <input type="file" name="shop_image" id="shop_image" class="hidden"
                        accept="image/jpeg, image/png, image/gif">
                    <?php if ($shop_image_err): ?>
                        <div class="text-red-600 text-sm mt-1"><?= $shop_image_err ?></div><?php endif; ?>
                </div>

                <div>
                    <label for="shop_name" class="block text-sm font-medium text-gray-700 mb-1">Shop Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="shop_name" id="shop_name" value="<?= htmlspecialchars($shop_name) ?>"
                        placeholder="Your Shop Name"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    <?php if ($shop_name_err): ?>
                        <p class="text-red-600 text-sm mt-1"><?= $shop_name_err ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address <span
                            class="text-red-500">*</span></label>
                    <textarea name="address" id="address" rows="3" placeholder="Your Shop Address"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required><?= htmlspecialchars($address) ?></textarea>
                    <?php if ($address_err): ?>
                        <p class="text-red-600 text-sm mt-1"><?= $address_err ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">Owner Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="owner_name" id="owner_name" value="<?= htmlspecialchars($owner_name) ?>"
                        placeholder="Your Name"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    <?php if ($owner_name_err): ?>
                        <p class="text-red-600 text-sm mt-1"><?= $owner_name_err ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="owner_email" class="block text-sm font-medium text-gray-700 mb-1">Owner Email <span
                            class="text-red-500">*</span></label>
                    <input type="email" name="owner_email" id="owner_email"
                        value="<?= htmlspecialchars($owner_email) ?>" placeholder="your.email@example.com"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    <?php if ($owner_email_err): ?>
                        <p class="text-red-600 text-sm mt-1"><?= $owner_email_err ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="owner_mobile" class="block text-sm font-medium text-gray-700 mb-1">Owner Mobile <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="owner_mobile" id="owner_mobile"
                        value="<?= htmlspecialchars($owner_mobile) ?>" placeholder="e.g., 9876543210"
                        pattern="[0-9]{10}"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    <?php if ($owner_mobile_err): ?>
                        <p class="text-red-600 text-sm mt-1"><?= $owner_mobile_err ?></p><?php endif; ?>
                </div>



                <div>
                    <label for="gst_number" class="block text-sm font-medium text-gray-700 mb-1">GST Number
                        (Optional)</label>
                    <input type="text" name="gst_number" id="gst_number" value="<?= htmlspecialchars($gst_number) ?>"
                        placeholder="e.g., 22AAAAA0000A1Z5"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div>
                    <label for="shop_license" class="block text-sm font-medium text-gray-700 mb-1">Shop License
                        (Optional)</label>
                    <input type="text" name="shop_license" id="shop_license"
                        value="<?= htmlspecialchars($shop_license) ?>" placeholder="e.g., ABC-12345"
                        class="w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <button type="submit"
                    class="w-full inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 ease-in-out font-semibold text-lg">
                    <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    Update Profile
                </button>
            </form>
        </div>
    </main>

    <script>
        // JavaScript to fade out messages after a few seconds
        document.addEventListener('DOMContentLoaded', function () {
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
    <script>
        // Existing fade-out code...

        // Image preview logic
        document.getElementById('shop_image').addEventListener('change', function (event) {
            const preview = document.getElementById('previewImage');
            const file = event.target.files[0];

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    </script>


</body>

</html>