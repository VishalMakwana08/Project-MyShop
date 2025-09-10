<?php
require_once '../config.php';


// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$success = $error = "";

// Fetch categories for this shop
$cats = [];
$result = mysqli_query($conn, "SELECT id, name FROM categories WHERE shop_id = $shop_id ORDER BY name");
if ($result) {
    $cats = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $error = "❌ Failed to fetch categories: " . mysqli_error($conn);
}

// Fetch shop name for display
$shop_name = '';
$shop_query = mysqli_query($conn, "SELECT shop_name FROM shops WHERE id = $shop_id LIMIT 1");
if ($shop_query && $row = mysqli_fetch_assoc($shop_query)) {
    $shop_name = $row['shop_name'];
}

// Handle form submission (only insert if valid, no rerendering)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['products'])) {
    $products = $_POST['products'];
    $validProducts = [];
    $insert_count = 0;

    foreach ($products as $index => $product) {
        // Basic PHP validation (should not be reached if JS validation is correct)
        $product_id = strtoupper(trim(mysqli_real_escape_string($conn, $product['product_id'])));
        $name = trim(mysqli_real_escape_string($conn, $product['name']));
        $category_id = intval($product['category_id']);
        $description = trim(mysqli_real_escape_string($conn, $product['description']));
        $price = floatval($product['price']);
        $cost_price_val = (isset($product['cost_price']) && $product['cost_price'] !== '') ? floatval($product['cost_price']) : null;
        $discount_percent = floatval($product['discount_percent']);
        $stock_quantity = floatval($product['stock_quantity']);
        $unit_measurement = trim(mysqli_real_escape_string($conn, $product['unit_measurement']));
        $is_decimal_quantity = isset($product['is_decimal_quantity']) ? intval($product['is_decimal_quantity']) : 0;
        $low_stock_threshold_val = (isset($product['low_stock_threshold']) && $product['low_stock_threshold'] !== '') ? floatval($product['low_stock_threshold']) : 0;
        $min_qty_for_discount_val = floatval($product['min_qty_for_discount']);
        $qty_discount_percentage_val = floatval($product['qty_discount_percentage']);
        $status = isset($product['status']) ? intval($product['status']) : 1;
        $unit_measurement_other = isset($product['unit_measurement_other']) ? trim(mysqli_real_escape_string($conn, $product['unit_measurement_other'])) : '';

        if ($unit_measurement === 'Other' && $unit_measurement_other) {
            $unit_measurement = $unit_measurement_other;
        }

        // Image upload
        $image_path = '';
        if (isset($_FILES['products']['name'][$index]['image']) && $_FILES['products']['name'][$index]['image']) {
            $img_name = $_FILES['products']['name'][$index]['image'];
            $img_tmp = $_FILES['products']['tmp_name'][$index]['image'];
            $img_error = $_FILES['products']['error'][$index]['image'];
            $img_size = $_FILES['products']['size'][$index]['image'];
            $maxFileSize = 2 * 1024 * 1024; // 2MB
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $target_dir = "uploads/";

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if ($img_error === UPLOAD_ERR_OK && $img_size <= $maxFileSize) {
                $ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_ext)) {
                    $newFileName = "prod_" . uniqid() . "." . $ext;
                    $target_file = $target_dir . $newFileName;
                    if (move_uploaded_file($img_tmp, $target_file)) {
                        $image_path = $target_file;
                    }
                }
            }
        }

        $validProducts[] = [
            'shop_id' => $shop_id,
            'product_id' => $product_id,
            'name' => $name,
            'category_id' => $category_id,
            'description' => $description,
            'price' => $price,
            'cost_price' => $cost_price_val,
            'discount_percent' => $discount_percent,
            'stock_quantity' => $stock_quantity,
            'unit_measurement' => $unit_measurement,
            'is_decimal_quantity' => $is_decimal_quantity,
            'low_stock_threshold' => $low_stock_threshold_val,
            'min_qty_for_discount' => $min_qty_for_discount_val,
            'qty_discount_percentage' => $qty_discount_percentage_val,
            'status' => $status,
            'attributes' => isset($product['attributes']) ? $product['attributes'] : '[]',
            'image_path' => $image_path
        ];
    }

    // ===== ADDED: Check for duplicate product IDs =====
    foreach ($validProducts as $index => $product) {
        // Check if product ID already exists in this shop
        $check_sql = "SELECT id FROM products WHERE shop_id = ? AND product_id = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("is", $shop_id, $product['product_id']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = "❌ Product ID '{$product['product_id']}' already exists in your shop (Row " . ($index + 1) . ")";
                unset($validProducts[$index]); // Remove this product from insertion
            }
            $check_stmt->close();
        }
    }

    // Re-index array after possible removals
    $validProducts = array_values($validProducts);
    // ===== END OF ADDED CODE =====

    // Insert all valid products
    foreach ($validProducts as $product) {
        $insert_sql = "INSERT INTO products
            (shop_id, product_id, name, category_id, description, price, cost_price, discount_percent, 
             stock_quantity, unit_measurement, is_decimal_quantity, low_stock_threshold,
             min_qty_for_discount, qty_discount_percentage, image_path, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        if ($stmt = $conn->prepare($insert_sql)) {
            $stmt->bind_param(
                "ississddssiddds",
                $product['shop_id'],
                $product['product_id'],
                $product['name'],
                $product['category_id'],
                $product['description'],
                $product['price'],
                $product['cost_price'],
                $product['discount_percent'],
                $product['stock_quantity'],
                $product['unit_measurement'],
                $product['is_decimal_quantity'],
                $product['low_stock_threshold'],
                $product['min_qty_for_discount'],
                $product['qty_discount_percentage'],
                $product['image_path']
            );
            if ($stmt->execute()) {
                $insert_count++;
                $product_db_id = mysqli_insert_id($conn);
                $attributes = json_decode($product['attributes'], true);
                if (!empty($attributes)) {
                    $attr_sql = "INSERT INTO product_attribute_values (product_id, attribute_name, attribute_value) VALUES (?, ?, ?)";
                    if ($attr_stmt = $conn->prepare($attr_sql)) {
                        foreach ($attributes as $attr) {
                            if (!empty($attr['key']) && !empty($attr['value'])) {
                                $attr_stmt->bind_param("iss", $product_db_id, $attr['key'], $attr['value']);
                                $attr_stmt->execute();
                            }
                        }
                        $attr_stmt->close();
                    }
                }
            }
            $stmt->close();
        }
    }

    if ($insert_count > 0) {
        $success = "✅ Successfully inserted $insert_count products!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Product Insertion - MyShop</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"> -->
      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #374151;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .scrollable-table {
            overflow-x: auto;
            max-width: 100%;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        thead th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
        }
        
        tbody tr {
            background-color: #fff;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
        }
        
        td {
            padding: 1rem;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
        }
        
        tbody tr:hover {
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }

        input[type="text"], input[type="number"], select, textarea {
            width: 180px;           /* Set a fixed width for all fields */
            min-width: 180px;       /* Prevent shrinking */
            max-width: 100%;        /* Responsive for smaller screens */
            box-sizing: border-box; /* Include padding/border in width */
            padding: 0.75rem;
            font-size: 1.05rem;
            border: 1.5px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: #f9fafb;
            display: inline-block;
            vertical-align: middle;
        }

        textarea {
            height: 48px;           /* Make textareas visually similar in height */
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .error {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-align: center;
        }

        .status-active {
            background-color: #10b981;
        }

        .status-inactive {
            background-color: #ef4444;
        }
        
        .status-badge:hover {
            opacity: 0.8;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .custom-dialog {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .dialog-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        tr.invalid-row {
            box-shadow: 0 0 0 2px red !important;
            border-radius: 0.5rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
    </style>
</head>
<body class="bg-gray-100 p-8 font-sans antialiased flex flex-col items-center">
    <div class="container">
        <div class="bg-white p-8 rounded-lg shadow-2xl">
            <h1 class="text-4xl font-extrabold text-center mb-8 text-indigo-700">Bulk Product Insertion</h1>
            
            <?php if ($success): ?>
                <div id="success-message" class="bg-green-100 text-green-800 px-5 py-3 rounded-lg flex items-center gap-3 text-lg font-medium animate-pulse mb-6">
                    <i class="fa-solid fa-check-circle text-green-500"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div id="error-message" class="bg-red-100 text-red-800 px-5 py-3 rounded-lg flex items-center gap-3 text-lg font-medium mb-6">
                    <i class="fa-solid fa-exclamation-circle text-red-500"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <!-- Shop Info Section -->
            <div class="mb-8 p-6 bg-blue-50 rounded-lg border border-blue-200">
                <h2 class="text-2xl font-semibold mb-4 text-blue-700">Shop Information</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Shop Name</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded-md"><?= htmlspecialchars($shop_name) ?></p>
                </div>
            </div>

            <form id="bulkProductForm" method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="shop_id" value="<?= $shop_id ?>">
                
                <div class="scrollable-table">
                    <div class="table-container">
                        <table id="productTable">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Cost Price</th>
                                    <th>Discount (%)</th>
                                    <th>Stock Quantity</th>
                                    <th>Unit Measurement</th>
                                    <th>Is Decimal?</th>
                                    <th>Low Stock Threshold</th>
                                    <th>Min Qty for Discount</th>
                                    <th>Qty Discount (%)</th>
                                    <th>Description</th>
                                    <th>Attributes</th>
                                    <th>Image</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Only the template row, JS will add more -->
                                <tr data-row-index="0">
                                    <td>
                                        <input type="text" name="products[0][product_id]" class="product-id" placeholder="ID" required>
                                        <span id="error-0-product_id" class="error"></span>
                                    </td>
                                    <td>
                                        <input type="text" name="products[0][name]" placeholder="Product Name" required>
                                        <span id="error-0-name" class="error"></span>
                                    </td>
                                    <!-- <td>
                                        <select name="products[0][category_id]" class="category-select" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($cats as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span id="error-0-category_id" class="error"></span>
                                    </td> -->
                                    <td>
    <select name="products[0][category_id]" id="category-select-0" class="category-select" required>
        <option value="">Select Category</option>
        <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" class="btn-add-category" data-index="0">+ Add New Category</button>
    <span id="error-0-category_id" class="error"></span>
</td>


        
                                    <td>
                                        <input type="number" name="products[0][price]" step="0.01" min="0" placeholder="Price" required>
                                        <span id="error-0-price" class="error"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][cost_price]" step="0.01" min="0" value="0" placeholder="Cost Price">
                                        <span id="error-0-cost_price" class="error"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][discount_percent]" min="0" max="100" value="0" placeholder="0-100">
                                        <span id="error-0-discount_percent" class="error"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][stock_quantity]" step="0.01" min="0" placeholder="Stock" required>
                                        <span id="error-0-stock_quantity" class="error"></span>
                                    </td>
                                    <td>
                                        <select name="products[0][unit_measurement]" class="unit-measurement" onchange="showOtherUnitInput(this)" required>
                                            <option value="">Select Unit</option>
                                            <option value="Piece">Piece</option>
                                            <option value="Kg">Kg</option>
                                            <option value="Gram">Gram</option>
                                            <option value="Liter">Liter</option>
                                            <option value="Pack">Pack</option>
                                            <option value="Meter">Meter</option>
                                            <option value="Box">Box</option>
                                            <option value="Set">Set</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div class="other-unit-input" style="display: none; margin-top: 8px;">
                                            <input type="text" placeholder="Specify unit">
                                        </div>
                                        <span id="error-0-unit_measurement" class="error"></span>
                                    </td>
                                    <td>
                                        <select name="products[0][is_decimal_quantity]">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][low_stock_threshold]" min="0" value="0" placeholder="Threshold">
                                        <span id="error-0-low_stock_threshold" class="error"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][min_qty_for_discount]" min="0" value="0" placeholder="Min Qty">
                                        <span id="error-0-min_qty_for_discount" class="error"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="products[0][qty_discount_percentage]" min="0" max="100" value="0" placeholder="0-100">
                                        <span id="error-0-qty_discount_percentage" class="error"></span>
                                    </td>
                                    <td>
                                        <textarea name="products[0][description]" rows="3" placeholder="Description..."></textarea>
                                    </td>
                                    <td>
                                        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm" onclick="showAttributeModal(this)">Add Attributes</button>
                                        <input type="hidden" name="products[0][attributes]" value="[]">
                                    </td>
                                    <td>
                                        <input type="file" name="products[0][image]" accept="image/*" class="product-image">
                                        <span id="error-0-image" class="error"></span>
                                    </td>
                                    <td>
                                        <div class="status-toggle flex items-center justify-center">
                                            <input type="checkbox" name="products[0][status]" value="1" class="hidden" checked>
                                            <span class="status-badge status-active" onclick="toggleStatus(this)">Active</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" onclick="removeProduct(this)" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <button type="button" onclick="addProduct()" class="flex items-center space-x-2 px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors shadow-lg">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Another Product</span>
                    </button>
                    <button type="button" onclick="clearTable()" class="flex items-center space-x-2 px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-lg">
                        <i class="fas fa-trash-alt"></i>
                        <span>Clear All</span>
                    </button>
                    <button type="submit" class="flex items-center space-x-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg">
                        <i class="fas fa-paper-plane"></i>
                        <span>Submit All Products</span>
                    </button>
                    <a href="dashboard.php" class="flex items-center space-x-2 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors shadow-md">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    Dashboard
                    </a>
                    <a href="products.php" class="flex items-center space-x-2 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors shadow-md">
                        <i class="fas fa-list-alt"></i>
                        <span>Back to Product List</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
<!-- Category Modal -->
<div id="category-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Add New Category</h3>
        
        <input type="text" id="new-category-name" 
               class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:outline-none mb-4"
               placeholder="Enter category name" required>
        
        <div class="flex justify-end gap-3">
            <button type="button" id="cancel-category" 
                    class="px-4 py-2 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition">
                Cancel
            </button>
            <button type="button" id="save-category" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Save
            </button>
        </div>
    </div>
</div>


    <!-- Attribute Management Modal -->
    <div id="attributeModal" class="modal">
        <div class="modal-content">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Manage Attributes</h2>
            <div id="attributesContainer"></div>
            <div class="flex items-center space-x-2 mt-4">
                <input type="text" id="newAttrKey" placeholder="Attribute Name" class="flex-1">
                <input type="text" id="newAttrValue" placeholder="Attribute Value" class="flex-1">
                <button type="button" onclick="addNewAttributeRow()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">Add</button>
            </div>
            <div class="flex justify-end mt-6 space-x-2">
                <button type="button" onclick="saveAttributes()" class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">Save</button>
                <button type="button" onclick="closeAttributeModal()" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Custom Dialogs -->
    <div id="customDialog" class="custom-dialog">
        <div class="dialog-content">
            <p id="dialogMessage" class="text-lg text-gray-700 mb-4"></p>
            <div id="dialogButtons" class="dialog-buttons"></div>
        </div>
    </div>
    

    <script>
        let rowCount = 1;
        let lastProductId = '';
        let currentEditingRowIndex = null;
        let existingProductIds = []; // Will hold all product IDs in this shop

        // Fetch existing product IDs via AJAX on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetch('get_product_ids.php?shop_id=<?= $shop_id ?>')
                .then(response => response.json())
                .then(data => {
                    existingProductIds = data; // Array of product IDs
                });

            // Real-time validation as users type
            document.addEventListener('input', function(e) {
                const row = e.target.closest('tr');
                if (row && row.hasAttribute('data-row-index')) {
                    const index = row.getAttribute('data-row-index');
                    validateRow(row, index);
                }
            });

            // Real-time validation for select changes
            document.addEventListener('change', function(e) {
                const row = e.target.closest('tr');
                if (row && row.hasAttribute('data-row-index')) {
                    const index = row.getAttribute('data-row-index');
                    validateRow(row, index);
                }
            });
            
            // Initialize any "Other" unit inputs
            document.querySelectorAll('.unit-measurement').forEach(select => {
                if (select.value === 'Other') {
                    showOtherUnitInput(select);
                }
            });
            
            // Decimal quantity handling
            document.addEventListener('change', function(e) {
                if (e.target && e.target.name && e.target.name.match(/\[is_decimal_quantity\]$/)) {
                    const row = e.target.closest('tr');
                    const isDecimal = e.target.value === '1';
                    const stockInput = row.querySelector('input[name$="[stock_quantity]"]');
                    const lowStockInput = row.querySelector('input[name$="[low_stock_threshold]"]');
                    const minQtyInput = row.querySelector('input[name$="[min_qty_for_discount]"]');

                    [stockInput, lowStockInput, minQtyInput].forEach(input => {
                        if (input) {
                            input.step = isDecimal ? "0.01" : "1";
                            input.min = "0";
                            
                            // If switching from decimal to non-decimal, round the value
                            if (!isDecimal && input.value) {
                                const currentValue = parseFloat(input.value);
                                if (!isNaN(currentValue)) {
                                    input.value = Math.floor(currentValue);
                                }
                            }
                            
                            // Trigger validation
                            validateRow(row, row.getAttribute('data-row-index'));
                        }
                    });
                }
            });
            
            // Fade out success/error messages after a few seconds
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.classList.add('opacity-0', 'transition', 'duration-500', 'ease-out');
                    setTimeout(() => successMessage.remove(), 500);
                }, 5000);
            }
            
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.classList.add('opacity-0', 'transition', 'duration-500', 'ease-out');
                    setTimeout(() => errorMessage.remove(), 7000);
                }, 7000);
            }
        });

        /**
         * Custom function to show a modal instead of a browser alert.
         * @param {string} message The message to display.
         */
        function customAlert(message) {
            const dialog = document.getElementById('customDialog');
            document.getElementById('dialogMessage').textContent = message;
            document.getElementById('dialogButtons').innerHTML = `<button onclick="closeDialog()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">OK</button>`;
            dialog.style.display = 'flex';
        }

        /**
         * Custom function to show a modal instead of a browser confirm.
         * @param {string} message The message to display.
         * @param {Function} onConfirm Callback function to execute if the user confirms.
         */
        function customConfirm(message, onConfirm) {
            const dialog = document.getElementById('customDialog');
            document.getElementById('dialogMessage').textContent = message;
            document.getElementById('dialogButtons').innerHTML = `
                <button onclick="closeDialog(); ${onConfirm.name}()" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">Yes</button>
                <button onclick="closeDialog()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">No</button>
            `;
            dialog.style.display = 'flex';
        }

        /**
         * Closes the custom dialog box.
         */
        function closeDialog() {
            document.getElementById('customDialog').style.display = 'none';
        }

        /**
         * Toggles the product's status between "Active" and "Inactive".
         * @param {HTMLElement} button The button element that was clicked.
         */
        function toggleStatus(button) {
            const statusDiv = button.closest('.status-toggle');
            const statusInput = statusDiv.querySelector('input[type="checkbox"]');
            const statusBadge = statusDiv.querySelector('.status-badge');
            
            if (statusInput.value === '1') {
                statusInput.value = '0';
                statusInput.checked = false;
                statusBadge.textContent = 'Inactive';
                statusBadge.className = 'status-badge status-inactive';
            } else {
                statusInput.value = '1';
                statusInput.checked = true;
                statusBadge.textContent = 'Active';
                statusBadge.className = 'status-badge status-active';
            }
        }

        /**
         * Adds a new row to the product table with all fields.
         */
        function addProduct() {
            const tbody = document.getElementById('productTable').querySelector('tbody');
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-row-index', rowCount);
            
            // Generate next product ID based on the last one
            let newProductId = '';
            const lastIdInput = document.querySelector(`[data-row-index="${rowCount - 1}"] .product-id`);
            if (lastIdInput && lastIdInput.value) {
                lastProductId = lastIdInput.value;
            }

            if (lastProductId) {
                const prefix = lastProductId.match(/^[a-zA-Z]+/);
                const number = lastProductId.match(/\d+$/);
                if (prefix && number) {
                    newProductId = prefix[0] + (parseInt(number[0]) + 1);
                } else if (number) {
                    newProductId = parseInt(number[0]) + 1;
                }
            }

            const newRowHTML = `
                <td>
                    <input type="text" name="products[${rowCount}][product_id]" class="product-id" placeholder="ID" value="${newProductId}" required>
                    <span id="error-${rowCount}-product_id" class="error"></span>
                </td>
                <td>
                    <input type="text" name="products[${rowCount}][name]" placeholder="Product Name" required>
                    <span id="error-${rowCount}-name" class="error"></span>
                </td>
                <td>
                    <select name="products[${rowCount}][category_id]" class="category-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="error-${rowCount}-category_id" class="error"></span>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][price]" step="0.01" min="0" placeholder="Price" required>
                    <span id="error-${rowCount}-price" class="error"></span>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][cost_price]" step="0.01" min="0" value="0" placeholder="Cost Price">
                    <span id="error-${rowCount}-cost_price" class="error"></span>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][discount_percent]" min="0" max="100" value="0" placeholder="0-100">
                    <span id="error-${rowCount}-discount_percent" class="error"></span>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][stock_quantity]" step="0.01" min="0" placeholder="Stock" required>
                    <span id="error-${rowCount}-stock_quantity" class="error"></span>
                </td>
                <td>
                    <select name="products[${rowCount}][unit_measurement]" class="unit-measurement" onchange="showOtherUnitInput(this)" required>
                        <option value="">Select Unit</option>
                        <option value="Piece">Piece</option>
                        <option value="Kg">Kg</option>
                        <option value="Gram">Gram</option>
                        <option value="Liter">Liter</option>
                        <option value="Pack">Pack</option>
                        <option value="Meter">Meter</option>
                        <option value="Box">Box</option>
                        <option value="Set">Set</option>
                        <option value="Other">Other</option>
                    </select>
                    <div class="other-unit-input" style="display: none; margin-top: 8px;">
                        <input type="text" placeholder="Specify unit">
                    </div>
                    <span id="error-${rowCount}-unit_measurement" class="error"></span>
                </td>
                <td>
                    <select name="products[${rowCount}][is_decimal_quantity]">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][low_stock_threshold]" min="0" value="0" placeholder="Threshold">
                    <span id="error-${rowCount}-low_stock_threshold" class="error"></span>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][min_qty_for_discount]" min="0" value="0" placeholder="Min Qty">
                    <span id="error-${rowCount}-min_qty_for_discount" class="error"></span>
                </td>
                <td>
                    <input type="number" name="products[${rowCount}][qty_discount_percentage]" min="0" max="100" value="0" placeholder="0-100">
                    <span id="error-${rowCount}-qty_discount_percentage" class="error"></span>
                </td>
                <td>
                    <textarea name="products[${rowCount}][description]" rows="3" placeholder="Description..."></textarea>
                </td>
                <td>
                    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm" onclick="showAttributeModal(this)">Add Attributes</button>
                    <input type="hidden" name="products[${rowCount}][attributes]" value="[]">
                </td>
                <td>
                    <input type="file" name="products[${rowCount}][image]" accept="image/*" class="product-image">
                    <span id="error-${rowCount}-image" class="error"></span>
                </td>
                <td>
                    <div class="status-toggle flex items-center justify-center">
                        <input type="checkbox" name="products[${rowCount}][status]" value="1" class="hidden" checked>
                        <span class="status-badge status-active" onclick="toggleStatus(this)">Active</span>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" onclick="removeProduct(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            newRow.innerHTML = newRowHTML;
            tbody.appendChild(newRow);
            rowCount++;
        }
        
        /**
         * Removes a product row from the table.
         * @param {HTMLElement} button The button element that was clicked.
         */
        function removeProduct(button) {
            const row = button.closest('tr');
            row.remove();
        }

        /**
         * Clears all rows from the table except the first one and resets it.
         */
        function clearTable() {
            customConfirm('Are you sure you want to clear all product entries?', performClearTable);
        }

        function performClearTable() {
            const tbody = document.getElementById('productTable').getElementsByTagName('tbody')[0];
            while (tbody.rows.length > 1) {
                tbody.deleteRow(1);
            }
            
            const firstRow = tbody.rows[0];
            firstRow.querySelector('input[name$="[product_id]"]').value = '';
            firstRow.querySelector('input[name$="[name]"]').value = '';
            firstRow.querySelector('select[name$="[category_id]"]').value = '';
            firstRow.querySelector('input[name$="[price]"]').value = '';
            firstRow.querySelector('input[name$="[cost_price]"]').value = '0';
            firstRow.querySelector('input[name$="[discount_percent]"]').value = '0';
            firstRow.querySelector('input[name$="[stock_quantity]"]').value = '';
            firstRow.querySelector('select[name$="[unit_measurement]"]').value = '';
            firstRow.querySelector('select[name$="[is_decimal_quantity]"]').value = '0';
            firstRow.querySelector('input[name$="[low_stock_threshold]"]').value = '0';
            firstRow.querySelector('input[name$="[min_qty_for_discount]"]').value = '0';
            firstRow.querySelector('input[name$="[qty_discount_percentage]"]').value = '0';
            firstRow.querySelector('textarea').value = '';
            firstRow.querySelector('input[name$="[attributes]"]').value = '[]';
            firstRow.querySelector('.status-badge').textContent = 'Active';
            firstRow.querySelector('.status-badge').className = 'status-badge status-active';
            firstRow.querySelector('input[name$="[status]"]').value = '1';
            
            lastProductId = '';
            rowCount = 1;
        }

        /**
         * Shows the "Other" text input field if the user selects "Other" from the unit dropdown.
         * @param {HTMLElement} selectElement The select element that was changed.
         */
        function showOtherUnitInput(selectElement) {
            const row = selectElement.closest('tr');
            const otherInputDiv = row.querySelector('.other-unit-input');
            const otherInput = otherInputDiv.querySelector('input[type="text"]');

            if (selectElement.value === 'Other') {
                otherInputDiv.style.display = 'block';
                otherInput.setAttribute('name', selectElement.name.replace('unit_measurement', 'unit_measurement_other'));
            } else {
                otherInputDiv.style.display = 'none';
                otherInput.removeAttribute('name');
            }
            
            // Trigger validation
            const index = row.getAttribute('data-row-index');
            validateRow(row, index);
        }

        /**
         * Shows the attribute management modal for a specific product row.
         * @param {HTMLElement} button The button that was clicked.
         */
        function showAttributeModal(button) {
            const row = button.closest('tr');
            currentEditingRowIndex = row.getAttribute('data-row-index');
            const attributesInput = row.querySelector('input[name$="[attributes]"]');
            const attributes = JSON.parse(attributesInput.value);
            const container = document.getElementById('attributesContainer');
            container.innerHTML = '';
            
            attributes.forEach((attr, index) => {
                const attrDiv = document.createElement('div');
                attrDiv.className = 'flex items-center space-x-2 mb-2';
                attrDiv.innerHTML = `
                    <input type="text" value="${attr.key}" placeholder="Key" class="flex-1">
                    <input type="text" value="${attr.value}" placeholder="Value" class="flex-1">
                    <button type="button" onclick="removeAttributeRow(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times-circle"></i>
                    </button>
                `;
                container.appendChild(attrDiv);
            });
            
            document.getElementById('attributeModal').style.display = 'flex';
        }

        /**
         * Adds a new empty attribute row inside the modal.
         */
        function addNewAttributeRow() {
            const container = document.getElementById('attributesContainer');
            const newKeyInput = document.getElementById('newAttrKey');
            const newValueInput = document.getElementById('newAttrValue');
            
            if (!newKeyInput.value.trim() || !newValueInput.value.trim()) {
                customAlert('Attribute name and value cannot be empty.');
                return;
            }

            const attrDiv = document.createElement('div');
            attrDiv.className = 'flex items-center space-x-2 mb-2';
            attrDiv.innerHTML = `
                <input type="text" value="${newKeyInput.value}" placeholder="Key" class="flex-1">
                <input type="text" value="${newValueInput.value}" placeholder="Value" class="flex-1">
                <button type="button" onclick="removeAttributeRow(this)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times-circle"></i>
                </button>
            `;
            container.appendChild(attrDiv);
            
            newKeyInput.value = '';
            newValueInput.value = '';
        }
        
        /**
         * Removes an attribute row from the modal.
         * @param {HTMLElement} button The button that was clicked.
         */
        function removeAttributeRow(button) {
            button.closest('div').remove();
        }

        /**
         * Saves the attributes from the modal back to the hidden input field in the product table row.
         */
        function saveAttributes() {
            const container = document.getElementById('attributesContainer');
            const attributeRows = container.querySelectorAll('div');
            const attributes = [];
            attributeRows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                attributes.push({ key: inputs[0].value, value: inputs[1].value });
            });
            
            const tableRow = document.querySelector(`[data-row-index="${currentEditingRowIndex}"]`);
            const attributesInput = tableRow.querySelector('input[name$="[attributes]"]');
            attributesInput.value = JSON.stringify(attributes);
            
            closeAttributeModal();
        }

        /**
         * Closes the attribute management modal.
         */
        function closeAttributeModal() {
            document.getElementById('attributeModal').style.display = 'none';
        }

        /**
         * Validates a single product row
         * @param {HTMLElement} row The table row to validate
         * @param {number} index The row index
         * @returns {boolean} True if the row is valid, otherwise false
         */
        function validateRow(row, index) {
            // Remove any previous invalid class
            row.classList.remove('invalid-row');
            
            let isValid = true;
            
            // Clear previous error messages for this row
            row.querySelectorAll('.error').forEach(el => el.textContent = '');
            row.querySelectorAll('input, select, textarea').forEach(el => el.style.borderColor = '');
            
            // Validate required fields
            const requiredFields = [
                { name: 'product_id', label: 'Product ID' },
                { name: 'name', label: 'Product Name' },
                { name: 'category_id', label: 'Category' },
                { name: 'price', label: 'Price' },
                { name: 'stock_quantity', label: 'Stock Quantity' },
                { name: 'unit_measurement', label: 'Unit Measurement' }
            ];
            
            requiredFields.forEach(field => {
                const input = row.querySelector(`[name$="[${field.name}]"]`);
                const error = row.querySelector(`#error-${index}-${field.name}`);
                if (!input || !input.value.trim()) {
                    if (error) error.textContent = `${field.label} is required`;
                    if (input) input.style.borderColor = 'red';
                    isValid = false;
                }
            });
            
            // Validate product ID uniqueness (client-side only)
            const productIdInput = row.querySelector(`[name$="[product_id]"]`);
            if (productIdInput && productIdInput.value.trim()) {
                const productId = productIdInput.value.trim().toUpperCase();

                // Check against other rows (already in your code)
                const allProductIds = Array.from(document.querySelectorAll(`[name$="[product_id]"]`))
                    .map(input => input.value.trim().toUpperCase())
                    .filter((id, i, arr) => id && arr.indexOf(id) !== i);

                if (allProductIds.includes(productId)) {
                    row.querySelector(`#error-${index}-product_id`).textContent = 'Product ID must be unique in this form';
                    productIdInput.style.borderColor = 'red';
                    isValid = false;
                }

                // Check against existing product IDs in the shop (AJAX loaded)
                if (existingProductIds.includes(productId)) {
                    row.querySelector(`#error-${index}-product_id`).textContent = 'Product ID already exists in your shop';
                    productIdInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate price > cost price
            const priceInput = row.querySelector(`[name$="[price]"]`);
            const costPriceInput = row.querySelector(`[name$="[cost_price]"]`);
            if (priceInput && costPriceInput && priceInput.value && costPriceInput.value) {
                const price = parseFloat(priceInput.value);
                const costPrice = parseFloat(costPriceInput.value);
                
                if (costPrice > price) {
                    row.querySelector(`#error-${index}-cost_price`).textContent = 'Cost price cannot be greater than price';
                    costPriceInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate discount percentage (0-100)
            const discountInput = row.querySelector(`[name$="[discount_percent]"]`);
            if (discountInput && discountInput.value) {
                const discount = parseFloat(discountInput.value);
                if (discount < 0 || discount > 100) {
                    row.querySelector(`#error-${index}-discount_percent`).textContent = 'Discount must be between 0 and 100';
                    discountInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate quantity discount percentage (0-100)
            const qtyDiscountInput = row.querySelector(`[name$="[qty_discount_percentage]"]`);
            if (qtyDiscountInput && qtyDiscountInput.value) {
                const qtyDiscount = parseFloat(qtyDiscountInput.value);
                if (qtyDiscount < 0 || qtyDiscount > 100) {
                    row.querySelector(`#error-${index}-qty_discount_percentage`).textContent = 'Quantity discount must be between 0 and 100';
                    qtyDiscountInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate stock quantity (non-negative)
            const stockInput = row.querySelector(`[name$="[stock_quantity]"]`);
            if (stockInput && stockInput.value) {
                const stock = parseFloat(stockInput.value);
                if (stock < 0) {
                    row.querySelector(`#error-${index}-stock_quantity`).textContent = 'Stock quantity cannot be negative';
                    stockInput.style.borderColor = 'red';
                    isValid = false;
                }
                
                // Check if decimal quantity is allowed
                const isDecimalSelect = row.querySelector(`[name$="[is_decimal_quantity]"]`);
                const isDecimal = isDecimalSelect && isDecimalSelect.value === '1';
                
                if (!isDecimal && !Number.isInteger(stock)) {
                    row.querySelector(`#error-${index}-stock_quantity`).textContent = 'Stock quantity must be a whole number';
                    stockInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate low stock threshold (non-negative and <= stock)
            const lowStockInput = row.querySelector(`[name$="[low_stock_threshold]"]`);
            if (lowStockInput && lowStockInput.value) {
                const lowStock = parseFloat(lowStockInput.value);
                const stock = stockInput && stockInput.value ? parseFloat(stockInput.value) : 0;
                
                if (lowStock < 0) {
                    row.querySelector(`#error-${index}-low_stock_threshold`).textContent = 'Low stock threshold cannot be negative';
                    lowStockInput.style.borderColor = 'red';
                    isValid = false;
                }
                
                if (lowStock > stock) {
                    row.querySelector(`#error-${index}-low_stock_threshold`).textContent = 'Low stock threshold cannot be greater than stock quantity';
                    lowStockInput.style.borderColor = 'red';
                    isValid = false;
                }
                
                // Check if decimal quantity is allowed
                const isDecimalSelect = row.querySelector(`[name$="[is_decimal_quantity]"]`);
                const isDecimal = isDecimalSelect && isDecimalSelect.value === '1';
                
                if (!isDecimal && !Number.isInteger(lowStock)) {
                    row.querySelector(`#error-${index}-low_stock_threshold`).textContent = 'Low stock threshold must be a whole number';
                    lowStockInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate min quantity for discount (non-negative)
            const minQtyInput = row.querySelector(`[name$="[min_qty_for_discount]"]`);
            if (minQtyInput && minQtyInput.value) {
                const minQty = parseFloat(minQtyInput.value);
                
                if (minQty < 0) {
                    row.querySelector(`#error-${index}-min_qty_for_discount`).textContent = 'Minimum quantity for discount cannot be negative';
                    minQtyInput.style.borderColor = 'red';
                    isValid = false;
                }
                
                // Check if decimal quantity is allowed
                const isDecimalSelect = row.querySelector(`[name$="[is_decimal_quantity]"]`);
                const isDecimal = isDecimalSelect && isDecimalSelect.value === '1';
                
                if (!isDecimal && !Number.isInteger(minQty)) {
                    row.querySelector(`#error-${index}-min_qty_for_discount`).textContent = 'Minimum quantity for discount must be a whole number';
                    minQtyInput.style.borderColor = 'red';
                    isValid = false;
                }
            }
            
            // Validate unit measurement if "Other" is selected
            const unitSelect = row.querySelector(`[name$="[unit_measurement]"]`);
            if (unitSelect && unitSelect.value === 'Other') {
                const otherUnitInput = row.querySelector('.other-unit-input input[type="text"]');
                if (!otherUnitInput || !otherUnitInput.value.trim()) {
                    row.querySelector(`#error-${index}-unit_measurement`).textContent = 'Please specify the custom unit';
                    unitSelect.style.borderColor = 'red';
                    isValid = false;
                }
            }

            // Image validation (type and size)
            const imageInput = row.querySelector(`[name="products[${index}][image]"]`);
            const imageError = row.querySelector(`#error-${index}-image`);
            if (imageInput && imageInput.files && imageInput.files.length > 0) {
                const file = imageInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    imageError.textContent = 'Allowed types: JPG, JPEG, PNG, GIF';
                    imageInput.style.borderColor = 'red';
                    isValid = false;
                } else if (file.size > maxSize) {
                    imageError.textContent = 'Image size must be 2MB or less';
                    imageInput.style.borderColor = 'red';
                    isValid = false;
                }
            }

            // Add invalid class if row has errors
            if (!isValid) {
                row.classList.add('invalid-row');
            }
            return isValid;
        }

        /**
         * Validates the form before submission.
         * @returns {boolean} True if the form is valid, otherwise false.
         */
        function validateForm() {
            let isValid = true;
            const rows = document.querySelectorAll('#productTable tbody tr');
            
            rows.forEach((row, index) => {
                if (!validateRow(row, index)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                customAlert('Please fix the errors in the highlighted rows.');
            }
            
            return isValid;
        }
        
        // Add event listeners when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Event listener for the form submission
            document.getElementById('bulkProductForm').addEventListener('submit', function(e) {
                document.querySelectorAll('.unit-measurement').forEach(function(select) {
                    const row = select.closest('tr');
                    const otherInput = row.querySelector('.other-unit-input input[type="text"]');
                    if (select.value === 'Other') {
                        if (otherInput && otherInput.value.trim()) {
                            // Ensure the custom field has a name and value
                            otherInput.setAttribute('name', select.name.replace('unit_measurement', 'unit_measurement_other'));
                        } else {
                            // If "Other" is selected but no custom value, prevent submit
                            e.preventDefault();
                            customAlert('Please specify the custom unit measurement.');
                        }
                    }else {
                        // Remove name attribute if not "Other"
                        if (otherInput) otherInput.removeAttribute('name');
                    }
                });

                if (!validateForm()) {
                    e.preventDefault();
                }
            });
        });
    </script>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    let currentSelect = null;

    // Open modal
    document.querySelectorAll(".btn-add-category").forEach(btn => {
        btn.addEventListener("click", function () {
            currentSelect = document.getElementById("category-select-" + this.dataset.index);
            document.getElementById("category-modal").classList.remove("hidden");
        });
    });

    // Cancel modal
    document.getElementById("cancel-category").addEventListener("click", function () {
        document.getElementById("category-modal").classList.add("hidden");
        document.getElementById("new-category-name").value = "";
    });

    // Save category
    document.getElementById("save-category").addEventListener("click", function () {
        const name = document.getElementById("new-category-name").value.trim();
        if (!name) {
            alert("Please enter category name");
            return;
        }

        fetch("add_category.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "name=" + encodeURIComponent(name)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Add new option to select
                const option = document.createElement("option");
                option.value = data.id;
                option.textContent = data.name;
                currentSelect.appendChild(option);
                currentSelect.value = data.id;

                // Close modal
                document.getElementById("category-modal").classList.add("hidden");
                document.getElementById("new-category-name").value = "";
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    });
});
</script>

</body>
</html>