<?php
include 'check_internet.php';
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header("Location: login.php");
    exit;
}

$shop_id = $_SESSION['shop_id'];
$product = null;
$success = $error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID provided. Please go back to products page.");
}
$product_id = intval($_GET['id']);

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $product_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Product not found or you do not have permission to edit it.");
}
$product = $result->fetch_assoc();
$stmt->close();

// Initialize low_stock_threshold, min_qty_for_discount, and qty_discount_percentage for display if not set or null (e.g., old products or empty from DB)
if (!isset($product['low_stock_threshold']) || $product['low_stock_threshold'] === null) {
    $product['low_stock_threshold'] = 0;
}
// Initialize NEW quantity-based discount fields
$product['min_qty_for_discount'] = $product['min_qty_for_discount'] ?? 0.00;
$product['qty_discount_percentage'] = $product['qty_discount_percentage'] ?? 0.00;


// Fetch categories
$categories = [];
$res = $conn->prepare("SELECT id, name FROM categories WHERE shop_id = ?");
$res->bind_param("i", $shop_id);
$res->execute();
$result = $res->get_result();
while ($row = $result->fetch_assoc()) $categories[] = $row;
$res->close();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $cost_price = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? floatval($_POST['cost_price']) : null; // Handle null

    $stock_quantity = floatval($_POST['stock_quantity']);
    $category_id = intval($_POST['category_id']);
    $discount_percent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0;
    // $unit_measurement = trim($_POST['unit_measurement']);
    $unit_measurement = ($_POST['unit_measurement'] === 'Other') 
    ? trim($_POST['custom_unit']) 
    : $_POST['unit_measurement'];

    $is_decimal_quantity = isset($_POST['is_decimal_quantity']) ? intval($_POST['is_decimal_quantity']) : 0;
    // Get the low stock threshold, treat empty string as 0 for validation purposes
    $low_stock_threshold = isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '' ? floatval($_POST['low_stock_threshold']) : 0;

    // NEW Quantity-based discount fields
    $min_qty_for_discount = floatval($_POST['min_qty_for_discount'] ?? 0);
    $qty_discount_percentage = floatval($_POST['qty_discount_percentage'] ?? 0);

    // --- Validation Logic ---
    if (empty($name) || empty($category_id) || empty($unit_measurement)) {
        $error = "❌ Product Name, Category, and Unit Measurement are required.";
    } elseif ($price <= 0) {
        $error = "❌ Price must be a positive number.";
    } elseif ($stock_quantity < 0) {
        $error = "❌ Stock quantity cannot be negative.";
    } elseif ($discount_percent < 0 || $discount_percent > 100) {
        $error = "❌ Product-Wide Discount must be between 0 and 100.";
    }
    // New/Improved Low Stock Threshold and Stock Quantity Validation
    elseif ($low_stock_threshold < 0) {
        $error = "❌ Low stock threshold cannot be negative.";
    }
    // NEW Quantity Discount Validation
    elseif ($min_qty_for_discount < 0) {
        $error = "❌ Min Quantity for Discount cannot be negative.";
    } elseif ($qty_discount_percentage < 0 || $qty_discount_percentage > 100) {
        $error = "❌ Quantity Discount Percentage must be between 0 and 100.";
    }
    // Decimal Quantity Validation for Stock, Low Stock, and Min Qty for Discount
    elseif ($is_decimal_quantity == 0) { // If decimal quantity is NOT allowed
        if (fmod($stock_quantity, 1) != 0) {
            $error = "❌ Stock quantity must be a whole number for non-decimal quantity products.";
        } elseif (fmod($low_stock_threshold, 1) != 0) {
            $error = "❌ Low stock threshold must be a whole number for non-decimal quantity products.";
        } elseif (fmod($min_qty_for_discount, 1) != 0) { // NEW validation for min_qty_for_discount
            $error = "❌ Min Quantity for Discount must be a whole number for non-decimal quantity products.";
        }
    }
    elseif ($low_stock_threshold > $stock_quantity) {
        $error = "❌ Low stock threshold cannot be greater than the current stock quantity.";
    }
    // --- End Validation Logic ---


    $image_path = $product['image_path']; // Keep existing path by default
    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        // Max file size 2MB
        $max_file_size = 2 * 1024 * 1024; // 2 MB in bytes
        if ($_FILES['image']['size'] > $max_file_size) {
            $error = "❌ Image file size exceeds 2MB limit.";
        } elseif (!in_array($ext, $allowed_ext)) {
            $error = "❌ Invalid image format. Only JPG, PNG, GIF are allowed.";
        } else {
            $newFileName = "prod_" . uniqid() . "." . $ext;
            $target_file = $target_dir . $newFileName;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image if it exists and is different from the new one
                if (!empty($image_path) && file_exists($image_path) && $image_path != $target_file) {
                    unlink($image_path);
                }
                $image_path = $target_file;
            } else {
                $error = "❌ Failed to upload image.";
            }
        }
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "❌ Image upload error: " . $_FILES['image']['error'];
    }


    if (!$error) {
        // Prepare the UPDATE statement including the new fields
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, cost_price=?, stock_quantity=?, image_path=?, category_id=?, discount_percent=?, unit_measurement=?, is_decimal_quantity=?, low_stock_threshold=?, min_qty_for_discount=?, qty_discount_percentage=?, updated_at=NOW() WHERE id=? AND shop_id=?");
        
        // Bind parameters - remember to add 'd' for the new float fields
        $stmt->bind_param(
            "ssddssidsddddii", // s(name), s(desc), d(price), d(cost_price), d(stock), s(img), i(cat_id), d(disc_perc), s(unit), i(is_dec), d(low_stock), d(min_qty_disc), d(qty_disc_perc), i(prod_id), i(shop_id)
            $name,
            $description,
            $price,
            $cost_price, // Will be NULL if original input was empty string
            $stock_quantity,
            $image_path,
            $category_id,
            $discount_percent,
            $unit_measurement,
            $is_decimal_quantity,
            $low_stock_threshold,
            $min_qty_for_discount, // NEW
            $qty_discount_percentage, // NEW
            $product_id,
            $shop_id
        );

        if ($stmt->execute()) {
            $success = "✅ Product updated successfully!";
            // Update the $product array to reflect the new values, maintaining sticky form behavior
            $product['name'] = $name;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['cost_price'] = $cost_price;
            $product['stock_quantity'] = $stock_quantity;
            $product['category_id'] = $category_id;
            $product['discount_percent'] = $discount_percent;
            $product['image_path'] = $image_path;
            $product['unit_measurement'] = $unit_measurement;
            $product['is_decimal_quantity'] = $is_decimal_quantity;
            $product['low_stock_threshold'] = $low_stock_threshold;
            $product['min_qty_for_discount'] = $min_qty_for_discount; // NEW
            $product['qty_discount_percentage'] = $qty_discount_percentage; // NEW


            // Update product attributes
            $conn->query("DELETE FROM product_attribute_values WHERE product_id = $product_id");
            if (isset($_POST['attribute_names']) && isset($_POST['attribute_values'])) {
                $names = $_POST['attribute_names'];
                $values = $_POST['attribute_values'];
                for ($i = 0; $i < count($names); $i++) {
                    $attr_name = trim($names[$i]);
                    $attr_value = trim($values[$i]);
                    if ($attr_name && $attr_value) {
                        $stmt_attr = $conn->prepare("INSERT INTO product_attribute_values (product_id, attribute_name, attribute_value) VALUES (?, ?, ?)");
                        $stmt_attr->bind_param("iss", $product_id, $attr_name, $attr_value);
                        $stmt_attr->execute();
                        $stmt_attr->close();
                    }
                }
            }
        } else {
            $error = "❌ Database error: " . $conn->error;
        }
    }
}

// Fetch existing attributes again for sticky form after a POST, or initial load
$attributes = [];
$stmt_attr_fetch = $conn->prepare("SELECT attribute_name, attribute_value FROM product_attribute_values WHERE product_id = ?");
$stmt_attr_fetch->bind_param("i", $product_id);
$stmt_attr_fetch->execute();
$res_attr = $stmt_attr_fetch->get_result();
while ($row_attr = $res_attr->fetch_assoc()) $attributes[] = $row_attr;
$stmt_attr_fetch->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Product - MyShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Optional: Custom scrollbar for better aesthetics if content overflows */
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
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-indigo-600 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-4xl bg-white p-6 sm:p-10 rounded-2xl shadow-2xl space-y-6">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 border-b pb-4 border-gray-200">
            <h1 class="text-3xl font-extrabold text-blue-700 text-center sm:text-left flex items-center gap-2">
                <i class="fa-solid fa-edit text-blue-500"></i> Edit Product
            </h1>
            <a href="products.php" class="mt-4 sm:mt-0 px-5 py-2 bg-gray-600 text-white rounded-lg shadow-md hover:bg-gray-700 transition duration-300 ease-in-out flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Products
            </a>
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

        <form method="POST" enctype="multipart/form-data" class="space-y-6">

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="product_name" class="block text-sm font-semibold text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="product_name" required value="<?= htmlspecialchars($product['name']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., T-Shirt, Laptop">
                </div>

                <div>
                    <label for="price" class="block text-sm font-semibold text-gray-700 mb-1">Price (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="price" step="0.01" min="0.01" required value="<?= htmlspecialchars($product['price']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 29.99">
                </div>
            </div>
            
            <div>
                <label for="cost_price" class="block text-sm font-semibold text-gray-700 mb-1">Cost Price (₹) <span class="text-gray-400 text-sm">(Optional)</span></label>
                <input type="number" name="cost_price" id="cost_price" step="0.01" min="0" value="<?= htmlspecialchars($product['cost_price'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 18.00">
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="stock_quantity" class="block text-sm font-semibold text-gray-700 mb-1">Stock Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_quantity" id="stock_quantity" step="0.01" min="0" required value="<?= htmlspecialchars($product['stock_quantity']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="e.g., 100 or 10.5">
                </div>
                <!-- <div>
                    <label for="unit_measurement" class="block text-sm font-semibold text-gray-700 mb-1">Unit Measurement <span class="text-red-500">*</span></label>
                    <select name="unit_measurement" id="unit_measurement" required
                                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm">
                        <option value="">— Select Unit —</option>
                        <option value="Piece" <?= ($product['unit_measurement'] == 'Piece') ? 'selected' : '' ?>>Piece</option>
                        <option value="Kg" <?= ($product['unit_measurement'] == 'Kg') ? 'selected' : '' ?>>Kg</option>
                        <option value="Gram" <?= ($product['unit_measurement'] == 'Gram') ? 'selected' : '' ?>>Gram</option>
                        <option value="Liter" <?= ($product['unit_measurement'] == 'Liter') ? 'selected' : '' ?>>Liter</option>
                        <option value="Pack" <?= ($product['unit_measurement'] == 'Pack') ? 'selected' : '' ?>>Pack</option>
                        <option value="Meter" <?= ($product['unit_measurement'] == 'Meter') ? 'selected' : '' ?>>Meter</option>
                        <option value="Box" <?= ($product['unit_measurement'] == 'Box') ? 'selected' : '' ?>>Box</option>
                        <option value="Set" <?= ($product['unit_measurement'] == 'Set') ? 'selected' : '' ?>>Set</option>
                    </select>
                </div> -->
                <?php
$units = ["Piece", "Kg", "Gram", "Liter", "Pack", "Meter", "Box", "Set"];
$currentUnit = $product['unit_measurement'];
$isCustomUnit = !in_array($currentUnit, $units);
?>
<div>
    <label for="unit_measurement" class="block text-sm font-semibold text-gray-700 mb-1">
        Unit Measurement <span class="text-red-500">*</span>
    </label>

    <select name="unit_measurement" id="unit_measurement"
        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
        required>
        <option value="">— Select Unit —</option>
        <?php foreach ($units as $unit): ?>
            <option value="<?= $unit ?>" <?= ($currentUnit === $unit) ? 'selected' : '' ?>><?= $unit ?></option>
        <?php endforeach; ?>
        <option value="Other" <?= $isCustomUnit ? 'selected' : '' ?>>Other</option>
    </select>

    <input type="text" name="custom_unit" id="custom_unit"
        placeholder="Enter custom unit (A-Z only)"
        value="<?= $isCustomUnit ? htmlspecialchars($currentUnit) : '' ?>"
        class="mt-2 w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm <?= $isCustomUnit ? '' : 'hidden' ?>"
        pattern="[A-Za-z\s]+">
</div>


            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="is_decimal_quantity" class="block text-sm font-medium text-gray-700 mb-1">Allow Decimal Quantity?</label>
                    <select name="is_decimal_quantity" id="is_decimal_quantity" class="w-full border-gray-300 rounded-md shadow-sm px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200">
                        <option value="0" <?= $product['is_decimal_quantity'] == 0 ? 'selected' : '' ?>>No (Only Whole Units like Pieces)</option>
                        <option value="1" <?= $product['is_decimal_quantity'] == 1 ? 'selected' : '' ?>>Yes (Supports Decimal like KG, Litre)</option>
                    </select>
                </div>

                <div>
                    <label for="discount_percent" class="block text-sm font-semibold text-gray-700 mb-1">Product-Wide Discount (%)</label>
                    <input type="number" name="discount_percent" id="discount_percent" step="0.01" min="0" max="100" value="<?= htmlspecialchars($product['discount_percent']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           placeholder="0-100">
                     <p class="text-xs text-gray-500 mt-1">This is a general product discount, not quantity-based.</p>
                </div>
            </div>

            <div>
                <label for="low_stock_threshold" class="block text-sm font-semibold text-gray-700 mb-1">
                    Low Stock Threshold (Optional)
                </label>
                <input type="number" name="low_stock_threshold" id="low_stock_threshold" step="0.01" min="0"
                       value="<?= htmlspecialchars($product['low_stock_threshold']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                       placeholder="e.g., 5 or 0.5">
                <p class="mt-2 text-sm text-gray-500">
                    Set a quantity below which this product will be flagged as 'low in stock'.
                    If 'Allow Decimal Quantity' is 'No', this must be a whole number.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label for="min_qty_for_discount" class="block text-sm font-semibold text-gray-700 mb-1">
                        Min Quantity for Discount:
                    </label>
                    <input type="number" id="min_qty_for_discount" name="min_qty_for_discount"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           value="<?= htmlspecialchars($product['min_qty_for_discount']) ?>" min="0" step="any"
                           title="Enter min quantity (e.g., 5 or 5.5). Set 0 if no quantity discount.">
                    <p class="text-xs text-gray-500 mt-1">Set to 0 if no quantity-based discount applies.</p>
                </div>

                <div>
                    <label for="qty_discount_percentage" class="block text-sm font-semibold text-gray-700 mb-1">
                        Quantity Discount Percentage (%):
                    </label>
                    <input type="number" id="qty_discount_percentage" name="qty_discount_percentage"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                           value="<?= htmlspecialchars($product['qty_discount_percentage']) ?>" min="0" max="100" step="0.01"
                           placeholder="e.g., 10 for 10%">
                    <p class="text-xs text-gray-500 mt-1">e.g., 10 for 10%. Applies if min quantity is met. Set to 0 if no discount.</p>
                </div>
            </div>
            <div>
                <label for="category_id" class="block text-sm font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <select name="category_id" id="category_id" required
                                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4"
                                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition duration-200 shadow-sm"
                                 placeholder="A brief description of the product..."><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            
            <div class="space-y-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Product Attributes (Optional)</label>
                <div id="attribute-container" class="space-y-4">
                    <?php if (!empty($attributes)): ?>
                        <?php foreach ($attributes as $index => $attr): ?>
                            <div class="grid grid-cols-[1fr_1fr_auto] gap-4 items-center attribute-row">
                                <input type="text" name="attribute_names[]" placeholder="Attribute Name (e.g., Color)"
                                             value="<?= htmlspecialchars($attr['attribute_name']) ?>"
                                             class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 shadow-sm" />
                                <input type="text" name="attribute_values[]" placeholder="Attribute Value (e.g., Red)"
                                             value="<?= htmlspecialchars($attr['attribute_value']) ?>"
                                             class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 shadow-sm" />
                                <button type="button" onclick="removeAttributeField(this)"
                                             class="p-2 text-red-500 hover:text-red-700 transition duration-200">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="grid grid-cols-[1fr_1fr_auto] gap-4 items-center attribute-row">
                            <input type="text" name="attribute_names[]" placeholder="Attribute Name (e.g., Size)"
                                   class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 shadow-sm" />
                            <input type="text" name="attribute_values[]" placeholder="Attribute Value (e.g., M)"
                                   class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 shadow-sm" />
                            <button type="button" onclick="removeAttributeField(this)"
                                    class="p-2 text-red-500 hover:text-red-700 transition duration-200">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addAttributeField()" class="text-blue-600 hover:underline text-sm font-medium mt-2">
                    <i class="fa-solid fa-plus-circle mr-1"></i> Add Another Attribute
                </button>
            </div>


            <div class="col-span-2">
                <label for="image_upload" class="block text-sm font-semibold text-gray-700 mb-2">Product Image</label>
                <?php if (!empty($product['image_path']) && file_exists($product['image_path'])): ?>
                    <div class="mb-4 flex items-center gap-4 p-3 border border-gray-200 rounded-lg bg-gray-50">
                        <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="Current Product Image" class="h-24 w-24 object-cover rounded-md shadow-sm">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Current Image</p>
                            <span class="text-xs text-gray-400"><?= htmlspecialchars(basename($product['image_path'])) ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-4 p-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm italic">
                        No image currently uploaded for this product.
                    </div>
                <?php endif; ?>

                <input type="file" name="image" id="image_upload" accept="image/png, image/jpeg, image/gif"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition duration-200 cursor-pointer shadow-sm">
                <p class="mt-2 text-sm text-gray-500">Upload a new image to replace the current one. Max file size 2MB. Allowed formats: JPG, PNG, GIF.</p>
            </div>

            <div class="pt-4 border-t border-gray-200 text-center">
                <button type="submit"
                                 class="px-8 py-3 bg-blue-700 text-white font-bold rounded-lg shadow-lg hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300 ease-in-out transform hover:scale-105">
                    <i class="fa-solid fa-save mr-2"></i> Update Product
                </button>
            </div>
        </form>
    </div>
<script>
    function addAttributeField() {
        const container = document.getElementById('attribute-container');
        const row = document.createElement('div');
        row.className = "grid grid-cols-[1fr_1fr_auto] gap-4 items-center attribute-row"; // Ensure consistency with fetched rows

        row.innerHTML = `
            <input type="text" name="attribute_names[]" placeholder="Attribute Name (e.g., Color)"
                                   class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 shadow-sm" />
            <input type="text" name="attribute_values[]" placeholder="Attribute Value (e.g., Red)"
                                   class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 shadow-sm" />
            <button type="button" onclick="removeAttributeField(this)"
                                   class="p-2 text-red-500 hover:text-red-700 transition duration-200">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function removeAttributeField(button) {
        button.closest('.attribute-row').remove();
    }
</script>

    <script>
        // Optional: Fade out success/error messages after a few seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            if (successMessage) {
                setTimeout(() => {
                    successMessage.classList.add('opacity-0', 'transition', 'duration-500', 'ease-out');
                    setTimeout(() => successMessage.remove(), 500); // Remove after transition
                }, 5000); // 5 seconds
            }

            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.classList.add('opacity-0', 'transition', 'duration-500', 'ease-out');
                    setTimeout(() => errorMessage.remove(), 500); // Remove after transition
                }, 7000); // 7 seconds (give a bit more time for errors)
            }

            // --- Dynamic step for quantity inputs based on is_decimal_quantity ---
            const isDecimalQuantitySelect = document.getElementById('is_decimal_quantity');
            const stockQuantityInput = document.getElementById('stock_quantity');
            const lowStockThresholdInput = document.getElementById('low_stock_threshold');
            const minQtyForDiscountInput = document.getElementById('min_qty_for_discount'); // NEW: Get min_qty_for_discount input
            function updateQuantityInputSteps() {
                if (isDecimalQuantitySelect.value === '1') { // 'Yes' for decimal quantity
                    stockQuantityInput.step = '0.01';
                    stockQuantityInput.placeholder = 'e.g., 100.5';
                    lowStockThresholdInput.step = '0.01';
                    lowStockThresholdInput.placeholder = 'e.g., 5.5';
                    minQtyForDiscountInput.step = '0.01'; // NEW: Set step to allow decimals
                    minQtyForDiscountInput.placeholder = 'e.g., 5.5 for 5 and a half units'; // NEW: Update placeholder
                } else { // 'No' for whole quantity
                    stockQuantityInput.step = '1';
                    stockQuantityInput.placeholder = 'e.g., 100';
                    lowStockThresholdInput.step = '1';
                    lowStockThresholdInput.placeholder = 'e.g., 5';
                    minQtyForDiscountInput.step = '1'; // NEW: Set step to whole numbers
                    minQtyForDiscountInput.placeholder = 'e.g., 5 for 5 pieces'; // NEW: Update placeholder
                    }  }
            // Call on page load to set initial state based on current product data
            updateQuantityInputSteps();
            // Add event listener for when the 'Allow Decimal Quantity?' selection changes
            isDecimalQuantitySelect.addEventListener('change', updateQuantityInputSteps);
            // --- END Dynamic step ---
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('unit_measurement');
    const customInput = document.getElementById('custom_unit');
    function toggleCustomInput() {
        if (select.value === 'Other') {
            customInput.classList.remove('hidden');
            customInput.required = true;
        } else {
            customInput.classList.add('hidden');
            customInput.required = false;
        }
    }
    // Run on load and change
    toggleCustomInput();
    select.addEventListener('change', toggleCustomInput);
});
</script>
</body>
</html>