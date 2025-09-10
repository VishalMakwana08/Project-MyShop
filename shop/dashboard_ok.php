<?php
// include 'check_internet.php';
require_once '../config.php';

// Start session if not already started (important for $_SESSION['shop_id'])
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION["loggedin"])) {
    header("location: login.php");
    exit;
}

$shop_id = intval($_SESSION["shop_id"]);
$owner_name = htmlspecialchars($_SESSION['owner_name']);

$success = $error = "";

// Fetch stats - These queries correctly filter out soft-deleted products.
$total_products = 0;
$low_stock_products = 0;

// Query for total products - EXCLUDES SOFT-DELETED PRODUCTS
$q1 = mysqli_query($conn, "SELECT COUNT(id) as count FROM products WHERE shop_id = $shop_id AND deleted_at IS NULL");
if ($r = mysqli_fetch_assoc($q1)) {
    $total_products = $r['count'];
}

// Query for low_stock_products - EXCLUDES SOFT-DELETED PRODUCTS
$q2 = mysqli_query($conn, "SELECT COUNT(id) as count FROM products WHERE shop_id = $shop_id AND stock_quantity < low_stock_threshold AND deleted_at IS NULL");
if ($r = mysqli_fetch_assoc($q2)) {
    $low_stock_products = $r['count'];
}

$q3 = mysqli_query($conn, "SELECT IFNULL(SUM(total_amount), 0) as total FROM bills WHERE shop_id = $shop_id");
$total_sales = ($r = mysqli_fetch_assoc($q3)) ? $r['total'] : 0;

$q4 = mysqli_query($conn, "SELECT IFNULL(SUM(total_amount), 0) as total FROM bills WHERE shop_id = $shop_id AND DATE(created_at) = CURDATE()");
$sales_today = ($r = mysqli_fetch_assoc($q4)) ? $r['total'] : 0;

$latest_products = [];
// IMPORTANT: ADDED 'AND deleted_at IS NULL' TO EXCLUDE SOFT-DELETED PRODUCTS from recent list
$q5 = mysqli_query($conn, "SELECT image_path,unit_measurement,product_id, name, price, stock_quantity, discount_percent, id, low_stock_threshold, is_decimal_quantity FROM products WHERE shop_id = $shop_id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($q5)) {
    $latest_products[] = $row;
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>MyShop Dashboard</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
                        <!-- <link rel="stylesheet" href="../tailwind/src/output.css" /> -->

    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"> -->
     <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
   <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom mobile menu transitions */
        .mobile-menu-container {
            transition: max-height 0.3s ease, opacity 0.3s ease, transform 0.3s ease;
            max-height: 0;
            opacity: 0;
            transform: translateY(-10px);
            overflow: hidden;
        }

        .mobile-menu-container.open {
            max-height: 500px; /* Adjust as needed for content */
            opacity: 1;
            transform: translateY(0);
        }

        /* Hamburger icon animation */
        .hamburger-line {
            transition: all 0.3s ease-in-out;
        }

        .hamburger-open .line-top {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-open .line-middle {
            opacity: 0;
        }

        .hamburger-open .line-bottom {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Message animation */
        @keyframes pulse-fade {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        .animate-pulse-fade {
            animation: pulse-fade 1.5s infinite alternate;
        }
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #60A5FA 0%, #3B82F6 100%); /* Softer blue gradient */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 1s ease-out; /* Fade out effect */
        }

        #splash-screen.fade-out {
            opacity: 0;
            visibility: hidden; /* Hide completely after fade */
        }

        .splash-text {
            font-size: 4rem; /* Adjust as needed */
            font-weight: 800; /* Extra bold */
            color: white;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            animation: pulse 2s infinite ease-in-out; /* Pulse animation for the text */
            display: flex;
        }

        .splash-text .myshop-blue {
            color: #DBEAFE; /* A lighter blue for contrast */
        }

        .splash-text .myshop-green {
            color: #A7F3D0; /* A lighter green for contrast */
        }

        /* Pulse animation for splash screen text */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }

        /* Optional: Spin animation for a subtle loading indicator */
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-top: 2rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Ensure main content is not visible until splash screen fades */
        body.loading > *:not(#splash-screen) {
            visibility: hidden;
        }
        body:not(.loading) > *:not(#splash-screen) {
            visibility: visible;
        }
        /* New CSS for content fade-in */
        .dashboard-content-fade-in {
            opacity: 0;
            transition: opacity 0.8s ease-in-out; /* Adjust timing as needed */
        }

        .dashboard-content-fade-in.show {
            opacity: 1;
        }

        /* Ensure main content is hidden initially, even before JS applies the class */
        body.loading main,
        body.loading header:not(.hidden), /* Keep header visible if you want it to fade in with content */
        body.loading footer,
        body.loading aside {
            opacity: 0;
        }
        /* Clock Widget Styles */
.clock-widget {
    transition: all 0.3s ease;
}

.clock-widget:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

#current-time {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

#current-date {
    font-family: 'Inter', sans-serif;
}

#current-day {
    font-family: 'Inter', sans-serif;
    text-transform: uppercase;
}
.card-value {
  font-size: 2.5rem;       /* default size */
  font-weight: bold;
  text-align: center;
  white-space: nowrap;     /* always one line */
  overflow: hidden;        /* prevent overflow */
}
    </style>
</head>

<body class="bg-gray-100 font-sans text-gray-800 antialiased loading">

    <div id="splash-screen">
        <div class="splash-text">
            <span class="myshop-blue">My</span><span class="myshop-green">Shop</span>
        </div>
        <div class="spinner"></div>
    </div>

    <div id="dashboard-main-content" class="dashboard-content-fade-in">
        <header class="bg-white shadow-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="text-blue-600 font-extrabold text-3xl tracking-tight">
                    My<span class="text-green-500">Shop</span>
                </div>
                <nav class="hidden md:flex space-x-8 items-center">
                    <a href="products.php" class="font-medium text-gray-700 hover:text-blue-600 transition duration-300 ease-in-out">Products</a>
                    <a href="billing.php" class="font-medium text-gray-700 hover:text-blue-600 transition duration-300 ease-in-out">Billing</a>
                    <a href="billing_history.php" class="font-medium text-gray-700 hover:text-blue-600 transition duration-300 ease-in-out">Sales Report</a>
                    <a href="category.php" class="font-medium text-gray-700 hover:text-blue-600 transition duration-300 ease-in-out">Category</a>
                    <a href="contact_us.php" class="font-medium text-gray-700 hover:text-blue-600 transition duration-300 ease-in-out flex items-center space-x-1">
                        <span>Contact Us</span>
                    </a>

                    <a href="profile.php" class="font-medium text-gray-700 hover:text-blue-600 transition duration-300 ease-in-out">Profile</a>
                    <a href="logout.php" class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-red-500 hover:bg-red-600 transition duration-300 ease-in-out">Logout</a>
                </nav>
                <div class="md:hidden flex items-center cursor-pointer" id="mobileMenuBtn" onclick="toggleMobileMenu()">
                    <div class="flex flex-col space-y-1.5">
                        <div class="w-7 h-0.5 bg-gray-700 hamburger-line line-top"></div>
                        <div class="w-7 h-0.5 bg-gray-700 hamburger-line line-middle"></div>
                        <div class="w-7 h-0.5 bg-gray-700 hamburger-line line-bottom"></div>
                    </div>
                </div>
            </div>

            <div class="md:hidden">
                <div id="mobileMenu" class="mobile-menu-container bg-white shadow-lg py-3">
                    <button class="block w-full text-right px-4 py-2 text-gray-600 hover:text-red-600" onclick="toggleMobileMenu()">✖ Close</button>
                    <a href="products.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition duration-150">Products</a>
                    <a href="billing.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition duration-150">Billing</a>
                    <a href="billing_history.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition duration-150">Sales Report</a>
                    <a href="category.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition duration-150">Category</a>
                    <a href="contact_us.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition duration-150">Contact Us</a>
                    <a href="profile.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition duration-150">Profile</a>
                    <a href="logout.php" class="block px-4 py-3 text-red-600 hover:bg-red-50 transition duration-150">Logout</a>

                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 py-10 space-y-12">

            <!-- <h1 class="text-4xl font-extrabold text-gray-900 mb-8 text-center lg:text-left">
                Welcome, <span class="text-blue-600"><?php echo $owner_name; ?>!</span>
            </h1> old code -->
            <!--new code -->
            <div class="flex flex-col lg:flex-row justify-between items-center mb-8 gap-4">
    <div>
        <h1 class="text-4xl font-extrabold text-gray-900 text-center lg:text-left">
            Welcome, <span class="text-blue-600"><?php echo $owner_name; ?>!</span>
        </h1>
    </div>
    
    <!-- Clock Widget -->
    <!-- Alternative Modern Clock Design -->
<div class="bg-gradient-to-br from-blue-600 to-purple-600 p-5 rounded-xl shadow-lg text-white min-w-[280px] clock-widget">
    <div class="text-center">
        <div id="current-time" class="text-4xl font-bold mb-2 font-mono"></div>
        <div id="current-date" class="text-sm opacity-90"></div>
        <div id="current-day" class="text-sm font-medium mt-2 opacity-90"></div>
    </div>
</div>
</div>

            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-7">
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition duration-300 ease-in-out cursor-pointer transform hover:-translate-y-1" onclick="window.location.href='products.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-600">Total Products</div>
                        <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <!-- <div class="text-5xl font-bold text-gray-900 card-value"><?php echo $total_products; ?></div> -->
                     <div class="text-5xl font-bold text-gray-900 card-value" data-value="<?= $total_products; ?>"></div>

                </div>

                <div onclick="window.location.href='products.php?lowstock=1'" class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition duration-300 ease-in-out cursor-pointer transform hover:-translate-y-1">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-600">Low Stock Products</div>
                        <svg class="h-6 w-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <!-- <div class="text-5xl font-bold text-gray-900 card-value"><?php echo $low_stock_products; ?></div> -->
                     <div class="text-5xl font-bold text-gray-900 card-value" data-value="<?= $low_stock_products; ?>"></div>

                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition duration-300 ease-in-out cursor-pointer transform hover:-translate-y-1" onclick="window.location.href='billing_history.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-600">Total Sales</div>
                        <svg class="h-6 w-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0v4m0-4h.01M19 12a7 7 0 11-14 0 7 7 0 0114 0zm-9 6h4m-4 0c-.828 0-1.5-.672-1.5-1.5V11a.5.5 0 01.5-.5h4a.5.5 0 01.5.5v5.5c0 .828-.672 1.5-1.5 1.5z"></path></svg>
                    </div>
                    <!-- <div class="text-5xl font-bold text-gray-900 card-value">₹<?= ($total_sale) ?></div> -->
                     <div class="text-5xl font-bold text-gray-900 card-value" data-value="<?= $total_sales; ?>"></div>

                </div>

              <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition duration-300 ease-in-out cursor-pointer transform hover:-translate-y-1"
           onclick="redirectToTodaySales()">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm font-semibold text-gray-600">Sales Today</div>
                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <!-- <div class="text-5xl font-bold text-gray-900 card-value">₹<?= ($sales_today) ?></div> -->
                                  <div class="text-5xl font-bold text-gray-900 card-value" data-value="<?= $sales_today; ?>"></div>

        </div>

        <script>
            function redirectToTodaySales() {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0'); // Month is 0-indexed
                const day = String(today.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;

                window.location.href = `billing_history.php?start=${formattedDate}&end=${formattedDate}`;
            }
        </script>
            </section>

            <hr class="border-gray-200">

            <section>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center justify-between">
                        <span>Recent Products</span>
                        <a href="products.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition duration-200 flex items-center space-x-1">
                            <span>View All</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </h2>
                  <!--  <div class="overflow-x-auto">
                        <table class="min-w-full text-left divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <?php foreach(['Image','ID','Name','Price','Discount (%)','Stock','Unit','Actions'] as $col): ?>
                                        <th class="px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider"><?php echo $col; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if(empty($latest_products)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-400 text-base">No recent products to display.</td>
                                    </tr>
                                <?php else: foreach($latest_products as $p): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-4 py-3"> <?php if (!empty($p['image_path']) && file_exists($p['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="h-14 w-14 object-cover rounded-md shadow-sm border border-gray-200">
                                        <?php else: ?>
                                            <div class="h-14 w-14 bg-gray-200 flex items-center justify-center rounded-md text-gray-500 text-xs text-center border border-gray-300 p-1">
                                                No Image
                                            </div>
                                        <?php endif; ?></td>
                                        <td class="px-5 py-4 text-sm font-medium text-gray-800"><?php echo htmlspecialchars($p['product_id']); ?></td>
                                        <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td class="px-5 py-4 text-sm text-gray-700">₹<?php echo number_format($p['price'],2); ?></td>
                                        <td class="px-5 py-4 text-sm text-gray-700"><?php echo $p['discount_percent']; ?>%</td>
                                           
                                        <td class="px-5 py-4 text-sm text-gray-700 <?php echo ($p['stock_quantity'] < $p['low_stock_threshold']) ? 'text-red-500 font-semibold' : ''; ?>">
                                     <td class="px-5 py-4 text-sm text-gray-700 <?php echo ($p['stock_quantity'] < $p['low_stock_threshold']) ? 'text-red-500 font-semibold' : ''; ?>">
                                   


                                         <?php
                                            // Display stock quantity based on is_decimal_quantity
                                            echo htmlspecialchars(
                                                $p['is_decimal_quantity'] == 0
                                                ? intval($p['stock_quantity'])
                                                : rtrim(rtrim(number_format($p['stock_quantity'], 2), '0'), '.')
                                            );
                                            ?>
                                        </td>
                                         <td class="px-5 py-4 text-sm text-gray-700"><?php echo $p['unit_measurement']; ?></td>
                                        <td class="px-5 py-4 text-sm whitespace-nowrap space-x-4">
                                            <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="text-blue-600 hover:text-blue-800 transition duration-150">Edit</a>
                                            <a href="delete_product.php?id=<?php echo $p['id']; ?>" onclick="return confirm('Are you sure you want to soft-delete this product? It will no longer appear in active lists.');" class="text-red-600 hover:text-red-800 transition duration-150">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>-->
                    <div class="overflow-x-auto">
    <table class="min-w-full text-left divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <?php 
                $columns = ['Image', 'ID', 'Name', 'Price', 'Discount (%)', 'Stock', 'Unit', 'Actions'];
                foreach ($columns as $col): ?>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <?php echo $col; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php if (empty($latest_products)): ?>
                <tr>
                    <td colspan="8" class="py-8 text-center text-gray-400 text-base">
                        No recent products to display.
                    </td>
                </tr>
            <?php else: foreach ($latest_products as $p): ?>
                <tr class="hover:bg-gray-50 transition duration-150">
                    <!-- Image -->
                    <td class="px-4 py-3">
                        <?php if (!empty($p['image_path']) && file_exists($p['image_path'])): ?>
                            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                                 class="h-14 w-14 object-cover rounded-md shadow-sm border border-gray-200">
                        <?php else: ?>
                            <div class="h-14 w-14 bg-gray-200 flex items-center justify-center rounded-md text-gray-500 text-xs text-center border border-gray-300 p-1">
                                No Image
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- ID -->
                    <td class="px-5 py-4 text-sm font-medium text-gray-800">
                        <?= htmlspecialchars($p['product_id']); ?>
                    </td>

                    <!-- Name -->
                    <td class="px-5 py-4 text-sm text-gray-700">
                        <?= htmlspecialchars($p['name']); ?>
                    </td>

                    <!-- Price -->
                    <td class="px-5 py-4 text-sm text-gray-700">
                        ₹<?= number_format($p['price'], 2); ?>
                    </td>

                    <!-- Discount -->
                    <td class="px-5 py-4 text-sm text-gray-700">
                        <?= htmlspecialchars($p['discount_percent']); ?>%
                    </td>

                    <!-- Stock -->
                    <td class="px-5 py-4 text-sm text-gray-700 <?php echo ($p['stock_quantity'] < $p['low_stock_threshold']) ? 'text-red-500 font-semibold' : ''; ?>">
                        <?php
                        echo htmlspecialchars(
                            $p['is_decimal_quantity'] == 0
                                ? intval($p['stock_quantity'])
                                : rtrim(rtrim(number_format($p['stock_quantity'], 2), '0'), '.')
                        );
                        ?>
                    </td>

                    <!-- Unit -->
                    <td class="px-5 py-4 text-sm text-gray-700">
                        <?= htmlspecialchars($p['unit_measurement']); ?>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-4 text-sm whitespace-nowrap space-x-4">
                        <a href="edit_product.php?id=<?= $p['id']; ?>" class="text-blue-600 hover:text-blue-800 transition duration-150">Edit</a>
                        <a href="delete_product.php?id=<?= $p['id']; ?>"
                           onclick="return confirm('Are you sure you want to soft-delete this product? It will no longer appear in active lists.');"
                           class="text-red-600 hover:text-red-800 transition duration-150">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

                    <div class="mt-8 flex flex-wrap gap-4 justify-center sm:justify-start">
                        <a href="add_product.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add New Product
                        </a>
                        <a href="products.php" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7z" />
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V5z" clip-rule="evenodd" />
                            </svg>
                            Manage Products
                        </a>
                    </div>
                </div>
            </section>

        </main>

        <footer class="bg-gray-800 mt-12 py-8 text-center text-gray-300 text-sm">
            <div class="max-w-7xl mx-auto px-4">
                &copy; <?php echo date("Y"); ?> MyShop. All rights reserved.
            </div>
        </footer>
    </div>
    <script>
        // Mobile menu toggle
        const mobileMenu = document.getElementById("mobileMenu");
        const mobileMenuBtn = document.getElementById("mobileMenuBtn");

        function toggleMobileMenu() {
            mobileMenu.classList.toggle("open");
            mobileMenuBtn.classList.toggle("hamburger-open");
        }

        document.addEventListener('click', function (event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target) && mobileMenu.classList.contains('open')) {
                toggleMobileMenu();
            }
        });
      
    
       // Splash screen - REVERTED TO USE SESSIONSTORAGE
        document.addEventListener("DOMContentLoaded", () => {
            const splashScreen = document.getElementById('splash-screen');
            const body = document.body;
            const dashboardMainContent = document.getElementById('dashboard-main-content');
            const hasPlayedSplash = sessionStorage.getItem('hasPlayedDashboardSplash');

            if (hasPlayedSplash) {
                // If splash has played, remove it immediately and show content
                splashScreen.remove();
                body.classList.remove('loading');
                dashboardMainContent.classList.add('show');
            } else {
                // If splash hasn't played, show it, then fade out and set session storage
                setTimeout(() => {
                    splashScreen.classList.add('fade-out');
                    splashScreen.addEventListener('transitionend', () => {
                        splashScreen.remove();
                        body.classList.remove('loading');
                        dashboardMainContent.classList.add('show');
                    }, { once: true });
                }, 2000); // 2 seconds
                sessionStorage.setItem('hasPlayedDashboardSplash', 'true');
            }
        });
// Clock Widget Functionality
function updateClock() {
    const now = new Date();
    
    // Format time (HH:MM:SS)
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const timeString = `${hours}:${minutes}:${seconds}`;
    
    // Format date (Day, Month Date, Year)
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    const dayName = days[now.getDay()];
    const monthName = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();
    
    const dateString = `${monthName} ${date}, ${year}`;
    
    // Update DOM elements
    document.getElementById('current-time').textContent = timeString;
    document.getElementById('current-date').textContent = dateString;
    document.getElementById('current-day').textContent = dayName;
}

// Initialize clock and update every second
function initClock() {
    updateClock(); // Initial update
    setInterval(updateClock, 1000); // Update every second
}

// Call initClock when DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClock);
} else {
    initClock();
}
       

// function resizeCardValues() {
//   document.querySelectorAll('.card-value').forEach(el => {
//     el.style.fontSize = "2.5rem"; // reset to default
//     let parentWidth = el.parentElement.offsetWidth - 20; // padding adjustment
//     while (el.scrollWidth > parentWidth && parseFloat(getComputedStyle(el).fontSize) > 10) {
//       el.style.fontSize = (parseFloat(getComputedStyle(el).fontSize) - 1) + "px";
//     }
//   });
// }

// // Run on page load
// window.addEventListener('load', resizeCardValues);
// // Run when window is resized
// window.addEventListener('resize', resizeCardValues);
    </script>

   <!-- <script>
function formatIndianNumber(num) {
  num = Number(num); // force numeric

  if (isNaN(num)) return "0"; // fallback if invalid

  if (num >= 10000000) {
    return (num / 10000000).toFixed(1).replace(/\.0$/, '') + 'Cr'; // Crore
  } else if (num >= 100000) {
    return (num / 100000).toFixed(1).replace(/\.0$/, '') + 'L'; // Lakh
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K'; // Thousand
  }
  return num.toString();
}

// Apply formatting to all card values
function updateCardValues() {
  document.querySelectorAll('.card-value').forEach(el => {
    let raw = el.getAttribute("data-value"); 
    
    // If not set, take innerText and remove commas/spaces
    if (!raw) {
      raw = el.textContent.trim().replace(/,/g, '');
      el.setAttribute("data-value", raw);
    }

    el.textContent = formatIndianNumber(raw);
  });
}

window.addEventListener('load', updateCardValues);
</script>
<script>
function formatIndianNumber(num) {
  num = parseFloat(num);
  if (isNaN(num)) return "0";

  if (num >= 10000000) {
    return (num / 10000000).toFixed(1).replace(/\.0$/, '') + 'Cr'; // Crore
  } else if (num >= 100000) {
    return (num / 100000).toFixed(1).replace(/\.0$/, '') + 'L'; // Lakh
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K'; // Thousand
  }
  return num.toString();
}

function updateCardValues() {
  document.querySelectorAll('.card-value').forEach(el => {
    let raw = el.getAttribute("data-value");
    if (raw !== null) {
      el.textContent = formatIndianNumber(raw);
    }
  });
}

window.addEventListener('load', updateCardValues);
</script> -->

<script>
function formatIndianNumber(num) {
  num = parseFloat(num);
  if (isNaN(num)) return "0";

  // Use Indian style grouping (12,34,567)
  return new Intl.NumberFormat('en-IN').format(num);
}

function updateCardValues() {
  document.querySelectorAll('.card-value').forEach(el => {
    let raw = el.getAttribute("data-value");
    if (raw !== null) {
      el.textContent = formatIndianNumber(raw);
    }
  });
}

window.addEventListener('load', updateCardValues);
</script>
<script>
function resizeCardValues() {
  document.querySelectorAll('.card-value').forEach(el => {
    el.style.fontSize = "2.5rem"; // reset
    while (el.scrollWidth > el.offsetWidth && parseFloat(el.style.fontSize) > 0.8) {
      el.style.fontSize = (parseFloat(el.style.fontSize) - 0.1) + "rem";
    }
  });
}

window.addEventListener('load', resizeCardValues);
window.addEventListener('resize', resizeCardValues);
</script>

</body>
</html>