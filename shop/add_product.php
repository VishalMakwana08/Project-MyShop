<?php
// include 'check_internet.php';
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$success = $error = "";

// Fetch categories
$cats = [];
$result = mysqli_query($conn, "SELECT id, name FROM categories WHERE shop_id = $shop_id ORDER BY name");
if ($result) {
    $cats = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $error = "❌ Failed to fetch categories: " . mysqli_error($conn);
}

// Initialize Sticky Form Fields for POST and GET
$product_id = $_POST['product_id'] ?? '';
$name = $_POST['name'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? '';
$cost_price = $_POST['cost_price'] ?? '';
$discount_percent = $_POST['discount_percent'] ?? 0;
$stock_quantity = $_POST['stock_quantity'] ?? '';
$unit_measurement = $_POST['unit_measurement'] ?? '';
$image_path = "";
$is_decimal_quantity = $_POST['is_decimal_quantity'] ?? 0;
$low_stock_threshold = $_POST['low_stock_threshold'] ?? '';
$min_qty_for_discount = $_POST['min_qty_for_discount'] ?? 0.00;
$qty_discount_percentage = $_POST['qty_discount_percentage'] ?? 0.00;

// --- IMPORTANT FIX: Handle category creation with AJAX (or a direct post) ---
if (isset($_POST['new_category']) && !empty($_POST['new_category'])) {
    $new_category_name = trim(mysqli_real_escape_string($conn, $_POST['new_category']));
    
    // Check if category already exists for this shop
    $check_category = mysqli_query($conn, "SELECT id FROM categories WHERE shop_id = $shop_id AND name = '$new_category_name'");
    
    if (mysqli_num_rows($check_category) > 0) {
        // Return an error response
        echo json_encode(['status' => 'error', 'message' => "❌ Category '$new_category_name' already exists."]);
    } else {
        // Insert new category
        $insert_category = mysqli_query($conn, "INSERT INTO categories (shop_id, name) VALUES ($shop_id, '$new_category_name')");
        
        if ($insert_category) {
            $new_cat_id = mysqli_insert_id($conn);
            echo json_encode(['status' => 'success', 'message' => "✅ Category added!", 'id' => $new_cat_id, 'name' => htmlspecialchars($new_category_name)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "❌ Failed to add category: " . mysqli_error($conn)]);
        }
    }
    // STOP SCRIPT EXECUTION AFTER HANDLING CATEGORY CREATION
    exit; 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and Validate
    $product_id = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['product_id'])));
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $category_id = intval($_POST['category_id']);
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $price = floatval($_POST['price']);
    $cost_price_val = (isset($_POST['cost_price']) && $_POST['cost_price'] !== '') ? floatval($_POST['cost_price']) : null;
    $discount_percent = floatval($_POST['discount_percent']);
    $stock_quantity = floatval($_POST['stock_quantity']);
    $unit_measurement = trim(mysqli_real_escape_string($conn, $_POST['unit_measurement']));
    $is_decimal_quantity = isset($_POST['is_decimal_quantity']) ? intval($_POST['is_decimal_quantity']) : 0;
    $low_stock_threshold_val = (isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '') ? floatval($_POST['low_stock_threshold']) : null; // Use null for empty value
    $min_qty_for_discount_val = floatval($_POST['min_qty_for_discount']);
    $qty_discount_percentage_val = floatval($_POST['qty_discount_percentage']);

    // --- Validation ---
    if (empty($product_id) || empty($name) || empty($category_id) || $price <= 0 || $stock_quantity < 0 || empty($unit_measurement)) {
        $error = "❌ Please fill all required fields correctly.";
    } elseif ($cost_price_val !== null && $cost_price_val < 0) {
        $error = "❌ Cost Price cannot be negative.";
    } elseif ($discount_percent < 0 || $discount_percent > 100) {
        $error = "❌ Product-wide Discount must be between 0 and 100.";
    } elseif ($min_qty_for_discount_val < 0) {
        $error = "❌ Min Quantity for Discount cannot be negative.";
    } elseif ($qty_discount_percentage_val < 0 || $qty_discount_percentage_val > 100) {
        $error = "❌ Quantity Discount Percentage must be between 0 and 100.";
    } elseif ($low_stock_threshold_val !== null && $low_stock_threshold_val < 0) {
        $error = "❌ Low stock threshold cannot be negative.";
    } elseif ($is_decimal_quantity == 0) {
        if (fmod($stock_quantity, 1) != 0) {
            $error = "❌ Stock quantity must be a whole number for non-decimal quantity products.";
        } elseif ($low_stock_threshold_val !== null && fmod($low_stock_threshold_val, 1) != 0) {
            $error = "❌ Low stock threshold must be a whole number for non-decimal quantity products.";
        } elseif (fmod($min_qty_for_discount_val, 1) != 0) {
            $error = "❌ Min Quantity for Discount must be a whole number for non-decimal quantity products.";
        }
    }
    
    // Check duplicate product_id ONLY if there are no other validation errors.
    if (!$error) {
        $check_sql = "SELECT id FROM products WHERE shop_id = ? AND product_id = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("is", $shop_id, $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $error = "❌ Product ID already exists. Please choose a different one.";
            }
            $check_stmt->close();
        }
    }

    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileError = $_FILES['image']['error'];
        $fileSize = $_FILES['image']['size'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $target_dir = "uploads/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if ($fileError !== UPLOAD_ERR_OK) {
            // ... (your existing error handling code)
            $error = "❌ File upload error. Please check file size and format.";
        } elseif ($fileSize > $maxFileSize) {
            $error = "❌ File exceeds the maximum size of 2MB.";
        } else {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext)) {
                $newFileName = "prod_" . uniqid() . "." . $ext;
                $target_file = $target_dir . $newFileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $error = "❌ Failed to move uploaded file.";
                }
            } else {
                $error = "❌ Invalid image format. Allowed: jpg, jpeg, png, gif.";
            } 
        }
    }

    // Insert product into database
    if (!$error) {
        $insert_sql = "INSERT INTO products
            (shop_id, product_id, name, category_id, description, price, cost_price, discount_percent, 
             stock_quantity, unit_measurement, image_path, is_decimal_quantity, low_stock_threshold,
             min_qty_for_discount, qty_discount_percentage,
             created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        if ($stmt = $conn->prepare($insert_sql)) {
            $stmt->bind_param("issssdddsssdddd",
                $shop_id,
                $product_id,
                $name,
                $category_id,
                $description,
                $price,
                $cost_price_val,
                $discount_percent,
                $stock_quantity,
                $unit_measurement,
                $image_path,
                $is_decimal_quantity,
                $low_stock_threshold_val,
                $min_qty_for_discount_val,
                $qty_discount_percentage_val
            );

            if ($stmt->execute()) {
                $product_db_id = mysqli_insert_id($conn);

                if (isset($_POST['attribute_names']) && isset($_POST['attribute_values'])) {
                    $attr_names = $_POST['attribute_names'];
                    $attr_values = $_POST['attribute_values'];
                    $attr_sql = "INSERT INTO product_attribute_values (product_id, attribute_name, attribute_value) VALUES (?, ?, ?)";
                    if ($attr_stmt = $conn->prepare($attr_sql)) {
                        for ($i = 0; $i < count($attr_names); $i++) {
                            $attr_name = trim($attr_names[$i]);
                            $attr_value = trim($attr_values[$i]);
                            if (!empty($attr_name) && !empty($attr_value)) {
                                $attr_stmt->bind_param("iss", $product_db_id, $attr_name, $attr_value);
                                $attr_stmt->execute();
                            }
                        }
                        $attr_stmt->close();
                    } else {
                        error_log("Failed to prepare attribute insert: " . $conn->error);
                    }
                }

                $success = "✅ Product <strong>" . htmlspecialchars($name) . "</strong> added successfully!";
                // Reset form fields
                $product_id = $name = $description = $price = $stock_quantity = $unit_measurement = '';
                $category_id = '';
                $discount_percent = 0;
                $is_decimal_quantity = 0;
                $low_stock_threshold = '';
                $cost_price = '';
                $min_qty_for_discount = 0.00;
                $qty_discount_percentage = 0.00;
            } else {
                $error = "❌ Failed to insert product: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "❌ Database error preparing statement: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Product - MyShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> -->
      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .validation-error {
            border-color: #f87171 !important;
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.2) !important;
        }
        .validation-success {
            border-color: #4ade80 !important;
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.2) !important;
        }
        .validation-message {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .validation-error-message {
            color: #ef4444;
        }
        .validation-success-message {
            color: #22c55e;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-indigo-600 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-4xl bg-white p-6 sm:p-10 rounded-2xl shadow-2xl space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 border-b pb-4 border-gray-200">
            <h1 class="text-3xl font-extrabold text-blue-700 text-center sm:text-left flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-blue-500"></i> Add New Product
            </h1>
            <div class="flex gap-3">
                <a href="products.php" class="px-5 py-2 bg-gray-600 text-white rounded-lg shadow-md hover:bg-gray-700 transition duration-300 ease-in-out flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Back to Products
                </a>
                <a href="bulk_product.php" class="px-5 py-2 bg-green-600 text-white rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out flex items-center gap-2">
                    <i class="fa-solid fa-layer-group"></i> Add Multiple Products
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div id="success-message" class="bg-green-100 text-green-800 px-5 py-3 rounded-lg flex items-center gap-3 text-lg font-medium animate-pulse">
                <i class="fa-solid fa-check-circle text-green-500"></i>
                <?= $success ?>
            </div>
        <?php elseif ($error): ?>
            <div id="error-message" class="bg-red-100 text-red-700 px-5 py-3 rounded-lg flex items-center gap-3 text-lg font-medium animate-bounce">
                <i class="fa-solid fa-times-circle text-red-500"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6" id="productForm" onsubmit="return validateForm()">
            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="product_id" class="block text-sm font-semibold text-gray-700 mb-1">Product ID <span class="text-red-500">*</span></label>
                    <input type="text" name="product_id" id="product_id" value="<?= htmlspecialchars($product_id) ?>" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., PROD001, Unique ID" onblur="validateProductId()">
                    <div id="product_id_validation" class="validation-message"></div>
                </div>
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., T-Shirt, Laptop">
                </div>
            </div>

            <div>
                <label for="category_id" class="block text-sm font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <select name="category_id" id="category_id" required
                                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm">
                        <option value="">— Select Category —</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (string)$category_id === (string)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="addCategoryBtn" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <div id="newCategorySection" class="mt-2 hidden">
                    <div class="flex gap-2">
                        <input type="text" id="new_category_input"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                               placeholder="Enter new category name">
                        <button type="button" id="saveCategoryBtn" class="px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300">
                            <i class="fa-solid fa-check"></i>
                        </button>
                        <button type="button" id="cancelCategoryBtn" class="px-4 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-300">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div id="category_validation" class="validation-message"></div>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4"
                                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                                 placeholder="A brief description of the product..."><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="grid sm:grid-cols-3 gap-6">
                <div>
                    <label for="price" class="block text-sm font-semibold text-gray-700 mb-1">Price (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="price" step="0.01" min="0.01" value="<?= htmlspecialchars($price) ?>" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 29.99" onblur="validatePrice()">
                    <div id="price_validation" class="validation-message"></div>
                </div>
                <div>
                    <label for="cost_price" class="block text-sm font-semibold text-gray-700 mb-1">
                        Cost Price (₹) <span class="text-gray-400 text-sm">(Optional)</span>
                    </label>
                    <input type="number" name="cost_price" id="cost_price" step="0.01" min="0"
                           value="<?= htmlspecialchars($cost_price) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 15.00" onblur="validateCostPrice()">
                    <div id="cost_price_validation" class="validation-message"></div>
                </div>
                <div>
                    <label for="discount_percent" class="block text-sm font-semibold text-gray-700 mb-1">Product-Wide Discount (%)</label>
                    <input type="number" name="discount_percent" id="discount_percent" step="0.01" min="0" max="100" value="<?= htmlspecialchars($discount_percent) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="0-100" onblur="validateDiscount()">
                    <div id="discount_validation" class="validation-message"></div>
                    <p class="text-xs text-gray-500 mt-1">This is a general product discount, not quantity-based.</p>
                </div>
            </div>
            
            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="stock_quantity" class="block text-sm font-semibold text-gray-700 mb-1">Stock Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_quantity" id="stock_quantity" step="0.01" min="0" value="<?= htmlspecialchars($stock_quantity) ?>" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 100 or 10.5" onblur="validateStock()">
                    <div id="stock_validation" class="validation-message"></div>
                </div>
                <div>
                    <label for="unit_measurement" class="block text-sm font-semibold text-gray-700 mb-1">Unit Measurement <span class="text-red-500">*</span></label>
                    <input type="hidden" name="unit_measurement" id="final_unit_measurement" value="<?= htmlspecialchars($unit_measurement ?? '') ?>">
                    <select id="unit_selector" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm">
                        <option value="">— Select Unit —</option>
                        <option value="Piece" <?= ($unit_measurement == 'Piece') ? 'selected' : '' ?>>Piece</option>
                        <option value="Kg" <?= ($unit_measurement == 'Kg') ? 'selected' : '' ?>>Kg</option>
                        <option value="Gram" <?= ($unit_measurement == 'Gram') ? 'selected' : '' ?>>Gram</option>
                        <option value="Liter" <?= ($unit_measurement == 'Liter') ? 'selected' : '' ?>>Liter</option>
                        <option value="Pack" <?= ($unit_measurement == 'Pack') ? 'selected' : '' ?>>Pack</option>
                        <option value="Meter" <?= ($unit_measurement == 'Meter') ? 'selected' : '' ?>>Meter</option>
                        <option value="Box" <?= ($unit_measurement == 'Box') ? 'selected' : '' ?>>Box</option>
                        <option value="Set" <?= ($unit_measurement == 'Set') ? 'selected' : '' ?>>Set</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="custom_unit_input"
                           class="mt-2 hidden w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="Enter custom unit (alphabets only)" pattern="^[A-Za-z\s]+$"
                           title="Only alphabets allowed" />
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="is_decimal_quantity" class="block text-sm font-medium text-gray-700 mb-1">Allow Decimal Quantity?</label>
                    <select name="is_decimal_quantity" id="is_decimal_quantity" class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200" onchange="updateQuantityInputSteps()">
                        <option value="0" <?= ((string)$is_decimal_quantity === '0') ? 'selected' : '' ?>>No (Only Whole Units like Pieces)</option>
                        <option value="1" <?= ((string)$is_decimal_quantity === '1') ? 'selected' : '' ?>>Yes (Supports Decimal like KG, Litre)</option>
                    </select>
                </div>
                <div>
                    <label for="low_stock_threshold" class="block text-sm font-semibold text-gray-700 mb-1">
                        Low Stock Threshold (Optional)
                    </label>
                    <input type="number" name="low_stock_threshold" id="low_stock_threshold" step="0.01" min="0"
                           value="<?= htmlspecialchars($low_stock_threshold) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 5 or 0.5" onblur="validateLowStock()">
                    <div id="low_stock_validation" class="validation-message"></div>
                    <p class="mt-2 text-sm text-gray-500">
                        Set a quantity below which this product will be flagged as 'low in stock'.
                        If 'Allow Decimal Quantity' is 'No', this must be a whole number.
                    </p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="min_qty_for_discount" class="block text-sm font-semibold text-gray-700 mb-1">
                        Min Quantity for Discount:
                    </label>
                    <input type="number" id="min_qty_for_discount" name="min_qty_for_discount"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           value="<?= htmlspecialchars($min_qty_for_discount) ?>" min="0" step="1"
                           title="Enter min quantity (e.g., 5). Set 0 if no quantity discount." onblur="validateMinQtyDiscount()">
                    <div id="min_qty_discount_validation" class="validation-message"></div>
                    <p class="text-xs text-gray-500 mt-1">Set to 0 if no quantity-based discount applies.</p>
                </div>

                <div>
                    <label for="qty_discount_percentage" class="block text-sm font-semibold text-gray-700 mb-1">
                        Quantity Discount Percentage (%):
                    </label>
                    <input type="number" id="qty_discount_percentage" name="qty_discount_percentage"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           value="<?= htmlspecialchars($qty_discount_percentage) ?>" min="0" max="100" step="0.01"
                           placeholder="e.g., 10 for 10%" onblur="validateQtyDiscount()">
                    <div id="qty_discount_validation" class="validation-message"></div>
                    <p class="text-xs text-gray-500 mt-1">e.g., 10 for 10%. Applies if min quantity is met. Set to 0 if no discount.</p>
                </div>
            </div>
            
            <div>
                <label for="image" class="block text-sm font-semibold text-gray-700 mb-1">Product Image (Optional)</label>
                <input type="file" name="image" id="image" accept="image/png, image/jpeg, image/gif"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition duration-200 cursor-pointer shadow-sm">
                <p class="mt-2 text-sm text-gray-500">Max file size 2MB. Allowed formats: JPG, PNG, GIF.</p>
            </div>
            
            <div id="attributes-section" class="space-y-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Attributes (Optional)</label>
                <div id="attribute-list" class="space-y-2"></div>
                <button type="button" onclick="addAttributeRow()"
                    class="mt-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300 ease-in-out flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle mr-1"></i> Add Attribute  
                </button>
            </div>

            <div class="pt-4 border-t border-gray-200 text-center">
                <button type="submit" id="submitBtn"
                    class="px-8 py-3 bg-blue-700 text-white font-bold rounded-lg shadow-lg hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300 ease-in-out transform hover:scale-105">
                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Add Product
                </button>
            </div>
        </form>
    </div>

<script>
    function addAttributeRow() {
        const container = document.getElementById('attribute-list');
        const row = document.createElement('div');
        row.className = 'grid sm:grid-cols-3 gap-4 items-center';
        row.innerHTML = `
            <input type="text" name="attribute_names[]" placeholder="e.g., Color" class="border border-gray-300 rounded-lg px-4 py-2.5 w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200">
            <input type="text" name="attribute_values[]" placeholder="e.g., Red, Blue" class="border border-gray-300 rounded-lg px-4 py-2.5 w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200">
            <button type="button" onclick="this.closest('.grid').remove()" class="col-span-1 text-red-600 hover:text-red-800 font-semibold text-sm flex items-center justify-center gap-1">
                <i class="fa-solid fa-trash-can"></i> Remove
            </button>
        `;
        container.appendChild(row);
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selector = document.getElementById('unit_selector');
        const customInput = document.getElementById('custom_unit_input');
        const finalInput = document.getElementById('final_unit_measurement');
        const categorySelect = document.getElementById('category_id');
        const addCategoryBtn = document.getElementById('addCategoryBtn');
        const newCategorySection = document.getElementById('newCategorySection');
        const saveCategoryBtn = document.getElementById('saveCategoryBtn');
        const cancelCategoryBtn = document.getElementById('cancelCategoryBtn');
        const newCategoryInput = document.getElementById('new_category_input');

        // Function to handle unit selector and custom input
        function updateFinalUnit() {
            if (selector.value === 'Other') {
                customInput.classList.remove('hidden');
                customInput.required = true;
                finalInput.value = customInput.value;
            } else {
                customInput.classList.add('hidden');
                customInput.required = false;
                customInput.value = '';
                finalInput.value = selector.value;
            }
        }

        // Set the correct selected option for unit measurement on page load
        if (finalInput.value) {
            let unitExists = false;
            for (let i = 0; i < selector.options.length; i++) {
                if (selector.options[i].value === finalInput.value) {
                    selector.value = finalInput.value;
                    unitExists = true;
                    break;
                }
            }
            if (!unitExists) {
                selector.value = 'Other';
                customInput.classList.remove('hidden');
                customInput.value = finalInput.value;
            }
        }
        updateFinalUnit();

        selector.addEventListener('change', updateFinalUnit);
        customInput.addEventListener('input', () => {
            finalInput.value = customInput.value;
        });

        // Category management
        addCategoryBtn.addEventListener('click', () => {
            newCategorySection.classList.remove('hidden');
            addCategoryBtn.classList.add('hidden');
        });
        
        cancelCategoryBtn.addEventListener('click', () => {
            newCategorySection.classList.add('hidden');
            addCategoryBtn.classList.remove('hidden');
            newCategoryInput.value = '';
            showCategoryValidation('', 'clear');
        });
        
        // --- FIX: Use AJAX to add new category without full form submission ---
        saveCategoryBtn.addEventListener('click', async () => {
            const categoryName = newCategoryInput.value.trim();
            if (!categoryName) {
                showCategoryValidation('Please enter a category name', 'error');
                return;
            }

            // Check if category already exists locally
            const options = categorySelect.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].text.toLowerCase() === categoryName.toLowerCase()) {
                    showCategoryValidation('This category already exists', 'error');
                    return;
                }
            }

            const formData = new FormData();
            formData.append('new_category', categoryName);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    const newOption = new Option(result.name, result.id, true, true);
                    categorySelect.add(newOption);
                    newCategorySection.classList.add('hidden');
                    addCategoryBtn.classList.remove('hidden');
                    newCategoryInput.value = '';
                    showCategoryValidation('Category added successfully!', 'success');
                    // Reset sticky form message
                    document.getElementById('success-message')?.remove();
                    document.getElementById('error-message')?.remove();
                } else {
                    showCategoryValidation(result.message, 'error');
                }
            } catch (e) {
                showCategoryValidation('Failed to add category. Please try again.', 'error');
            }
        });
    });

    // --- FIX: Add a global flag to wait for AJAX validation ---
    let isProductIdValidating = false;

    function validateProductId() {
        const productIdInput = document.getElementById('product_id');
        const productId = productIdInput.value.trim();
        const validationDiv = document.getElementById('product_id_validation');
        isProductIdValidating = true;

        if (!productId) {
            validationDiv.textContent = '';
            productIdInput.classList.remove('validation-error', 'validation-success');
            isProductIdValidating = false;
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_product_ids.php?product_id=' + encodeURIComponent(productId), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.exists) {
                        productIdInput.classList.add('validation-error');
                        productIdInput.classList.remove('validation-success');
                        validationDiv.textContent = '❌ This Product ID is already in use';
                        validationDiv.className = 'validation-message validation-error-message';
                    } else {
                        productIdInput.classList.add('validation-success');
                        productIdInput.classList.remove('validation-error');
                        validationDiv.textContent = '✅ Product ID is available';
                        validationDiv.className = 'validation-message validation-success-message';
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            }
            isProductIdValidating = false;
        };
        xhr.onerror = function() {
            console.error('Request failed');
            isProductIdValidating = false;
        };
        xhr.send();
    }
    
    // Remaining validation functions
    function validatePrice() {
        const priceInput = document.getElementById('price');
        const price = parseFloat(priceInput.value);
        const validationDiv = document.getElementById('price_validation');
        const isValid = !isNaN(price) && price > 0;
        updateValidationStyle(priceInput, validationDiv, isValid, 'Price must be a positive number');
        return isValid;
    }
    
    function validateCostPrice() {
        const costPriceInput = document.getElementById('cost_price');
        const costPrice = costPriceInput.value.trim();
        const validationDiv = document.getElementById('cost_price_validation');
        if (costPrice === '') {
            updateValidationStyle(costPriceInput, validationDiv, true, '');
            return true;
        }
        const costPriceVal = parseFloat(costPrice);
        const isValid = !isNaN(costPriceVal) && costPriceVal >= 0;
        updateValidationStyle(costPriceInput, validationDiv, isValid, 'Cost price cannot be negative');
        return isValid;
    }
    
    function validateDiscount() {
        const discountInput = document.getElementById('discount_percent');
        const discount = parseFloat(discountInput.value);
        const validationDiv = document.getElementById('discount_validation');
        const isValid = !isNaN(discount) && discount >= 0 && discount <= 100;
        updateValidationStyle(discountInput, validationDiv, isValid, 'Discount must be between 0 and 100');
        return isValid;
    }
    
    function validateStock() {
        const stockInput = document.getElementById('stock_quantity');
        const stock = parseFloat(stockInput.value);
        const validationDiv = document.getElementById('stock_validation');
        const isDecimalAllowed = document.getElementById('is_decimal_quantity').value === '1';
        let isValid = !isNaN(stock) && stock >= 0;
        let message = 'Stock quantity cannot be negative';

        if (isValid && !isDecimalAllowed && stock % 1 !== 0) {
            isValid = false;
            message = 'Stock quantity must be a whole number';
        }
        updateValidationStyle(stockInput, validationDiv, isValid, message);
        return isValid;
    }
    
    function validateLowStock() {
        const lowStockInput = document.getElementById('low_stock_threshold');
        const lowStock = lowStockInput.value.trim();
        const validationDiv = document.getElementById('low_stock_validation');
        const isDecimalAllowed = document.getElementById('is_decimal_quantity').value === '1';
        const stock = parseFloat(document.getElementById('stock_quantity').value);
        let isValid = true;
        let message = '';

        if (lowStock === '') {
            updateValidationStyle(lowStockInput, validationDiv, true, '');
            return true;
        }
        
        const lowStockVal = parseFloat(lowStock);
        if (isNaN(lowStockVal) || lowStockVal < 0) {
            isValid = false;
            message = 'Low stock threshold cannot be negative';
        } else if (!isDecimalAllowed && lowStockVal % 1 !== 0) {
            isValid = false;
            message = 'Low stock threshold must be a whole number';
        } else if (!isNaN(stock) && lowStockVal > stock) {
            isValid = false;
            message = 'Low stock threshold cannot be greater than the stock';
        }
        updateValidationStyle(lowStockInput, validationDiv, isValid, message);
        return isValid;
    }
    
    function validateMinQtyDiscount() {
        const minQtyInput = document.getElementById('min_qty_for_discount');
        const minQty = minQtyInput.value.trim();
        const validationDiv = document.getElementById('min_qty_discount_validation');
        const isDecimalAllowed = document.getElementById('is_decimal_quantity').value === '1';
        let isValid = true;
        let message = '';

        if (minQty === '' || minQty === '0') {
            updateValidationStyle(minQtyInput, validationDiv, true, '');
            return true;
        }
        
        const minQtyVal = parseFloat(minQty);
        if (isNaN(minQtyVal) || minQtyVal < 0) {
            isValid = false;
            message = 'Min quantity cannot be negative';
        } else if (!isDecimalAllowed && minQtyVal % 1 !== 0) {
            isValid = false;
            message = 'Min quantity must be a whole number';
        }
        updateValidationStyle(minQtyInput, validationDiv, isValid, message);
        return isValid;
    }
    
    function validateQtyDiscount() {
        const qtyDiscountInput = document.getElementById('qty_discount_percentage');
        const qtyDiscount = qtyDiscountInput.value.trim();
        const validationDiv = document.getElementById('qty_discount_validation');
        let isValid = true;
        let message = '';
        
        if (qtyDiscount === '' || qtyDiscount === '0') {
            updateValidationStyle(qtyDiscountInput, validationDiv, true, '');
            return true;
        }

        const qtyDiscountVal = parseFloat(qtyDiscount);
        if (isNaN(qtyDiscountVal) || qtyDiscountVal < 0 || qtyDiscountVal > 100) {
            isValid = false;
            message = 'Quantity discount must be between 0 and 100';
        }
        updateValidationStyle(qtyDiscountInput, validationDiv, isValid, message);
        return isValid;
    }
    
    function showCategoryValidation(message, type) {
        const validationDiv = document.getElementById('category_validation');
        const newCategoryInput = document.getElementById('new_category_input');
        
        validationDiv.textContent = message;
        newCategoryInput.classList.remove('validation-error', 'validation-success');
        if (type === 'error') {
            validationDiv.className = 'validation-message validation-error-message';
            newCategoryInput.classList.add('validation-error');
        } else if (type === 'success') {
            validationDiv.className = 'validation-message validation-success-message';
            newCategoryInput.classList.add('validation-success');
        } else {
             validationDiv.textContent = '';
        }
    }
    
    // Helper function to update validation styles
    function updateValidationStyle(inputElement, validationDiv, isValid, errorMessage) {
        if (isValid) {
            inputElement.classList.add('validation-success');
            inputElement.classList.remove('validation-error');
            validationDiv.textContent = ''; // Clear message for valid state
        } else {
            inputElement.classList.add('validation-error');
            inputElement.classList.remove('validation-success');
            validationDiv.textContent = `❌ ${errorMessage}`;
            validationDiv.className = 'validation-message validation-error-message';
        }
    }

    function updateQuantityInputSteps() {
        const isDecimalAllowed = document.getElementById('is_decimal_quantity').value === '1';
        const stepValue = isDecimalAllowed ? '0.01' : '1';
        
        document.getElementById('stock_quantity').step = stepValue;
        document.getElementById('low_stock_threshold').step = stepValue;
        document.getElementById('min_qty_for_discount').step = stepValue;
        
        validateStock();
        validateLowStock();
        validateMinQtyDiscount();
    }
    
    function validateForm() {
        // Wait for AJAX validation to finish before submitting
        if (isProductIdValidating) {
            setTimeout(validateForm, 100);
            return false;
        }

        let isValid = true;
        
        // Run all validation functions
        if (!validatePrice()) isValid = false;
        if (!validateCostPrice()) isValid = false;
        if (!validateDiscount()) isValid = false;
        if (!validateStock()) isValid = false;
        if (!validateLowStock()) isValid = false;
        if (!validateMinQtyDiscount()) isValid = false;
        if (!validateQtyDiscount()) isValid = false;
        
        // Final check on required fields
        if (document.getElementById('product_id').value.trim() === '') {
            updateValidationStyle(document.getElementById('product_id'), document.getElementById('product_id_validation'), false, 'Product ID is required.');
            isValid = false;
        } else if (document.getElementById('product_id').classList.contains('validation-error')) {
            isValid = false;
        }

        const categorySelect = document.getElementById('category_id');
        if (categorySelect.value === '') {
            categorySelect.classList.add('validation-error');
            isValid = false;
        } else {
            categorySelect.classList.remove('validation-error');
        }
        
        const unitSelector = document.getElementById('unit_selector');
        const customUnitInput = document.getElementById('custom_unit_input');
        if (unitSelector.value === '') {
            unitSelector.classList.add('validation-error');
            isValid = false;
        } else {
            unitSelector.classList.remove('validation-error');
            if (unitSelector.value === 'Other' && customUnitInput.value.trim() === '') {
                customUnitInput.classList.add('validation-error');
                isValid = false;
            } else {
                customUnitInput.classList.remove('validation-error');
            }
        }
        
        if (isValid) {
            // Re-bind to ensure final value is set
            const finalUnitInput = document.getElementById('final_unit_measurement');
            if (unitSelector.value === 'Other') {
                finalUnitInput.value = customUnitInput.value.trim();
            } else {
                finalUnitInput.value = unitSelector.value;
            }
        }

        if (!isValid) {
            // Prevent form submission if there are validation errors
            alert("Please fix the validation errors before submitting the form.");
        }
        
        return isValid;
    }
</script>
</body>
</html>