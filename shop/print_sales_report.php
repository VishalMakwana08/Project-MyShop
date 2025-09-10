<?php
include 'check_internet.php';
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

$shop_id = intval($_SESSION["shop_id"]);
$sql = "SELECT shop_name FROM shops WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $shopName = $row['shop_name'];
    
}
$from = isset($_GET['start']) ? $_GET['start'] : '';
$to   = isset($_GET['end']) ? $_GET['end'] : '';
$chart_type = isset($_GET['chart_type']) ? $_GET['chart_type'] : 'bar';

$whereClause = "WHERE b.shop_id = $shop_id";
if (!empty($from) && !empty($to)) {
    $from_dt = date('Y-m-d 00:00:00', strtotime($from));
    $to_dt   = date('Y-m-d 23:59:59', strtotime($to));
    $whereClause .= " AND b.created_at BETWEEN '$from_dt' AND '$to_dt'";
}

// **UPDATED QUERY FOR CONSOLIDATED DATA (FOR CHARTS & GRAND TOTALS)**
// This query now sums the profit directly from the bill_items table
$sql_consolidated = "
    SELECT
        bi.product_id,
        bi.product_name_at_sale AS product_name,
        SUM(bi.quantity) AS total_qty_sold,
        SUM(bi.total) AS total_sales,
        SUM(bi.total - (bi.product_cost_at_sale * bi.quantity)) AS total_profit
    FROM bill_items bi
    JOIN bills b ON b.id = bi.bill_id
    $whereClause
    GROUP BY bi.product_id, bi.product_name_at_sale
    ORDER BY total_qty_sold DESC
";


// **UPDATED QUERY FOR DETAILED DATA (FOR THE TABLE)**
// This query now fetches both product_price_at_sale and product_cost_at_sale
$sql_detailed = "
    SELECT
        bi.product_id,
        bi.product_name_at_sale AS product_name,
        SUM(bi.quantity) AS total_qty_sold,
        SUM(bi.total) AS total_sales,
        bi.product_price_at_sale AS selling_price_at_sale,
        bi.product_cost_at_sale AS cost_price_at_sale,
        bi.product_unit_measurement_at_sale AS unit_measurement,
        (bi.product_price_at_sale - bi.product_cost_at_sale) * SUM(bi.quantity) AS profit_per_sale_price_point,
        p.deleted_at IS NOT NULL AS is_soft_deleted
    FROM bill_items bi
    JOIN bills b ON b.id = bi.bill_id
    LEFT JOIN products p ON p.product_id = bi.product_id AND p.shop_id = b.shop_id
    $whereClause
    GROUP BY
        bi.product_id,
        bi.product_name_at_sale,
        bi.product_price_at_sale,
        bi.product_cost_at_sale,
        bi.product_unit_measurement_at_sale
    ORDER BY bi.product_id, bi.product_price_at_sale
";


$result_consolidated = mysqli_query($conn, $sql_consolidated);
$result_detailed = mysqli_query($conn, $sql_detailed);

if (!$result_consolidated || !$result_detailed) {
    error_log("Error fetching sales data: " . mysqli_error($conn));
    die("An error occurred while fetching sales data. Please try again later.");
}
// After your SQL queries, add this:

$sales_data = mysqli_fetch_all($result_consolidated, MYSQLI_ASSOC);
$detailed_sales_data = mysqli_fetch_all($result_detailed, MYSQLI_ASSOC);

// Initialize variables for most sold and most profitable products
$most_sold = null;
$most_profit = null;
$max_qty = 0;
$max_profit = -INF;

$grand_total = 0;
$grand_profit = 0;

// Process the consolidated data for charts and totals
foreach ($sales_data as $i => $row) {
    $qty = floatval($row['total_qty_sold']);
    $total_sales = floatval($row['total_sales']);
    $profit = floatval($row['total_profit'] ?? 0); // Use the profit directly from the query

    $grand_total += $total_sales;
    $grand_profit += $profit;

    $sales_data[$i]['profit'] = $profit;

    $display_product_name = htmlspecialchars($row['product_name']);
    $sales_data[$i]['display_product_name'] = $display_product_name;

    if ($qty > $max_qty) {
        $max_qty = $qty;
        $most_sold = htmlspecialchars($row['product_name']);
    }

    if ($profit > $max_profit) {
        $max_profit = $profit;
        $most_profit = htmlspecialchars($row['product_name']);
    }
}

if (is_null($most_sold) && !empty($sales_data)) {
    $most_sold = htmlspecialchars($sales_data[0]['product_name']);
    $max_qty = floatval($sales_data[0]['total_qty_sold']);
} elseif (is_null($most_sold)) {
    $most_sold = 'N/A';
    $max_qty = 0;
}

usort($sales_data, function($a, $b) {
    return $b['profit'] <=> $a['profit'];
});

if (is_null($most_profit) && !empty($sales_data)) {
    $most_profit = htmlspecialchars($sales_data[0]['product_name']);
    $max_profit = floatval($sales_data[0]['profit']);
} elseif (is_null($most_profit)) {
    $most_profit = 'N/A';
    $max_profit = 0;
}

// Process the detailed data for the table
foreach ($detailed_sales_data as $i => $row) {
    $total_sales = floatval($row['total_sales']);
    $qty = floatval($row['total_qty_sold']);
    $selling_price = floatval($row['selling_price_at_sale']);
    $cost_price = floatval($row['cost_price_at_sale']);

    // Re-calculate profit to handle edge cases and display
    $profit = ($selling_price - $cost_price) * $qty;

    $detailed_sales_data[$i]['profit'] = $profit;
    $detailed_sales_data[$i]['selling_price'] = $selling_price;
    $detailed_sales_data[$i]['cost_price'] = $cost_price;

    $display_product_name = htmlspecialchars($row['product_name']);
    $is_soft_deleted = (bool)$row['is_soft_deleted'];
    if ($is_soft_deleted) {
        $display_product_name .= ' <span class="text-gray-500 text-xs">(Deleted)</span>';
    }
    $detailed_sales_data[$i]['display_product_name'] = $display_product_name;
}
// --- New code to sort the detailed table data by profit ---
usort($detailed_sales_data, function($a, $b) {
    // Sort in descending order based on the 'profit' key
    // Using spaceship operator (<=>) for cleaner comparison
    return $b['profit'] <=> $a['profit'];
});

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <script src="../assets/js/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff; }
            h1, h2 { color: #1a202c !important; }
        }
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .chart-controls {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        .chart-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stats-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .chart-controls select {
    min-width: 120px;
}
    </style>
</head>
<body class="p-6 bg-gray-100 text-gray-800 font-sans min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 sm:p-10 rounded-xl shadow-lg border border-gray-200">
        <header class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-blue-700 mb-2 flex items-center justify-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                Sales Performance Report <span class="text-green-600"><?php echo htmlspecialchars($shopName); ?></span>
            </h1>
            <?php if (!empty($from) && !empty($to)): ?>
                <p class="text-lg text-gray-600 mt-4">
                    Report period: <span class="font-semibold text-indigo-700"><?= htmlspecialchars(date('d M, Y', strtotime($from))) ?></span> to <span class="font-semibold text-indigo-700"><?= htmlspecialchars(date('d M, Y', strtotime($to))) ?></span>
                </p>
            <?php else: ?>
                <p class="text-lg text-gray-600 mt-4">Showing sales data for all time.</p>
            <?php endif; ?>
        </header>

        <hr class="border-gray-200 mb-8">

        <!-- Summary Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="stats-card bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 p-6 rounded-xl shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800 mb-1">Total Sales</h3>
                        <p class="text-3xl font-bold text-blue-900">₹<?= number_format($grand_total, 2) ?></p>
                    </div>
                    <div class="text-blue-600 bg-blue-200 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="stats-card bg-gradient-to-r from-green-50 to-green-100 border border-green-200 p-6 rounded-xl shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-green-800 mb-1">Total Profit</h3>
                        <p class="text-3xl font-bold text-green-900">₹<?= number_format($grand_profit, 2) ?></p>
                    </div>
                    <div class="text-green-600 bg-green-200 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="stats-card bg-gradient-to-r from-purple-50 to-purple-100 border border-purple-200 p-6 rounded-xl shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-purple-800 mb-1">Products Sold</h3>
                        <p class="text-3xl font-bold text-purple-900"><?= count($sales_data) ?></p>
                    </div>
                    <div class="text-purple-600 bg-purple-200 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($most_sold || $most_profit): ?>
        <div class="grid md:grid-cols-2 gap-6 mb-10">
            <div class="bg-green-50 border border-green-300 text-green-800 p-6 rounded-xl shadow-md flex items-center space-x-4">
                <div class="flex-shrink-0 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-1">Most Sold Product:</h3>
                    <p class="text-lg"><?= htmlspecialchars($most_sold ?? 'N/A') ?> <span class="font-semibold">(<?= number_format($max_qty, ($max_qty == floor($max_qty) ? 0 : 2)) ?> units)</span></p>
                </div>
            </div>
            <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-6 rounded-xl shadow-md flex items-center space-x-4">
                <div class="flex-shrink-0 text-yellow-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V3m0 9v3m0-3c-1.11 0-2.08-.402-2.599-1M12 12V9m-6 2H3m12 0h3m-7.5 4h2.25c.51 0 .93.342 1.054.852L10.5 21l-2.25-1.5L7.5 14.25c-.124-.51-.544-.852-1.054-.852H5.25m-3 0H21" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-1">Most Profitable Product:</h3>
                    <p class="text-lg"><?= htmlspecialchars($most_profit ?? 'N/A') ?> <span class="font-semibold">(₹<?= number_format($max_profit, 2) ?> profit)</span></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Chart Controls -->
        <div class="chart-controls no-print mb-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold text-gray-700">Visualization Options</h2>
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label for="chart-type" class="block text-sm font-medium text-gray-700 mb-1">Chart Type</label>
                        <select id="chart-type" class="rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                            <option value="bar" <?= $chart_type === 'bar' ? 'selected' : '' ?>>Bar Chart</option>
                            <option value="pie" <?= $chart_type === 'pie' ? 'selected' : '' ?>>Pie Chart</option>
                            <option value="line" <?= $chart_type === 'line' ? 'selected' : '' ?>>Line Chart</option>
                        </select>
                    </div>
                    <div>
                        <label for="data-type" class="block text-sm font-medium text-gray-700 mb-1">Data View</label>
                        <select id="data-type" class="rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                            <option value="quantity">By Quantity</option>
                            <option value="profit">By Profit</option>
                        </select>
                    </div>
                    <!-- <div>
                        <label for="items-count" class="block text-sm font-medium text-gray-700 mb-1">Top Items</label>
                        <select id="items-count" class="rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                            <option value="5">Top 5</option>
                            <option value="10" selected>Top 10</option>
                            <option value="15">Top 15</option>
                            <option value="20">Top 20</option>
                        </select>
                    </div> -->
                    <div>
    <label for="items-count" class="block text-sm font-medium text-gray-700 mb-1">Top Items</label>
    <select id="items-count" class="rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
        <option value="5">Top 5</option>
        <option value="10" selected>Top 10</option>
        <option value="15">Top 15</option>
        <option value="20">Top 20</option>
        <option value="0">All Items</option> <!-- Add this line -->
    </select>
</div>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 mb-10 no-print chart-card">
            <h2 class="text-2xl font-bold text-center text-indigo-700 mb-6" id="chart-title">Top Products by Quantity</h2>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <h2 class="text-2xl font-semibold text-gray-800 mb-6 border-b pb-3">Detailed Sales Breakdown</h2>
        <div class="overflow-x-auto shadow-lg border border-gray-200 rounded-lg mb-10">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Product ID</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Product Name</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Quantity Sold</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Total Sales (₹)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Selling Price (₹)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Cost Price (₹)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Profit (₹)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($detailed_sales_data)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500 text-base">
                                No sales data available for the selected period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detailed_sales_data as $row): ?>
                        <tr class="hover:bg-indigo-50 even:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-700"><?= htmlspecialchars($row['product_id']) ?></td>
                            <td class="px-4 py-2 text-gray-800"><?= $row['display_product_name'] ?></td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                <?= number_format($row['total_qty_sold'], ($row['total_qty_sold'] == floor($row['total_qty_sold']) ? 0 : 2)) ?>
                                <?= htmlspecialchars($row['unit_measurement'] ?? '') ?>
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600">₹<?= number_format($row['total_sales'], 2) ?></td>
                            <td class="px-4 py-2 text-right text-gray-600">
                                <?= isset($row['selling_price']) ? '₹' . number_format($row['selling_price'], 2) : 'N/A' ?>
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600">
                                <?= isset($row['cost_price']) ? '₹' . number_format($row['cost_price'], 2) : 'N/A' ?>
                            </td>
                            <td class="px-4 py-2 text-right font-semibold <?= $row['profit'] >= 0 ? 'text-blue-700' : 'text-red-500' ?>">
                                <?= isset($row['profit']) ? '₹' . number_format($row['profit'], 2) : 'N/A' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-gray-700">Grand Totals:</td>
                        <td class="px-4 py-3 text-right text-gray-600">—</td>
                        <td class="px-4 py-3 text-right text-gray-600">—</td>
                        <td class="px-4 py-3 text-right text-blue-700">₹<?= number_format($grand_profit, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="text-center mt-12 mb-4 no-print">
            <button onclick="window.print()" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6m-6-4v4m6-4v4m-6-4H9m7-6H7m6 0H7m6 0a1 1 0 100-2H7a1 1 0 100 2h6z" />
                </svg>
                Print Report
            </button>
            <a href="billing_history.php" class="ml-6 inline-flex items-center px-8 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                </svg>
                Back to Billing History
            </a>
        </div>
    </div>

    <?php
    // Prepare data for the charts
    $topSold = array_slice($sales_data, 0, 10);
    usort($sales_data, function($a, $b) {
        return $b['profit'] <=> $a['profit'];
    });
    $topProfit = array_slice($sales_data, 0, 10);
    ?>
    <!-- Chart.js Script -->
<script>
        
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#4b5563';
        
        // Get data from PHP
        const soldLabels = <?= json_encode(array_column($topSold, 'product_name')) ?>;
        const soldData = <?= json_encode(array_map(fn($r) => round($r['total_qty_sold'], 2), $topSold)) ?>;
        
        const profitLabels = <?= json_encode(array_column($topProfit, 'product_name')) ?>;
        const profitData = <?= json_encode(array_map(fn($r) => round($r['profit'], 2), $topProfit)) ?>;
        
        // Initialize chart variables
        let salesChart;
        let chartType = '<?= $chart_type ?>';
        let dataType = 'quantity';
        let itemsCount = 10;
        
        // Function to update chart based on selections
        function updateChart() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Determine which data to use
            // const labels = dataType === 'quantity' ? 
            //     soldLabels.slice(0, itemsCount) : 
            //     profitLabels.slice(0, itemsCount);
                
            // const data = dataType === 'quantity' ? 
            //     soldData.slice(0, itemsCount) : 
            //     profitData.slice(0, itemsCount);
                
            // const label = dataType === 'quantity' ? 'Quantity Sold' : 'Profit (₹)';
            // const title = dataType === 'quantity' ? 
            //     `Top ${itemsCount} Products by Quantity` : 
            //     `Top ${itemsCount} Products by Profit`;
                
            // Determine which data to use
const labels = dataType === 'quantity' ? 
    (itemsCount === 0 ? soldLabels : soldLabels.slice(0, itemsCount)) : 
    (itemsCount === 0 ? profitLabels : profitLabels.slice(0, itemsCount));
    
const data = dataType === 'quantity' ? 
    (itemsCount === 0 ? soldData : soldData.slice(0, itemsCount)) : 
    (itemsCount === 0 ? profitData : profitData.slice(0, itemsCount));
    
const label = dataType === 'quantity' ? 'Quantity Sold' : 'Profit (₹)';
const title = dataType === 'quantity' ? 
    (itemsCount === 0 ? 'All Products by Quantity' : `Top ${itemsCount} Products by Quantity`) : 
    (itemsCount === 0 ? 'All Products by Profit' : `Top ${itemsCount} Products by Profit`);
            document.getElementById('chart-title').textContent = title;
            
            // Destroy existing chart if it exists
            if (salesChart) {
                salesChart.destroy();
            }
            
            // Chart configuration based on type
            let config;
            
            if (chartType === 'pie') {
                config = {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: data,
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.7)',
                                'rgba(16, 185, 129, 0.7)',
                                'rgba(245, 158, 11, 0.7)',
                                'rgba(139, 92, 246, 0.7)',
                                'rgba(236, 72, 153, 0.7)',
                                'rgba(239, 68, 68, 0.7)',
                                'rgba(234, 179, 8, 0.7)',
                                'rgba(6, 182, 212, 0.7)',
                                'rgba(131, 24, 67, 0.7)',
                                'rgba(5, 150, 105, 0.7)',
                                'rgba(107, 114, 128, 0.7)',
                                'rgba(55, 65, 81, 0.7)',
                                'rgba(251, 191, 36, 0.7)',
                                'rgba(22, 163, 74, 0.7)',
                                'rgba(192, 38, 211, 0.7)',
                                'rgba(217, 119, 6, 0.7)',
                                'rgba(37, 99, 235, 0.7)',
                                'rgba(225, 29, 72, 0.7)',
                                'rgba(234, 88, 12, 0.7)',
                                'rgba(101, 163, 13, 0.7)'
                            ],
                            borderColor: [
                                'rgba(59, 130, 246, 1)',
                                'rgba(16, 185, 129, 1)',
                                'rgba(245, 158, 11, 1)',
                                'rgba(139, 92, 246, 1)',
                                'rgba(236, 72, 153, 1)',
                                'rgba(239, 68, 68, 1)',
                                'rgba(234, 179, 8, 1)',
                                'rgba(6, 182, 212, 1)',
                                'rgba(131, 24, 67, 1)',
                                'rgba(5, 150, 105, 1)',
                                'rgba(107, 114, 128, 1)',
                                'rgba(55, 65, 81, 1)',
                                'rgba(251, 191, 36, 1)',
                                'rgba(22, 163, 74, 1)',
                                'rgba(192, 38, 211, 1)',
                                'rgba(217, 119, 6, 1)',
                                'rgba(37, 99, 235, 1)',
                                'rgba(225, 29, 72, 1)',
                                'rgba(234, 88, 12, 1)',
                                'rgba(101, 163, 13, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    font: {
                                        size: 12
                                    },
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (dataType === 'quantity') {
                                            label += context.raw + ' units';
                                        } else {
                                            label += '₹' + context.raw.toFixed(2);
                                        }
                                        return label;
                                    }
                                },
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                padding: 10,
                                cornerRadius: 4
                            }
                        }
                    }
                };
            } else if (chartType === 'line') {
                config = {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: data,
                            backgroundColor: dataType === 'quantity' ? 
                                'rgba(59, 130, 246, 0.2)' : 
                                'rgba(16, 185, 129, 0.2)',
                            borderColor: dataType === 'quantity' ? 
                                'rgba(59, 130, 246, 1)' : 
                                'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            pointBackgroundColor: dataType === 'quantity' ? 
                                'rgba(59, 130, 246, 1)' : 
                                'rgba(16, 185, 129, 1)',
                            pointBorderColor: '#fff',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (dataType === 'quantity') {
                                            label += context.raw + ' units';
                                        } else {
                                            label += '₹' + context.raw.toFixed(2);
                                        }
                                        return label;
                                    }
                                },
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                padding: 10,
                                cornerRadius: 4
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: '#e5e7eb'
                                },
                                ticks: {
                                    font: { size: 12 }
                                },
                                title: {
                                    display: true,
                                    text: 'Product Name',
                                    font: { size: 14, weight: 'bold' },
                                    color: '#4b5563'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#e5e7eb'
                                },
                                ticks: {
                                    font: { size: 12 }
                                },
                                title: {
                                    display: true,
                                    text: label,
                                    font: { size: 14, weight: 'bold' },
                                    color: '#4b5563'
                                }
                            }
                        }
                    }
                };
            } else {
                // Default to bar chart
                config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: data,
                            backgroundColor: dataType === 'quantity' ? 
                                'rgba(59, 130, 246, 0.7)' : 
                                'rgba(16, 185, 129, 0.7)',
                            borderColor: dataType === 'quantity' ? 
                                'rgba(59, 130, 246, 1)' : 
                                'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (dataType === 'quantity') {
                                            label += context.raw + ' units';
                                        } else {
                                            label += '₹' + context.raw.toFixed(2);
                                        }
                                        return label;
                                    }
                                },
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                padding: 10,
                                cornerRadius: 4
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: '#e5e7eb'
                                },
                                ticks: {
                                    font: { size: 12 }
                                },
                                title: {
                                    display: true,
                                    text: label,
                                    font: { size: 14, weight: 'bold' },
                                    color: '#4b5563'
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 12 }
                                },
                                title: {
                                    display: true,
                                    text: 'Product Name',
                                    font: { size: 14, weight: 'bold' },
                                    color: '#4b5563'
                                }
                            }
                        }
                    }
                };
            }
            
            // Create the chart
            salesChart = new Chart(ctx, config);
        }
        
        // Initial chart render
        updateChart();
        
        // Event listeners for controls
        document.getElementById('chart-type').addEventListener('change', function() {
            chartType = this.value;
            updateChart();
        });
        
        document.getElementById('data-type').addEventListener('change', function() {
            dataType = this.value;
            updateChart();
        });
        
        document.getElementById('items-count').addEventListener('change', function() {
            itemsCount = parseInt(this.value);
            updateChart();
        });
    </script> 
</body>
</html>