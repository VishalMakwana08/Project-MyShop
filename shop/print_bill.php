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
    <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../asset/css/tailwind.min.css" />
    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            * {
                color: black !important;
                print-color-adjust: exact;
            }

            body {
                background-color: #fff;
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: none;
            }

            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0;
                padding: 0;
                border-radius: 0;
                max-width: none;
            }

            table {
                border-collapse: collapse !important;
            }

            th, td {
                border: 1px solid #e5e7eb !important;
                font-size: 10px;
                padding: 4px;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
                page-break-inside: avoid;
            }
        }

        td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 text-gray-900">
    <div class="max-w-3xl mx-auto bg-white border border-gray-200 rounded-lg shadow-xl p-6 invoice-container">
        <header class="flex justify-between items-start mb-6 border-b pb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-700"><?= $shop_info['shop_name'] ?></h1>
                <p class="text-sm text-gray-600 mt-1"><?= $shop_info['address'] ?></p>
                <?php if (!empty($shop_info['gst_number']) && $shop_info['gst_number'] !== 'N/A'): ?>
                    <p class="text-sm mt-1">GSTIN: <strong class="font-semibold text-gray-700"><?= $shop_info['gst_number'] ?></strong></p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-800">INVOICE</h2>
                <p class="text-sm mt-2"><strong class="text-gray-700">Invoice No:</strong> <span class="font-mono text-blue-600">#<?= htmlspecialchars($bill_id) ?></span></p>
                <p class="text-sm mt-1"><strong class="text-gray-700">Date:</strong> <span class="font-mono"><?= date('d M Y h:i A', strtotime($bill['created_at'])) ?></span></p>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <p class="font-semibold text-gray-700">Billed To:</p>
                <p class="mt-1"><strong class="text-gray-600">Name:</strong> <?= htmlspecialchars($bill['customer_name']) ?></p>
                <p class="mt-1"><strong class="text-gray-600">Mobile:</strong> <?= htmlspecialchars($bill['customer_mobile']) ?></p>
            </div>
            <div class="text-right">
                <p class="font-semibold text-gray-700">Payment Method:</p>
                <p class="mt-1"><span class="uppercase"><?= htmlspecialchars($bill['payment_method'] ?? 'cash') ?></span></p>
            </div>
        </div>

        <table class="w-full text-sm mb-6 table-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 uppercase">Product Description</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 uppercase">Qty</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 uppercase">Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase">Base Price</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase">Disc. (%)</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase">Final Price/Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 uppercase">Total</th>
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
            <!-- Removed tfoot for totals, moved below table -->
        </table>

        <!-- Totals section only at the end, not repeated on every print page -->
        <div class="totals-section bg-blue-50 rounded-lg p-4 mt-2 mb-6" style="page-break-inside: avoid;">
            <div class="flex justify-end">
                <div class="text-right">
                    <div class="font-semibold text-gray-700">Subtotal:</div>
                    <div class="font-semibold text-gray-800">₹<?= number_format($subtotal, 2) ?></div>
                </div>
            </div>
            <div class="flex justify-end mt-2">
                <div class="text-right">
                    <div class="font-bold text-blue-800 text-lg">GRAND TOTAL:</div>
                    <div class="font-bold text-blue-800 text-xl">₹<?= number_format($bill['total_amount'], 2) ?></div>
                </div>
            </div>
        </div>

        <?php if ($shopInfo): ?>
            <div class="mt-8 text-sm text-center text-gray-700 border-t border-gray-300 pt-4">
                <p class="mb-2 font-semibold text-gray-800">Thank you for your business!</p>
                <p class="mb-2">This is an electronically generated invoice and does not require a signature. All prices are inclusive of applicable taxes.</p>
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