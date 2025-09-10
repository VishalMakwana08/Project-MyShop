<?php
// billing.php
// include 'check_internet.php';
require_once '../config.php';

// Start session if not already started (important for $_SESSION['current_bill'])
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$success = "";
$error = "";

$gst_number = $shop_license = $bill_footer = "";
$show_gst = $show_license = $auto_discount = 0;
$low_stock_threshold = 5; // Default value, will be fetched from settings

// --- Fetch Shop Details and Settings ---
// Fetch shop details for GST/License display
$shop_query = mysqli_query($conn, "SELECT gst_number, shop_license FROM shops WHERE id = $shop_id");
if ($shop_row = mysqli_fetch_assoc($shop_query)) {
    $gst_number = $shop_row['gst_number'];
    $shop_license = $shop_row['shop_license'];
}

// Fetch shop settings
$settings_query = mysqli_query($conn, "SELECT show_gst, show_license, auto_discount, bill_footer, low_stock_threshold FROM shop_settings WHERE shop_id = $shop_id LIMIT 1");
if ($settings_row = mysqli_fetch_assoc($settings_query)) {
    $show_gst = (int) $settings_row['show_gst'];
    $show_license = (int) $settings_row['show_license'];
    $auto_discount = (int) $settings_row['auto_discount'];
    $bill_footer = $settings_row['bill_footer'];
    $low_stock_threshold = (int) $settings_row['low_stock_threshold'];
}

// --- Product Data for Selector ---
// Fetch all active products for the dropdown
$all_products = [];
$products_res = mysqli_query($conn, "SELECT id, product_id, name, price, discount_percent, is_decimal_quantity, unit_measurement, min_qty_for_discount, qty_discount_percentage FROM products WHERE shop_id = $shop_id ORDER BY name ASC");
while ($p_row = mysqli_fetch_assoc($products_res)) {
    $all_products[] = $p_row;
}

// Initialize current bill if not set
if (!isset($_SESSION['current_bill'])) {
    $_SESSION['current_bill'] = [];
}
// Initialize session variable to track if a bill was loaded from pending
if (!isset($_SESSION['loaded_pending_bill_id'])) {
    $_SESSION['loaded_pending_bill_id'] = null;
}

/**
 * Calculates the effective discount percentage for a product.
 * Prioritizes quantity discount if applicable, then adds auto-discount if low stock.
 *
 * @param float $base_discount_percent Original discount percent from product.
 * @param float $qty_discount_threshold Minimum quantity for quantity discount.
 * @param float $qty_discount_percent Quantity-based discount percent.
 * @param float $item_quantity Quantity currently being added/considered.
 * @param float $current_stock Current stock quantity.
 * @param int $auto_discount_enabled Global setting for auto discount.
 * @param float $low_stock_thresh Global low stock threshold.
 * @return float The final effective discount percentage.
 */
function calculateEffectiveDiscount(
    $base_discount_percent,
    $qty_discount_threshold,
    $qty_discount_percent,
    $item_quantity,
    $current_stock,
    $auto_discount_enabled,
    $low_stock_thresh
) {
    $effective_discount = $base_discount_percent;

    // Apply quantity-based discount if applicable (overrides base_discount_percent)
    if ($qty_discount_threshold > 0 && $item_quantity >= $qty_discount_threshold) {
        $effective_discount = max($effective_discount, $qty_discount_percent); // Take the higher of base or quantity discount
    }

    // Apply auto-discount for low stock if enabled (adds to existing effective discount)
    if ($auto_discount_enabled && $current_stock < $low_stock_thresh) {
        $effective_discount += 10; // Add 10% for low stock
    }

    return min(100, $effective_discount); // Ensure discount does not exceed 100%
}


// --- Handle Adding an Item to the Current Bill Session ---
if (isset($_POST['add_item'])) {
    $product_id_string = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['product_id'])));
    $quantity = floatval($_POST['quantity']);

    if ($product_id_string && $quantity > 0) {
        // Fetch product details from DB including internal 'id' and new discount fields
        // MODIFICATION: Added cost_price to the SELECT query
        $query = "SELECT id, name, price, cost_price,stock_quantity, discount_percent, is_decimal_quantity, unit_measurement, min_qty_for_discount, qty_discount_percentage FROM products WHERE shop_id = $shop_id AND product_id = '$product_id_string' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($row = mysqli_fetch_assoc($result)) {
            $product_internal_id = $row['id']; // Internal primary key for attribute lookup
            $name = $row['name'];
            $base_price_from_db = floatval($row['price']);
            $cost_price_from_db = floatval($row['cost_price']); // Original cost price from DB
            $stock = floatval($row['stock_quantity']);
            $original_product_discount_percent = floatval($row['discount_percent']); // Original product discount
            $min_qty_for_discount = floatval($row['min_qty_for_discount']); // Quantity discount threshold
            $qty_discount_percentage = floatval($row['qty_discount_percentage']); // Quantity discount percent
            $is_decimal_quantity = intval($row['is_decimal_quantity']);
            $unit_measurement = htmlspecialchars($row['unit_measurement']); // Unit of measurement

            // Fetch product attributes
            $attributes = [];
            $attr_sql = "SELECT attribute_name, attribute_value FROM product_attribute_values WHERE product_id = $product_internal_id ORDER BY attribute_name ASC";
            $attr_result = mysqli_query($conn, $attr_sql);
            while ($attr = mysqli_fetch_assoc($attr_result)) {
                $attributes[] = ['name' => $attr['attribute_name'], 'value' => $attr['attribute_value']];
            }
            // Store attributes as a JSON string
            $attributes_json = json_encode($attributes);


            // Validate decimal quantity
            if (!$is_decimal_quantity && floor($quantity) != $quantity) {
                $error = "❌ This product does not support decimal quantity. Please enter a whole number.";
            } else {
                // Determine effective quantity for stock check and discount calculation
                $current_item_qty_in_bill = 0;
                $existing_item_key = null; // Store key if item exists to update it
                foreach ($_SESSION['current_bill'] as $key => $item) {
                    if ($item['product_id_string'] === $product_id_string) {
                        $current_item_qty_in_bill = $item['quantity'];
                        $existing_item_key = $key;
                        break;
                    }
                }
                
                $new_total_quantity = $current_item_qty_in_bill + $quantity;

                if ($new_total_quantity > $stock) {
                    $error = "❌ Not enough stock for " . htmlspecialchars($name) . ". Available: " . $stock . ". Already in bill: " . $current_item_qty_in_bill . ".";
                } else {
                    // Calculate effective discount based on combined quantity and stock
                    $applied_discount_percent = calculateEffectiveDiscount(
                        $original_product_discount_percent,
                        $min_qty_for_discount,
                        $qty_discount_percentage,
                        $new_total_quantity, // Use total quantity for discount check
                        $stock,
                        $auto_discount,
                        $low_stock_threshold
                    );
                    
                    $final_price_per_unit = round($base_price_from_db * (1 - $applied_discount_percent / 100), 2);
                    $item_total_amount = round($final_price_per_unit * $new_total_quantity, 2);

                    // Update existing item or add new one
                    if ($existing_item_key !== null) {
                        $_SESSION['current_bill'][$existing_item_key]['quantity'] = $new_total_quantity;
                        $_SESSION['current_bill'][$existing_item_key]['base_price_at_add'] = $base_price_from_db; // Store original price
                        // MODIFICATION: Store the cost price when updating an existing item
                        $_SESSION['current_bill'][$existing_item_key]['cost_price_at_add'] = $cost_price_from_db; 
                        $_SESSION['current_bill'][$existing_item_key]['discount_percent_at_add'] = $original_product_discount_percent; // Store original product discount
                        $_SESSION['current_bill'][$existing_item_key]['min_qty_for_discount_at_add'] = $min_qty_for_discount; // Store for history
                        $_SESSION['current_bill'][$existing_item_key]['qty_discount_percentage_at_add'] = $qty_discount_percentage; // Store for history
                        $_SESSION['current_bill'][$existing_item_key]['applied_discount_percent'] = $applied_discount_percent; // Store calculated applied discount
                        $_SESSION['current_bill'][$existing_item_key]['final_price_at_add'] = $final_price_per_unit;
                        $_SESSION['current_bill'][$existing_item_key]['total'] = $item_total_amount;
                        $_SESSION['current_bill'][$existing_item_key]['is_decimal'] = $is_decimal_quantity;
                        $_SESSION['current_bill'][$existing_item_key]['unit_measurement_at_add'] = $unit_measurement;
                        $_SESSION['current_bill'][$existing_item_key]['attributes_at_add_json'] = $attributes_json;
                    } else {
                        $_SESSION['current_bill'][] = [
                            "product_id_string"         => $product_id_string,
                            "name"                      => $name,
                            "base_price_at_add"         => $base_price_from_db, // Original price
                            // MODIFICATION: Store the cost price for the new item
                            "cost_price_at_add"         => $cost_price_from_db, 
                            "discount_percent_at_add"   => $original_product_discount_percent, // Original product discount
                            "min_qty_for_discount_at_add" => $min_qty_for_discount, // For historical logging
                            "qty_discount_percentage_at_add" => $qty_discount_percentage, // For historical logging
                            "applied_discount_percent"  => $applied_discount_percent, // The actual discount applied
                            "final_price_at_add"        => $final_price_per_unit, // Price per unit after all discounts
                            "quantity"                  => $quantity,
                            "total"                     => round($final_price_per_unit * $quantity, 2),
                            "is_decimal"                => $is_decimal_quantity,
                            "unit_measurement_at_add"   => $unit_measurement,
                            "attributes_at_add_json"    => $attributes_json
                        ];
                    }
                    $success = "✅ Item '" . htmlspecialchars($name) . "' added.";
                }
            }
        } else {
            $error = "❌ Invalid product ID: '$product_id_string' not found.";
        }
    } else {
        $error = "❌ Please enter a valid Product ID and Quantity (must be greater than 0).";
    }
    // Clear any loaded pending bill ID if a new item is added to an active bill
    $_SESSION['loaded_pending_bill_id'] = null;
}

// --- Handle Saving Current Bill as Pending ---
if (isset($_POST['save_pending_bill'])) {
    if (empty($_SESSION['current_bill'])) {
        $error = "❌ Cannot save an empty bill as pending.";
    } else {
        $customer_name = mysqli_real_escape_string($conn, trim($_POST["customer_name"] ?? ''));
        $customer_mobile = mysqli_real_escape_string($conn, trim($_POST["customer_mobile"] ?? ''));
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
        
        // Validate customer details
        if (empty($customer_name)) {
            $error = "❌ Customer name is required to save as pending bill.";
        } elseif (empty($customer_mobile)) {
            $error = "❌ Customer mobile number is required to save as pending bill.";
        } elseif (!preg_match('/^[0-9]{10}$/', $customer_mobile)) {
            $error = "❌ Please enter a valid 10-digit mobile number.";
        } else {
            $bill_data_json = mysqli_real_escape_string($conn, json_encode($_SESSION['current_bill']));

            mysqli_begin_transaction($conn);
            try {
                // Check if this bill was previously loaded from pending
                if ($_SESSION['loaded_pending_bill_id']) {
                    $pending_bill_id = intval($_SESSION['loaded_pending_bill_id']);
                    $update_query = "UPDATE pending_bills SET 
                                         customer_name = '$customer_name', 
                                         customer_mobile = '$customer_mobile', 
                                         bill_data_json = '$bill_data_json',
                                         payment_method = '$payment_method',
                                         status = 'pending'
                                         WHERE id = $pending_bill_id AND shop_id = $shop_id";
                    if (!mysqli_query($conn, $update_query)) {
                        throw new Exception("Failed to update existing pending bill: " . mysqli_error($conn));
                    }
                    $new_pending_bill_id = $pending_bill_id;
                    $success = "✅ Pending bill **#{$new_pending_bill_id}** updated and saved.";
                } else {
                    // Insert a new pending bill
                    $insert_query = "INSERT INTO pending_bills (shop_id, customer_name, customer_mobile, bill_data_json, payment_method, status) 
                                         VALUES ($shop_id, '$customer_name', '$customer_mobile', '$bill_data_json', '$payment_method', 'pending')";
                    if (!mysqli_query($conn, $insert_query)) {
                        throw new Exception("Failed to save new pending bill: " . mysqli_error($conn));
                    }
                    $new_pending_bill_id = mysqli_insert_id($conn);
                    $success = "✅ Bill saved to pending with ID **#{$new_pending_bill_id}**.";
                }

                mysqli_commit($conn);
                $_SESSION['current_bill'] = [];
                $_SESSION['loaded_pending_bill_id'] = null;
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "❌ Error saving pending bill: " . $e->getMessage();
            }
        }
    }
}
// --- Handle Loading a Pending Bill ---
if (isset($_POST['load_pending_bill'])) {
    $pending_bill_id_to_load = intval($_POST['pending_bill_id_to_load']);

    if (empty($pending_bill_id_to_load)) {
        $error = "❌ Please select a pending bill to load.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Fetch the pending bill data
            $fetch_query = "SELECT customer_name, customer_mobile, bill_data_json, payment_method FROM pending_bills WHERE id = $pending_bill_id_to_load AND shop_id = $shop_id AND status = 'pending' LIMIT 1";
            $result = mysqli_query($conn, $fetch_query);

            if ($row = mysqli_fetch_assoc($result)) {
                // Decode JSON and load into session
                $_SESSION['current_bill'] = json_decode($row['bill_data_json'], true);
                $_SESSION['loaded_pending_bill_id'] = $pending_bill_id_to_load;

                // Set customer details in session for sticky form (or direct output in HTML)
                // For direct use in HTML, we'll store them temporarily or load on page refresh.
                // A better approach for the current setup is to pass them via GET or rely on client-side JS to fill the form.
                // For simplicity, we'll ensure they are reflected in the form after refresh.
                // We'll use JavaScript in the HTML part to populate the customer fields if a bill is loaded.
                $customer_name_for_form = htmlspecialchars($row['customer_name'] ?? '');
                $customer_mobile_for_form = htmlspecialchars($row['customer_mobile'] ?? '');
                $payment_method_for_form = htmlspecialchars($row['payment_method'] ?? 'cash'); // NEW: Get payment method

                // Update status to 'loaded' to prevent loading the same bill multiple times or indicate it's active
                $update_status_query = "UPDATE pending_bills SET status = 'loaded' WHERE id = $pending_bill_id_to_load AND shop_id = $shop_id";
                if (!mysqli_query($conn, $update_status_query)) {
                    throw new Exception("Failed to update pending bill status: " . mysqli_error($conn));
                }

                mysqli_commit($conn);
                $success = "✅ Pending Bill **#{$pending_bill_id_to_load}** loaded successfully.";
                // Redirect to refresh page, passing customer data to populate form
                header("Location: billing.php?success=" . urlencode($success) . "&customer_name=" . urlencode($customer_name_for_form) . "&customer_mobile=" . urlencode($customer_mobile_for_form) . "&payment_method=" . urlencode($payment_method_for_form)); // NEW: Pass payment method
                exit;

            } else {
                throw new Exception("Pending bill not found or already loaded/completed.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "❌ Error loading pending bill: " . $e->getMessage();
        }
    }
}


// --- Handle Confirming the Bill ---
if (isset($_POST['confirm_bill'])) {
    // Validate and sanitize inputs
    $customer_name = !empty($_POST["customer_name"]) ? mysqli_real_escape_string($conn, trim($_POST["customer_name"])) : '';
    $customer_mobile = !empty($_POST["customer_mobile"]) ? mysqli_real_escape_string($conn, trim($_POST["customer_mobile"])) : '';
    $payment_method = !empty($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : 'cash';
    
    // Validate payment method
    $valid_payment_methods = ['cash', 'card', 'upi', 'online'];
    if (!in_array($payment_method, $valid_payment_methods)) {
        $payment_method = 'cash'; // Default to cash if invalid
    }

    // Get items from session
    $items = $_SESSION['current_bill'] ?? [];

    // Validate required fields
    if (empty($customer_name) || empty($customer_mobile)) {
        $error = "❌ Please fill in customer name and mobile number.";
    } elseif (empty($items)) {
        $error = "❌ Please add at least one product to the bill.";
    } else {
        // Calculate total bill amount
        $total_bill_amount = 0;
        foreach ($items as $item) {
            $total_bill_amount += floatval($item['total'] ?? 0);
        }

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Pre-check stock for all items before processing
            foreach ($items as $item) {
                $product_id_string = mysqli_real_escape_string($conn, $item['product_id_string'] ?? '');
                $quantity_requested = floatval($item['quantity'] ?? 0);
                $product_name = htmlspecialchars($item['name'] ?? 'Unknown Product');

                if (empty($product_id_string) || $quantity_requested <= 0) {
                    throw new Exception("Invalid product data for: " . $product_name);
                }

                // Check product existence and stock
                $stock_check_query = "SELECT stock_quantity, name FROM products 
                                         WHERE shop_id = $shop_id AND product_id = '$product_id_string' 
                                         LIMIT 1";
                $stock_check_result = mysqli_query($conn, $stock_check_query);
                
                if (!$stock_check_result) {
                    throw new Exception("Database error while checking stock: " . mysqli_error($conn));
                }

                if (mysqli_num_rows($stock_check_result) == 0) {
                    throw new Exception("Product '$product_name' not found in database.");
                }

                $stock_row = mysqli_fetch_assoc($stock_check_result);
                $available_stock = floatval($stock_row['stock_quantity'] ?? 0);

                if ($available_stock < $quantity_requested) {
                    throw new Exception("Insufficient stock for '$product_name'. Available: $available_stock, Requested: $quantity_requested");
                }
            }

            // Insert main bill record
            $bill_insert_query = "INSERT INTO bills (shop_id, customer_name, customer_mobile, total_amount, payment_method) 
                                     VALUES ($shop_id, '$customer_name', '$customer_mobile', $total_bill_amount, '$payment_method')";
            
            if (!mysqli_query($conn, $bill_insert_query)) {
                throw new Exception("Failed to create bill record: " . mysqli_error($conn));
            }

            $bill_id = mysqli_insert_id($conn);

            // Process each bill item
            foreach ($items as $item) {
                $product_id_string = mysqli_real_escape_string($conn, $item['product_id_string'] ?? '');
                $product_name = mysqli_real_escape_string($conn, $item['name'] ?? '');
                
                // Historical data for record keeping
                $base_price = floatval($item['base_price_at_add'] ?? 0);
                // MODIFICATION: Retrieve the cost price from the session array
                $cost_price = floatval($item['cost_price_at_add'] ?? 0); 
                $discount_percent = floatval($item['discount_percent_at_add'] ?? 0);
                $is_decimal_quantity = intval($item['is_decimal'] ?? 0);
                $unit_measurement = mysqli_real_escape_string($conn, $item['unit_measurement_at_add'] ?? '');
                $attributes_json = mysqli_real_escape_string($conn, $item['attributes_at_add_json'] ?? '[]');
                
                // Actual sale values
                $final_unit_price = floatval($item['final_price_at_add'] ?? 0);
                $quantity_sold = floatval($item['quantity'] ?? 0);
                $item_total = floatval($item['total'] ?? 0);
                $applied_discount = floatval($item['applied_discount_percent'] ?? 0);

                // MODIFICATION: Corrected column name to product_cost_at_sale
                $item_insert_query = "INSERT INTO bill_items (
                    bill_id, product_id, product_name, price, quantity, total, discount,
                    product_name_at_sale, product_price_at_sale, product_discount_percent_at_sale,
                    product_is_decimal_quantity_at_sale, product_unit_measurement_at_sale,
                    product_attributes_at_sale, product_cost_at_sale
                ) VALUES (
                    $bill_id, '$product_id_string', '$product_name', $final_unit_price, $quantity_sold, 
                    $item_total, $applied_discount, '$product_name', $base_price, $discount_percent,
                    $is_decimal_quantity, '$unit_measurement', '$attributes_json', $cost_price
                )";

                if (!mysqli_query($conn, $item_insert_query)) {
                    throw new Exception("Failed to insert bill item for '$product_name': " . mysqli_error($conn));
                }

                // Update product stock
                $stock_update_query = "UPDATE products 
                                         SET stock_quantity = stock_quantity - $quantity_sold 
                                         WHERE shop_id = $shop_id AND product_id = '$product_id_string'";
                
                if (!mysqli_query($conn, $stock_update_query)) {
                    throw new Exception("Failed to update stock for '$product_name': " . mysqli_error($conn));
                }
            }

            // Mark pending bill as completed if this was loaded from pending
            if (!empty($_SESSION['loaded_pending_bill_id'])) {
                $pending_bill_id = intval($_SESSION['loaded_pending_bill_id']);
                $update_pending_query = "UPDATE pending_bills SET status = 'completed' 
                                             WHERE id = $pending_bill_id AND shop_id = $shop_id";
                
                if (!mysqli_query($conn, $update_pending_query)) {
                    // Log error but don't fail the transaction
                    error_log("Failed to update pending bill status for ID: $pending_bill_id. Error: " . mysqli_error($conn));
                }
            }

            // Commit transaction
            mysqli_commit($conn);

            // Clear session data
            $_SESSION['current_bill'] = [];
            $_SESSION['loaded_pending_bill_id'] = null;

            // Redirect to print bill
            header("Location: print_bill.php?bill_id=" . $bill_id);
            exit;

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "❌ Error processing bill: " . $e->getMessage();
            
            // Log detailed error for debugging
            error_log("Bill Processing Error: " . $e->getMessage() . " | Customer: $customer_name, Mobile: $customer_mobile");
        }
    }
}
// --- Handle Clearing the Current Bill ---
if (isset($_POST['clear_bill'])) {
    $_SESSION['current_bill'] = [];
    $_SESSION['loaded_pending_bill_id'] = null; // Also clear loaded ID if bill is cleared
    
    // Clear any customer data that might be pre-filled
    $customer_name_prefill = '';
    $customer_mobile_prefill = '';
    $payment_method_prefill = 'cash';
    
    $success = "🧹 Current bill cleared.";
}

// --- Handle Removing Individual Item from Bill ---
if (isset($_POST['remove_item'])) {
    $item_index = intval($_POST['remove_item']);
    
    if (isset($_SESSION['current_bill'][$item_index])) {
        $removed_item = $_SESSION['current_bill'][$item_index];
        unset($_SESSION['current_bill'][$item_index]);
        $_SESSION['current_bill'] = array_values($_SESSION['current_bill']); // Reindex array
        $success = "❌ Item '" . htmlspecialchars($removed_item['name']) . "' removed from bill.";
    }
    
    // Clear any loaded pending bill ID if an item is removed
    $_SESSION['loaded_pending_bill_id'] = null;
}
// --- Fetch Pending Bills for Modal Display ---
// ... (existing PHP code before the HTML section) ...

// --- Fetch Pending Bills for Modal Display (Existing code, just ensuring it's there) ---
$pending_bills = [];
$pending_bills_query = mysqli_query($conn, "SELECT id, customer_name, customer_mobile, created_at FROM pending_bills WHERE shop_id = $shop_id AND status = 'pending' ORDER BY created_at DESC");
while ($pb_row = mysqli_fetch_assoc($pending_bills_query)) {
    $pending_bills[] = $pb_row;
}

// NEW: Get the count of pending bills
$pending_bills_count = count($pending_bills);

// ... (rest of your PHP code) ...

// --- Populate customer fields from GET parameters if a bill was just loaded ---
$customer_name_prefill = $_GET['customer_name'] ?? '';
$customer_mobile_prefill = $_GET['customer_mobile'] ?? '';
$payment_method_prefill = $_GET['payment_method'] ?? 'cash';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Section</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
       <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        /* Custom scrollbar for better aesthetics in tables if content overflows */
        .overflow-x-auto::-webkit-scrollbar {
            height: 8px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background-color: #cbd5e1; /* gray-300 */
            border-radius: 4px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background-color: #f8fafc; /* gray-50 */
        }
        tfoot {
            position: sticky;
            bottom: 0;
            background: #f9fafb;
        }

        /* Modal specific styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 2rem;
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-xl */
            width: 90%;
            max-width: 3xl; /* Equivalent to max-w-3xl */
            max-height: 90%;
            overflow-y: auto;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/tom-select.css">
</head>
<body>

    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-xl p-6 sm:p-8 border border-gray-100">
        <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between">
    
    <!-- Heading -->
    <h1 class="text-3xl font-extrabold text-indigo-700">
        🧾 Billing Counter
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

        <?php if ($show_gst || $show_license): ?>
            <div class="bg-indigo-50 p-4 rounded-lg mb-6 text-sm text-indigo-700 border border-indigo-200">
                <p class="flex items-center mb-1"><strong class="w-24 font-semibold">GST No:</strong> <?= htmlspecialchars($gst_number ?: "N/A") ?></p>
                <p class="flex items-center"><strong class="w-24 font-semibold">License:</strong> <?= htmlspecialchars($shop_license ?: "N/A") ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-800 px-5 py-3 rounded-lg mb-5 flex items-center space-x-3 border border-green-200 shadow-sm">
                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium"><?= $success ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 px-5 py-3 rounded-lg mb-5 flex items-center space-x-3 border border-red-200 shadow-sm">
                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <div class="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200 shadow-inner">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Add Item to Bill</h2>
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="product_selector" class="block text-sm font-medium text-gray-700 mb-1">Select Product:</label>
                    <select id="product_selector" name="product_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
                        <option value="">-- Select a product --</option>
                        <?php foreach ($all_products as $product_option): ?>
                            <option value="<?= htmlspecialchars($product_option['product_id']) ?>"
                                    data-is-decimal="<?= htmlspecialchars($product_option['is_decimal_quantity']) ?>"
                                    data-unit="<?= htmlspecialchars($product_option['unit_measurement']) ?>"
                                    data-internal-id="<?= htmlspecialchars($product_option['id']) ?>">
                                <?= htmlspecialchars($product_option['name']) ?> (ID: <?= htmlspecialchars($product_option['product_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity:</label>
                    <input id="quantity" name="quantity" type="number" step="any" min="0.01"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2" required>
                    <p id="quantity_hint" class="mt-1 text-sm text-gray-500 hidden">Enter whole numbers only.</p>
                </div>
                <button name="add_item" class="w-full bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 ease-in-out flex items-center justify-center">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add Item
                </button>
            </form>
        </div>

        <form method="POST" id="billingForm">
            <!-- Make the item list scrollable if there are many items -->
            <div class="overflow-x-auto rounded-lg shadow-md border border-gray-200 mb-6">
                <div class="overflow-y-auto" style="max-height: 340px; min-height: 120px;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100" style="position: sticky; top: 0; z-index: 2;">
    <tr>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product ID</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Base Price</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Disc. (%)</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Final Price/Unit</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
    </tr>
</thead>
                    <!-- In your HTML table body section -->
<tbody class="bg-white divide-y divide-gray-200">
    <?php $total = 0; if (empty($_SESSION['current_bill'])): ?>
        <tr>
            <td colspan="9" class="p-6 text-center text-gray-500 text-base">No items added to the bill yet.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($_SESSION['current_bill'] as $index => $item): ?>
            <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($item['product_id_string']) ?></td>
                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($item['name']) ?>
                    <?php if (!empty($item['attributes_at_add_json'])):
                        $decoded_attrs = json_decode($item['attributes_at_add_json'], true);
                        if ($decoded_attrs): ?>
                            <br><small class="text-gray-600 italic">
                            <?php $attr_display = [];
                            foreach($decoded_attrs as $attr) {
                                $attr_display[] = htmlspecialchars($attr['name']) . ': ' . htmlspecialchars($attr['value']);
                            }
                            echo implode(', ', $attr_display);
                            ?>
                            </small>
                        <?php endif;
                    endif; ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">₹<?= number_format($item['base_price_at_add'], 2) ?></td>
                <td class="px-4 py-3 text-sm text-blue-700 font-semibold"><?= number_format($item['applied_discount_percent'], 2) ?>%</td>
                <td class="px-4 py-3 text-sm font-semibold text-green-700">₹<?= number_format($item['final_price_at_add'], 2) ?></td>
                <td class="px-4 py-3 text-sm text-center">
                    <?= rtrim(rtrim(number_format($item['quantity'], $item['is_decimal'] ? 2 : 0), '0'), '.') ?>
                </td>
                <td class="px-4 py-3 text-sm text-center"><?= htmlspecialchars($item['unit_measurement_at_add']) ?></td>
                <td class="px-4 py-3 text-sm font-semibold text-gray-900">₹<?= number_format($item['total'], 2); $total += $item['total']; ?></td>
                <td class="px-4 py-3 text-center">
                    <form method="POST" class="inline">
                        <button type="submit" name="remove_item" value="<?= $index ?>" 
                                class="text-red-600 hover:text-red-900 text-sm font-medium"
                                onclick="return confirm('Remove this item from the bill?')">
                            ❌
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>
                        <tfoot class="bg-gray-100 border-t border-gray-300">
                            <tr>
                                <td colspan="8" class="px-4 py-3 text-right text-lg font-bold text-gray-800">Grand Total:</td>
                                <td class="px-4 py-3 text-lg font-bold text-indigo-700">₹<?= number_format($total, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Customer Name:</label>
                    <input id="customer_name" name="customer_name" type="text" placeholder="Customer Name" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2"  value="<?= isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : $customer_name_prefill ?>">
                </div>
                <div>
                    <label for="customer_mobile" class="block text-sm font-medium text-gray-700 mb-1">Mobile Number:</label>
                    <input id="customer_mobile" name="customer_mobile" type="tel" placeholder="e.g., 9876543210" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2" minlength="10" maxlength="10" value="<?= isset($_POST['customer_mobile']) ? htmlspecialchars($_POST['customer_mobile']) : $customer_mobile_prefill ?>">
                </div>
            </div>
            <!-- Payment Method Section -->
<div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
    <h3 class="text-lg font-medium text-gray-700 mb-3">Payment Method</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="radio" name="payment_method" value="cash" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" checked>
            <span class="text-gray-700">Cash</span>
        </label>
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="radio" name="payment_method" value="card" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
            <span class="text-gray-700">Card</span>
        </label>
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="radio" name="payment_method" value="upi" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
            <span class="text-gray-700">UPI</span>
        </label>
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="radio" name="payment_method" value="online" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
            <span class="text-gray-700">Online</span>
        </label>
    </div>
</div>

            <div class="flex flex-wrap gap-4 justify-end">
                <button name="confirm_bill" class="inline-flex items-center justify-center bg-green-500 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 ease-in-out" <?= empty($_SESSION['current_bill']) ? 'disabled' : '' ?>>
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Confirm Bill
                </button>
                <button type="submit" name="save_pending_bill" class="inline-flex items-center justify-center bg-yellow-500 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition duration-200 ease-in-out" <?= empty($_SESSION['current_bill']) ? 'disabled' : '' ?>>
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Save as Pending
                </button>
                <button type="button" id="openPendingModalBtn" class="inline-flex items-center justify-center bg-purple-500 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-200 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.234 7.173 2.158-1.583m-1.5-1.5 1.583 2.158M12 17.25h.007v.008H12v-.008ZM12 15h.007v.008H12V15Zm.007-4.242A.533.533 0 0 1 12 10.5h.007V10.5H12a.533.533 0 0 1-.007.008Z" />
                    </svg>
                    Load Pending Bill <span id="pendingBillCount" class="ml-2 px-2 py-0.5 text-xs font-bold bg-white text-purple-600 rounded-full"></span>
                </button>
                <button type="submit" name="clear_bill" id="clearBillBtn" class="inline-flex items-center justify-center bg-red-500 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200 ease-in-out" <?= empty($_SESSION['current_bill']) ? 'disabled' : '' ?>>
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 11-2 0v6a1 1 0 112 0V8z" clip-rule="evenodd" />
                    </svg>
                    Clear Bill
                </button>
               
            </div>
        </form>

        <p class="mt-8 text-sm text-center text-gray-500 leading-relaxed">
            <?= htmlspecialchars($bill_footer ?: "Thank you for your purchase! This bill is electronically generated and includes all applicable taxes.") ?>
        </p>
    </div>

    <div id="pendingBillsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Load Pending Bill</h2>

            <div class="mb-4">
                <input type="text" id="pendingBillSearch" placeholder="Search by customer name or mobile..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <?php if (empty($pending_bills)): ?>
                <p class="text-gray-600 text-center py-8">No pending bills available at the moment.</p>
            <?php else: ?>
                <form method="POST" id="loadPendingBillForm">
                    <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm mb-6">
                        <table class="min-w-full divide-y divide-gray-200" id="pendingBillsTable">
                            <!-- Update the table headers in the pending bills modal -->
<thead class="bg-gray-50">
    <tr>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer Name</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mobile</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Payment</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created At</th>
        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
    </tr>
</thead>

<!-- And update the table rows -->
<tbody class="bg-white divide-y divide-gray-200">
    <?php foreach ($pending_bills as $pb_item): ?>
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($pb_item['id']) ?></td>
            <td class="px-4 py-3 text-sm text-gray-700 bill-search-data"><?= htmlspecialchars($pb_item['customer_name'] ?: 'N/A') ?></td>
            <td class="px-4 py-3 text-sm text-gray-700 bill-search-data"><?= htmlspecialchars($pb_item['customer_mobile'] ?: 'N/A') ?></td>
            <td class="px-4 py-3 text-sm text-gray-700"><?= strtoupper(htmlspecialchars($pb_item['payment_method'] ?? 'cash')) ?></td>
            <td class="px-4 py-3 text-sm text-gray-700"><?= date("M d, Y H:i", strtotime($pb_item['created_at'])) ?></td>
            <td class="px-4 py-3 text-center">
                <button type="submit" name="load_pending_bill" value="<?= htmlspecialchars($pb_item['id']) ?>"
                        class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-900">
                        Load
                </button>
                <input type="hidden" name="pending_bill_id_to_load" value="<?= htmlspecialchars($pb_item['id']) ?>">
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script> -->

<script src="../assets/js/tom-select.complete.min.js"></script>
<script>
    // --- Passed PHP variable to JavaScript for pending bill count ---
    const pendingBillCount = <?= $pending_bills_count ?>;

    // --- Tom Select Initialization ---
    document.addEventListener("DOMContentLoaded", function () {
        new TomSelect("#product_selector", {
            placeholder: "Search or select a product...",
            allowEmptyOption: true,
            maxOptions: 100,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });

        // Update pending bill count on the button
        const pendingBillCountSpan = document.getElementById('pendingBillCount');
        if (pendingBillCountSpan) {
            pendingBillCountSpan.textContent = `(${pendingBillCount})`;
            if (pendingBillCount === 0) {
                // Optional: Grey out or disable the Load Pending Bill button if no bills are pending
                // document.getElementById('openPendingModalBtn').setAttribute('disabled', 'true');
                // document.getElementById('openPendingModalBtn').classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    });

    // --- Product Selector and Quantity Logic ---
    document.getElementById('product_selector').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const quantityInput = document.getElementById('quantity');
        const quantityHint = document.getElementById('quantity_hint');

        if (selectedOption.value) {
            const isDecimalAllowed = selectedOption.dataset.isDecimal === '1';

            if (isDecimalAllowed) {
                quantityInput.step = "0.01"; // Allows up to two decimal places
                quantityInput.min = "0.01"; // Set a minimum for decimal quantities (e.g., 0.01 for very small amounts)
                quantityInput.value = 1.00; // Default to 1.00 for decimal products
                quantityHint.classList.add('hidden'); // Hide hint
            } else {
                quantityInput.step = "1"; // Force whole numbers
                quantityInput.min = "1"; // Minimum quantity is 1 for whole number items
                quantityInput.value = Math.max(1, Math.floor(quantityInput.value || 1)); // Default to 1 if empty or less than 1
                quantityHint.textContent = "Enter whole numbers only.";
                quantityHint.classList.remove('hidden'); // Show hint
            }
        } else {
            // If no product is selected ("-- Select a product --")
            quantityInput.value = ''; // Clear quantity
            quantityInput.step = "any"; // Reset step to default 'any'
            quantityInput.min = "0"; // Reset min to 0 or a very small number when no product is selected
            quantityHint.classList.add('hidden'); // Hide hint
        }
        quantityInput.focus();
    });

    // --- Message Fade Out ---
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.querySelector('.bg-green-100');
        const errorMessage = document.querySelector('.bg-red-100');

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

        // --- Prefill Customer Details (if redirected from loading a pending bill) ---
        const urlParams = new URLSearchParams(window.location.search);
        const customerNameParam = urlParams.get('customer_name');
        const customerMobileParam = urlParams.get('customer_mobile');

        if (customerNameParam) {
            document.getElementById('customer_name').value = decodeURIComponent(customerNameParam);
        }
        if (customerMobileParam) {
            document.getElementById('customer_mobile').value = decodeURIComponent(customerMobileParam);
        }
    });

    // --- Modal Logic ---
    const pendingBillsModal = document.getElementById('pendingBillsModal');
    const openPendingModalBtn = document.getElementById('openPendingModalBtn');
    const closeButton = pendingBillsModal.querySelector('.close-button');
    const pendingBillSearchInput = document.getElementById('pendingBillSearch');
    const pendingBillsTableBody = document.querySelector('#pendingBillsTable tbody');
    const pendingBillRows = pendingBillsTableBody ? pendingBillsTableBody.getElementsByTagName('tr') : [];

    function openModal() {
        pendingBillsModal.style.display = 'flex'; // Use flex to center
        // Clear search on open and reset filters
        pendingBillSearchInput.value = '';
        filterPendingBills();
    }

    function closeModal() {
        pendingBillsModal.style.display = 'none';
    }

    openPendingModalBtn.onclick = openModal;
    closeButton.onclick = closeModal;

    // Close the modal if the user clicks anywhere outside of the modal content
    window.onclick = function(event) {
        if (event.target == pendingBillsModal) {
            closeModal();
        }
    }

    // --- Filter Pending Bills Functionality ---
    if (pendingBillSearchInput) {
        pendingBillSearchInput.addEventListener('keyup', filterPendingBills);
    }

    function filterPendingBills() {
        const searchTerm = pendingBillSearchInput.value.toLowerCase();

        for (let i = 0; i < pendingBillRows.length; i++) {
            const row = pendingBillRows[i];
            const cells = row.querySelectorAll('.bill-search-data'); // Cells with customer name and mobile
            let found = false;
            
            // Check if any of the searchable cells contain the search term
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }

            if (found) {
                row.style.display = ''; // Show row
            } else {
                row.style.display = 'none'; // Hide row
            }
        }
    }


    // --- Clear Bill Button Confirmation ---
    // document.getElementById('clearBillBtn').addEventListener('click', function(event) {
    //     if (!confirm('Are you sure you want to clear the current bill? This action cannot be undone.')) {
    //         event.preventDefault(); // Stop the form submission
    //     }
    // });

    // Add this to your JavaScript section
document.getElementById('clearBillBtn').addEventListener('click', function(event) {
    if (confirm('Are you sure you want to clear the current bill? This action cannot be undone.')) {
        // Clear customer fields immediately for better UX
        document.getElementById('customer_name').value = '';
        document.getElementById('customer_mobile').value = '';
        // Reset payment method to default
        document.querySelector('input[name="payment_method"][value="cash"]').checked = true;
    } else {
        event.preventDefault(); // Stop the form submission
    }
});
</script>
</body>

</html>