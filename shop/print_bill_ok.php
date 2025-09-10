<?php
// include 'check_internet.php';
require_once '../config.php'; // Ensure this path is correct for your setup

// Start session if not already started (important for $_SESSION['shop_id'])
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header("Location: login.php");
    exit;
}

$shop_id = $_SESSION['shop_id'];

// 🔹 NEW: Fetch shop details
$shopInfo = null;
$stmt = $conn->prepare("SELECT shop_name, owner_mobile, owner_email FROM shops WHERE id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $shopInfo = $result->fetch_assoc();
}
$stmt->close();

// Get bill_id from GET parameters
$bill_id = isset($_GET['bill_id']) ? intval($_GET['bill_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

// Validate bill_id
if ($bill_id <= 0) {
    echo "<div class='text-center p-4 text-red-600 bg-red-100 border border-red-200 rounded-md max-w-md mx-auto mt-10'>❌ Invalid Bill ID provided.</div>";
    exit;
}

$shop_id = $_SESSION['shop_id']; // Get shop_id from session

// Fetch bill details with payment method
$sql_fetch_bill = "SELECT customer_name, customer_mobile, total_amount, created_at, payment_method FROM bills WHERE id = ? AND shop_id = ?";
$stmt_bill = mysqli_prepare($conn, $sql_fetch_bill);
mysqli_stmt_bind_param($stmt_bill, "ii", $bill_id, $shop_id);
mysqli_stmt_execute($stmt_bill);
$result_bill = mysqli_stmt_get_result($stmt_bill);
$bill = mysqli_fetch_assoc($result_bill);
mysqli_stmt_close($stmt_bill);

// Check if bill exists and belongs to the current shop
if (!$bill) {
    echo "<div class='text-center p-4 text-red-600 bg-red-100 border border-red-200 rounded-md max-w-md mx-auto mt-10'>❌ Bill not found or does not belong to your shop.</div>";
    exit;
}

// Fetch bill items using the DENORMALIZED historical data
$items = [];
$sql_fetch_items = "SELECT
    bi.product_id,
    bi.quantity,
    bi.price AS final_unit_price_at_sale,
    bi.total,
    bi.product_name AS product_name,
    bi.product_price_at_sale AS original_product_price,
    bi.discount AS applied_discount_percent,
    bi.product_is_decimal_quantity_at_sale AS is_decimal_quantity,
    bi.product_unit_measurement_at_sale AS unit_measurement,
    bi.product_attributes_at_sale AS attributes_json
FROM bill_items bi
WHERE bi.bill_id = ?";

$stmt_items = mysqli_prepare($conn, $sql_fetch_items);
mysqli_stmt_bind_param($stmt_items, "i", $bill_id);
mysqli_stmt_execute($stmt_items);
$result_items = mysqli_stmt_get_result($stmt_items);

while ($item_row = mysqli_fetch_assoc($result_items)) {
    $item_row['attributes_display'] = [];
    if (!empty($item_row['attributes_json'])) {
        $decoded_attrs = json_decode($item_row['attributes_json'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_attrs)) {
            foreach ($decoded_attrs as $attr) {
                if (isset($attr['name']) && isset($attr['value'])) {
                    $item_row['attributes_display'][] = "{$attr['name']}: {$attr['value']}";
                }
            }
        }
    }
    $items[] = $item_row;
}
mysqli_stmt_close($stmt_items);

// Calculate subtotal
$subtotal = array_sum(array_column($items, 'total'));

// Fetch shop info
$shop_info = ['shop_name' => 'Your Shop Name', 'gst_number' => 'N/A', 'shop_license' => 'N/A', 'address' => 'Your Shop Address, City, State - PIN'];
$sql_fetch_shop_info = "SELECT shop_name, gst_number, shop_license, address FROM shops WHERE id = ?";
$stmt_shop_info = mysqli_prepare($conn, $sql_fetch_shop_info);
mysqli_stmt_bind_param($stmt_shop_info, "i", $shop_id);
mysqli_stmt_execute($stmt_shop_info);
$result_shop_info = mysqli_stmt_get_result($stmt_shop_info);

if ($result_shop_info && mysqli_num_rows($result_shop_info)) {
    $fetched_shop_info = mysqli_fetch_assoc($result_shop_info);
    $shop_info['shop_name'] = htmlspecialchars($fetched_shop_info['shop_name'] ?? 'N/A');
    $shop_info['gst_number'] = htmlspecialchars($fetched_shop_info['gst_number'] ?? 'N/A');
    $shop_info['shop_license'] = htmlspecialchars($fetched_shop_info['shop_license'] ?? 'N/A');
    $shop_info['address'] = htmlspecialchars($fetched_shop_info['address'] ?? 'N/A');
}
mysqli_stmt_close($stmt_shop_info);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $bill_id ?> - <?= $shop_info['shop_name'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"> -->
      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        @media print {
            .no-print { display: none !important; }
            * { color: black !important; print-color-adjust: exact; }
            body { background-color: #fff; margin: 0; padding: 0; }
            .invoice-container { box-shadow: none !important; border: none !important; margin: 0; padding: 0; border-radius: 0; }
            table { border-collapse: collapse !important; }
            th, td { border: 1px solid #e5e7eb !important; }
            .text-blue-600 { color: #2563eb !important; }
            .text-gray-800 { color: #1f2937 !important; }
            .text-blue-700 { color: #1d4ed8 !important; }
        }
        table {
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 text-gray-900">
<div class="max-w-3xl mx-auto bg-white border border-gray-200 rounded-lg shadow-xl p-6 invoice-container">
    <div class="text-center border-b border-gray-300 pb-4 mb-6">
        <div class="flex items-center justify-center mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h1 class="text-4xl font-extrabold text-gray-800"><?= $shop_info['shop_name'] ?></h1>
        </div>
        <p class="text-sm text-gray-600 mt-1"><?= $shop_info['address'] ?></p>
        <?php if (!empty($shop_info['gst_number']) && $shop_info['gst_number'] !== 'N/A'): ?>
            <p class="text-sm mt-1">GSTIN: <strong class="font-semibold text-gray-700"><?= $shop_info['gst_number'] ?></strong></p>
        <?php endif; ?>
        <?php if (!empty($shop_info['shop_license']) && $shop_info['shop_license'] !== 'N/A'): ?>
            <p class="text-sm mt-1">License: <strong class="font-semibold text-gray-700"><?= $shop_info['shop_license'] ?></strong></p>
        <?php endif; ?>
        <h2 class="text-2xl font-bold text-blue-700 mt-4">TAX INVOICE</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 text-sm mb-6 bg-gray-50 p-4 rounded-md border border-gray-200">
        <p class="mb-2 sm:mb-0"><strong class="text-gray-700">Invoice No:</strong> <span class="font-mono text-blue-600">#<?= htmlspecialchars($bill_id) ?></span></p>
        <p class="sm:text-right mb-2 sm:mb-0"><strong class="text-gray-700">Date:</strong> <span class="font-mono"><?= date('d M Y h:i A', strtotime($bill['created_at'])) ?></span></p>
        <p class="col-span-2 mt-2"><strong class="text-gray-700">Customer Name:</strong> <?= htmlspecialchars($bill['customer_name']) ?></p>
        <p class="col-span-2 mt-1"><strong class="text-gray-700">Customer Mobile:</strong> <?= htmlspecialchars($bill['customer_mobile']) ?></p>
        <p class="col-span-2 mt-1"><strong class="text-gray-700">Payment Method:</strong> 
            <span class="font-medium uppercase">
                <?= htmlspecialchars($bill['payment_method'] ?? 'cash') ?>
                <?php if (($bill['payment_method'] ?? 'cash') === 'online'): ?>
                    (Paid)
                <?php endif; ?>
            </span>
        </p>
    </div>

    <table class="w-full text-sm mb-6 table-fixed">
        <thead class="bg-blue-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider w-2/5">Product Description</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-700 uppercase tracking-wider w-1/12">Qty</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-700 uppercase tracking-wider w-1/12">Unit</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase tracking-wider w-1/6">Base Price</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase tracking-wider w-1/12">Disc. (%)</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase tracking-wider w-1/6">Final Price/Unit</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase tracking-wider w-1/6">Total</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500 text-base">No items found for this bill.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr class="even:bg-gray-50">
                        <td class="px-4 py-3 align-top">
                            <span class="font-medium text-gray-800"><?= htmlspecialchars($item['product_name']) ?></span><br>
                            <small class="text-gray-500">ID: <?= htmlspecialchars($item['product_id']) ?></small>
                            <?php if (!empty($item['attributes_display'])): ?>
                                <br><small class="text-gray-600 italic">Attributes: <?= htmlspecialchars(implode(', ', $item['attributes_display'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center align-top">
                            <?= (isset($item['is_decimal_quantity']) && $item['is_decimal_quantity']) ? number_format($item['quantity'], 2) : intval($item['quantity']) ?>
                        </td>
                        <td class="px-4 py-3 text-center align-top"><?= htmlspecialchars($item['unit_measurement']) ?></td>
                        <td class="px-4 py-3 text-right align-top">₹<?= number_format($item['original_product_price'], 2) ?></td>
                        <td class="px-4 py-3 text-right align-top"><?= number_format($item['applied_discount_percent'], 1) ?>%</td>
                        <td class="px-4 py-3 text-right align-top">₹<?= number_format($item['final_unit_price_at_sale'], 2) ?></td>
                        <td class="px-4 py-3 text-right align-top font-semibold">₹<?= number_format($item['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="bg-blue-50">
            <tr>
                <td colspan="6" class="px-4 py-2 text-right font-semibold text-gray-700">Subtotal:</td>
                <td class="px-4 py-2 text-right font-semibold text-gray-800">₹<?= number_format($subtotal, 2) ?></td>
            </tr>
            <tr class="bg-blue-100">
                <td colspan="6" class="px-4 py-3 text-right font-bold text-blue-800 text-lg">GRAND TOTAL:</td>
                <td class="px-4 py-3 text-right font-bold text-blue-800 text-xl">₹<?= number_format($bill['total_amount'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($shopInfo): ?>
        <div class="mt-8 text-sm text-center text-gray-700 border-t border-gray-300 pt-4">
            <p class="mb-2 font-semibold text-gray-800">Thank you for your business!</p>
            <p class="mb-2">This is an electronically generated invoice and does not require a signature. All prices include applicable taxes.</p>
            <p>For queries, please contact us at 
                <span class="font-medium"><?= htmlspecialchars($shopInfo['owner_mobile']) ?></span> 
                or 
                <span class="font-medium"><?= htmlspecialchars($shopInfo['owner_email']) ?></span>.
            </p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6m-6-4v4m6-4v4m-6-4H9m7-6H7m6 0H7m6 0a1 1 0 100-2H7a1 1 0 100 2h6z" />
            </svg>
            Print Invoice
        </button>
        <a href="billing_history.php" class="ml-6 inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
            </svg>
            Back to History
        </a>
    </div>
</div>
</body>
</html>