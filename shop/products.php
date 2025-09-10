<?php
// include 'check_internet.php';
require_once '../config.php';

if (!$_SESSION['loggedin']) {
    header("location: login.php");
    exit;
}

$shop_id = $_SESSION['shop_id'];
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$low_stock = isset($_GET['lowstock']) && $_GET['lowstock'] == 1;

// Fetch categories
$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories WHERE shop_id=? ORDER BY name");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

// Fetch products with filters
$products = [];
// Select p.low_stock_threshold to use it for display and dynamic low stock checking
$query = "SELECT p.*, c.name AS category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.shop_id = ? AND p.deleted_at IS NULL"; // <-- ADDED THIS LINE FOR SOFT DELETION FILTERING
$params = [$shop_id];
$types = "i";

if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($low_stock) {
    // Condition to check against the product's own low_stock_threshold
    $query .= " AND p.stock_quantity < p.low_stock_threshold";
    // No new parameter needed here, as we're comparing two columns
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// Close the database connection (important!)
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
              <!-- <link rel="stylesheet" href="../tailwind/src/output.css" /> -->

    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"> -->
          <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar for better aesthetics */
        .custom-scroll-y::-webkit-scrollbar {
            width: 8px;
            /* For vertical scrollbar */
        }

        .custom-scroll-y::-webkit-scrollbar-thumb {
            background-color: #9ca3af;
            /* gray-400 */
            border-radius: 4px;
            border: 2px solid #f3f4f6;
            /* gray-100, to match container background */
        }

        .custom-scroll-y::-webkit-scrollbar-track {
            background-color: #f3f4f6;
            /* gray-100 */
            border-radius: 4px;
        }

        /* Ensure header and body columns align */
        th, td {
    min-width: 80px;
}

        /* Ensure table cells don't shrink excessively on small screens but still allow wrap */
        .table-cell-min-width {
            min-width: 80px;
            /* Adjust as needed */
        }

        .table-name-min-width {
            min-width: 150px;
            /* For product name */
        }

        .table-actions-min-width {
            min-width: 120px;
            /* For action buttons */
        }
        #productModal {
        backdrop-filter:blur(4px);
        }

        table {
    table-layout: fixed;
    width: 100%;
}
    </style>
    
</head>

<body class="bg-gray-100 text-gray-800 font-sans antialiased min-h-screen py-8">

    <div class="max-w-7xl mx-auto bg-white p-6 sm:p-10 rounded-2xl shadow-xl border border-gray-200">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-6">
            <h1 class="text-3xl font-extrabold text-blue-700 tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                All Products
            </h1>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <a href="add_product.php"
                    class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Add New Product
                </a>
               <a href="dashboard.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg shadow-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition duration-200 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    Dashboard
                </a>
                 <a href="logout.php" class="px-3 py-1.5 bg-red-600 text-white rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out flex items-center gap-2 text-sm">
            Logout
        </a>
            </div>
        </div>

        <hr class="border-gray-200 mb-8">

        <?php if ($low_stock): ?>
            <div
                class="mb-6 p-4 bg-amber-50 text-amber-800 rounded-lg border border-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between shadow-sm">
                <span class="flex items-center space-x-2 text-base font-medium mb-2 sm:mb-0">
                    <svg class="h-6 w-6 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>You're viewing **low stock** products.</span>
                </span>
                <a href="products.php<?= $category_id > 0 ? '?category_id=' . $category_id : '' ?>"
                    class="text-blue-600 hover:text-blue-800 font-medium underline transition duration-200">
                    Clear Filter
                </a>
            </div>
        <?php endif; ?>

        <form id="filterForm" class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200 shadow-inner" onsubmit="return false;">
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <label for="category_select" class="font-semibold text-gray-700 whitespace-nowrap">Filter by
                    Category:</label>
                <select id="category_select" name="category_id"
                    class="w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base">
                    <option value="0" class="text-gray-600">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $category_id ? 'selected' : '') ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="flex-grow"></div>

                <button type="button" id="lowStockBtn" data-active="<?= $low_stock ? '1' : '0' ?>"
                    class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg shadow-sm text-sm font-medium transition duration-200 ease-in-out whitespace-nowrap">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                    Show Low Stock
                </button>
            </div>
        </form>

        <!-- Add this inside your main container, above the table -->
        <!-- <div class="mb-6 flex flex-col sm:flex-row items-center gap-4">
            <input 
                type="text" 
                id="productSearch" 
                placeholder="Search by Product ID or Name..." 
                class="w-full sm:w-96 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base"
                oninput="filterProducts()"
            >
        </div> -->
<div class="mb-6 flex flex-col sm:flex-row items-center gap-4 relative w-full sm:w-96">
    <input 
        type="text" 
        id="productSearch" 
        placeholder="Search by Product ID or Name..." 
        class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base"
        oninput="filterProducts()"
    >
    <!-- Clear Button -->
    <button 
        type="button" 
        id="clearSearchBtn" 
        class="absolute right-3 top-1/2 -translate-y-1/2 text-red-400 hover:text-red-600 hidden"
        onclick="clearSearch()"
    >
        ✕
    </button>
</div>      <!-- Replace your current table container with this structure -->
        <div class="rounded-lg shadow-md border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Image</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Product ID</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-name-min-width">
                            Name</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Category</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Price</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Discount (%)</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Stock</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-cell-min-width">
                            Unit</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider table-actions-min-width">
                            Actions</th>
                    </tr>
                </thead>
            </table>
            <div class="overflow-y-auto custom-scroll-y" style="max-height: 400px;">
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="p-6 text-center text-gray-500 text-lg">
                                    No products found matching your criteria.
                                    <p class="mt-2 text-sm text-gray-400">Try adjusting your filters or <a
                                            href="add_product.php" class="text-blue-500 hover:underline">add a new
                                            product</a>.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-4 py-3">
                                        <?php if (!empty($p['image_path']) && file_exists($p['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($p['image_path']) ?>"
                                                alt="<?= htmlspecialchars($p['name']) ?>"
                                                class="h-14 w-14 object-cover rounded-md shadow-sm border border-gray-200">
                                        <?php else: ?>
                                            <div
                                                class="h-14 w-14 bg-gray-200 flex items-center justify-center rounded-md text-gray-500 text-xs text-center border border-gray-300 p-1">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                        <?= htmlspecialchars($p['product_id']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($p['name']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold text-green-700">
                                        ₹<?= number_format($p['price'], 2) ?></td>
                                    <td class="px-4 py-3 text-sm text-purple-700"><?= floatval($p['discount_percent']) ?>%</td>
                                    <td
                                        class="px-4 py-3 text-sm font-semibold <?= $p['stock_quantity'] < $p['low_stock_threshold'] ? 'text-red-600' : 'text-gray-700' ?>">
                                        <?php
                                        // Display stock quantity based on is_decimal_quantity
                                        echo htmlspecialchars(
                                            $p['is_decimal_quantity'] == 0
                                            ? intval($p['stock_quantity'])
                                            : rtrim(rtrim(number_format($p['stock_quantity'], 2), '0'), '.')
                                        );
                                        ?>
                                        <?php if ($p['stock_quantity'] < $p['low_stock_threshold']): ?>
                                            <span class="text-xs text-red-500 ml-1">(Low!)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <?= htmlspecialchars($p['unit_measurement'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        <a href="#"
                                            class="text-indigo-600 hover:text-indigo-800 font-medium transition duration-150 mr-3"
                                            onclick="openProductModal(<?= $p['id'] ?>)">
                                            View
                                        </a>

                                        <a href="edit_product.php?id=<?= $p['id'] ?>"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition duration-150 mr-3">Edit</a>
                                        <a href="delete_product.php?id=<?= $p['id'] ?>"
                                            class="text-red-600 hover:text-red-800 font-medium transition duration-150"
                                            onclick="return confirm('Are you sure you want to soft-delete this product? It will be removed from active lists but kept for historical sales data.');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Product Detail Modal -->
<div id="productModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 relative">
        <button onclick="closeProductModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">
            ✖
        </button>
        <div id="productModalContent">
            <!-- Content will be loaded here dynamically -->
            <p class="text-gray-500 text-center">Loading product details...</p>
        </div>
    </div>
</div>

<script>
 
function openProductModal(id) {
    const modal = document.getElementById("productModal");
    const content = document.getElementById("productModalContent");
    modal.classList.remove("hidden");
    content.innerHTML = "<p class='text-gray-500 text-center'>Loading product details...</p>";

    fetch('view_product.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(() => {
            content.innerHTML = "<p class='text-red-500 text-center'>Failed to load product details.</p>";
        });
}

function closeProductModal() {
    document.getElementById("productModal").classList.add("hidden");
}

// Filter products based on search input
// function filterProducts() {
//     const search = document.getElementById('productSearch').value.trim().toLowerCase();
//     const rows = document.querySelectorAll('tbody tr');
//     rows.forEach(row => {
//         // Skip "no products found" row
//         if (row.querySelectorAll('td').length < 2) return;
//         const productId = row.children[1].textContent.trim().toLowerCase();
//         const productName = row.children[2].textContent.trim().toLowerCase();
//         if (productId.includes(search) || productName.includes(search)) {
//             row.style.display = '';
//         } else {
//             row.style.display = 'none';
//         }
//     });
// }
function filterProducts() {
    const input = document.getElementById('productSearch');
    const search = input.value.trim().toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    const clearBtn = document.getElementById('clearSearchBtn');

    // toggle clear button visibility
    clearBtn.classList.toggle('hidden', search === '');

    rows.forEach(row => {
        // Skip "no products found" row
        if (row.querySelectorAll('td').length < 2) return;
        const productId = row.children[1].textContent.trim().toLowerCase();
        const productName = row.children[2].textContent.trim().toLowerCase();
        if (productId.includes(search) || productName.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function clearSearch() {
    const input = document.getElementById('productSearch');
    input.value = '';
    document.getElementById('clearSearchBtn').classList.add('hidden');
    input.focus();
    filterProducts(); // reset table rows
}
function updateProductTable(params = {}) {
    const query = new URLSearchParams(params).toString();
    fetch('ajax_products.php?' + query)
        .then(response => response.text())
        .then(html => {
            document.querySelector('tbody').innerHTML = html;
            filterProducts(); // Re-apply search filter after update
        });
}

// Category filter (no reload)
document.getElementById('category_select').addEventListener('change', function() {
    updateProductTable({
        category_id: this.value,
        lowstock: document.getElementById('lowStockBtn').dataset.active === "1" ? 1 : 0
    });
});

// Low stock filter (no reload)
document.getElementById('lowStockBtn').addEventListener('click', function(e) {
    e.preventDefault();
    // Toggle active state
    this.dataset.active = this.dataset.active === "1" ? "0" : "1";
    updateProductTable({
        category_id: document.getElementById('category_select').value,
        lowstock: this.dataset.active
    });
});
</script>

</body>

</html>