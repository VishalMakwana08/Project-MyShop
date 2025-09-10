<?php
// include 'check_internet.php';
require_once '../config.php';
$gst_number_err = $shop_license_err = "";

$shop_name = $address = $owner_name = $owner_email = $owner_mobile = $password = $confirm_password = "";
$gst_number = $shop_license = "";
$shop_image_path = "";

$shop_name_err = $address_err = $owner_name_err = $owner_email_err = $owner_mobile_err = $shop_image_err = $password_err = $confirm_password_err = "";
$registration_success = $registration_error = "";

$target_dir = "uploads/shop_images/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $shop_name      = trim($_POST["shop_name"]);
    $address        = trim($_POST["address"]);
    $owner_name     = trim($_POST["owner_name"]);
    $owner_email    = trim($_POST["owner_email"]);
    $owner_mobile   = trim($_POST["owner_mobile"]);
    $gst_number     = trim($_POST["gst_number"]);
    $shop_license   = trim($_POST["shop_license"]);
    // Validate GST number format if provided
if (!empty($gst_number)) {
    if (!preg_match("/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$/", $gst_number)) {
        $gst_number_err = "Invalid GST number format e.g.11AAAAA0000A1Z1";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM shops WHERE gst_number = '$gst_number'");
        if (mysqli_num_rows($check) > 0) $gst_number_err = "GST number already registered.";
    }
}

// Validate shop license if provided
if (!empty($shop_license)) {
    if (!preg_match("/^[A-Z0-9\/\-]{6,20}$/i", $shop_license)) {
        $shop_license_err = "Invalid license format.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM shops WHERE shop_license = '$shop_license'");
        if (mysqli_num_rows($check) > 0) $shop_license_err = "Shop license already registered.";
    }
}

    $password       = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validation
    if ($shop_name == "") $shop_name_err = "Please enter your shop name.";
    if ($address == "") $address_err = "Please enter address.";
    if ($owner_name == "") $owner_name_err = "Please enter owner name.";

    if (!filter_var($owner_email, FILTER_VALIDATE_EMAIL)) {
        $owner_email_err = "Enter valid email.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM shops WHERE owner_email = '$owner_email'");
        if (mysqli_num_rows($check) > 0) $owner_email_err = "Email already registered.";
    }

    if (!preg_match("/^[0-9]{10}$/", $owner_mobile)) {
        $owner_mobile_err = "Enter valid 10-digit mobile number.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM shops WHERE owner_mobile = '$owner_mobile'");
        if (mysqli_num_rows($check) > 0) $owner_mobile_err = "Mobile number already registered.";
    }

    

    if (strlen($password) < 8 || !preg_match("#[0-9]+#", $password) || !preg_match("#[A-Z]+#", $password) ||
        !preg_match("#[a-z]+#", $password) || !preg_match("/[\W_]/", $password)) {
        $password_err = "Password must be 8+ chars, contain upper, lower, number, special char.";
    }

    if ($confirm_password != $password) $confirm_password_err = "Passwords do not match.";

    // File Upload
    if (isset($_FILES["shop_image"]) && $_FILES["shop_image"]["error"] == 0) {
        $imageFileType = strtolower(pathinfo($_FILES["shop_image"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif"];
        if (!in_array($imageFileType, $allowed)) {
            $shop_image_err = "Only JPG, PNG, GIF allowed.";
        } elseif ($_FILES["shop_image"]["size"] > 5 * 1024 * 1024) {
            $shop_image_err = "Image size max 5MB.";
        } else {
            $new_file = uniqid("shop_") . "." . $imageFileType;
            $final_path = $target_dir . $new_file;
            if (move_uploaded_file($_FILES["shop_image"]["tmp_name"], $final_path)) {
                $shop_image_path = $final_path;
            } else {
                $shop_image_err = "Upload failed.";
            }
        }
    }

    // If no errors, insert
if (!$shop_name_err && !$address_err && !$owner_name_err && !$owner_email_err && !$owner_mobile_err  && !$password_err && !$confirm_password_err && !$shop_image_err && !$gst_number_err && !$shop_license_err)
 {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO shops (shop_name, address, owner_name, owner_email, owner_mobile,gst_number, shop_license, shop_image_path, password_hash)
                VALUES ('$shop_name', '$address', '$owner_name', '$owner_email', '$owner_mobile',  '$gst_number', '$shop_license', '$shop_image_path', '$hash')";
        if (mysqli_query($conn, $sql)) {
            header("location: login.php?registration_success=true");
            exit();
        } else {
            $registration_error = "Error: " . mysqli_error($conn);
        }
    }
    mysqli_close($conn);
}
?>

<?php
// [Keep all your existing PHP code exactly the same until the HTML part]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop Registration - MyShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
    <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
     <link rel="stylesheet" href="../asset/css/tailwind.min.css" />
    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        .form-input {
            transition: all 0.3s;
        }
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .error-shake {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        .bg-primary {
            background-color: #3B82F6; /* Primary color */
        }
        .bg-success {
            background-color: #34D399; /* Success color */
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-primary py-4 px-6">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-store mr-2"></i> Shop Registration - MyShop
            </h1>
        </div>
        
        <div class="p-6 md:p-8">
            <?php if ($registration_error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded error-shake" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p class="font-medium"><?php echo $registration_error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Shop Info Column -->
                    <div class="space-y-5">
                        <div>
                            <label for="shop_name" class="block text-sm font-medium text-gray-700 mb-1">Shop Name *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-store text-gray-400"></i>
                                </div>
                                <input id="shop_name" name="shop_name" type="text" 
                                       class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                       placeholder="Enter shop name" value="<?= htmlspecialchars($shop_name) ?>">
                            </div>
                            <?php if ($shop_name_err): ?>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $shop_name_err ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                                </div>
                                <textarea id="address" name="address" rows="3"
                                          class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                          placeholder="Enter shop address"><?= htmlspecialchars($address) ?></textarea>
                            </div>
                            <?php if ($address_err): ?>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $address_err ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="shop_image" class="block text-sm font-medium text-gray-700 mb-1">Shop Image</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-camera text-gray-400"></i>
                                </div>
                                <input id="shop_image" name="shop_image" type="file" accept="image/*"
                                       class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input bg-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            </div>
                            <?php if ($shop_image_err): ?>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $shop_image_err ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Owner Info Column -->
                    <div class="space-y-5">
                        <div>
                            <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input id="owner_name" name="owner_name" type="text" 
                                       class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                       placeholder="Enter owner name" value="<?= htmlspecialchars($owner_name) ?>">
                            </div>
                            <?php if ($owner_name_err): ?>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $owner_name_err ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="owner_email" class="block text-sm font-medium text-gray-700 mb-1">Owner Email *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input id="owner_email" name="owner_email" type="email" 
                                       class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                       placeholder="Enter email address" value="<?= htmlspecialchars($owner_email) ?>">
                            </div>
                            <?php if ($owner_email_err): ?>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $owner_email_err ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="owner_mobile" class="block text-sm font-medium text-gray-700 mb-1">Owner Mobile *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-mobile-alt text-gray-400"></i>
                                </div>
                                <input id="owner_mobile" name="owner_mobile" type="tel" 
                                       class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                       placeholder="Enter 10-digit number" minlength="10" maxlength="10" value="<?= htmlspecialchars($owner_mobile) ?>">
                            </div>
                            <?php if ($owner_mobile_err): ?>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $owner_mobile_err ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Business Documents -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="gst_number" class="block text-sm font-medium text-gray-700 mb-1">GST Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-file-invoice text-gray-400"></i>
                            </div>
                            <input id="gst_number" name="gst_number" type="text" 
                                   class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                   placeholder="GST number (optional)" value="<?= htmlspecialchars($gst_number) ?>">
                        </div>
                        <?php if ($gst_number_err): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i> <?= $gst_number_err ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="shop_license" class="block text-sm font-medium text-gray-700 mb-1">Shop License</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400"></i>
                            </div>
                            <input id="shop_license" name="shop_license" type="text" 
                                   class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                   placeholder="Shop license (optional)" value="<?= htmlspecialchars($shop_license) ?>">
                        </div>
                        <?php if ($shop_license_err): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i> <?= $shop_license_err ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Passwords -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" 
                                   class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                   placeholder="Create password">
                        </div>
                        <?php if ($password_err): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i> <?= $password_err ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="confirm_password" name="confirm_password" type="password" 
                                   class="pl-10 w-full p-3 border border-gray-300 rounded-lg form-input" 
                                   placeholder="Confirm password">
                        </div>
                        <?php if ($confirm_password_err): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i> <?= $confirm_password_err ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-success text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-300 shadow hover:shadow-md">
                        <i class="fas fa-user-plus mr-2"></i> Register Shop
                    </button>
                </div>

                <p class="text-center text-sm text-gray-600 mt-4">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-primary hover:text-primary-800">Sign in here</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
