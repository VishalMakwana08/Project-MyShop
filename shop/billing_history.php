<?php
require_once '../config.php';

// Start session if not already started
if (!$_SESSION["loggedin"]) {
    header("Location: login.php");
    exit;
}
$shop_id = intval($_SESSION["shop_id"]);
$success = $error="";

// Handle deletion
if (isset($_POST['delete_selected']) && isset($_POST['bill_ids'])) {
    // Start a transaction for safe deletion
    mysqli_begin_transaction($conn);
    try {
        $deleted_count = 0;
        foreach ($_POST['bill_ids'] as $bill_id_str) {
            $bill_id = intval($bill_id_str); // Sanitize input

            // First, check if this bill belongs to the current shop
            $stmt_check = $conn->prepare("SELECT id FROM bills WHERE id = ? AND shop_id = ?");
            $stmt_check->bind_param("ii", $bill_id, $shop_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Delete from bill_items first (to satisfy foreign key constraints)
                $stmt_items = $conn->prepare("DELETE FROM bill_items WHERE bill_id = ?");
                $stmt_items->bind_param("i", $bill_id);
                if (!$stmt_items->execute()) {
                    throw new Exception("Failed to delete bill items for bill ID: " . $bill_id);
                }
                $stmt_items->close();

                // Delete from bills
                $stmt_bills = $conn->prepare("DELETE FROM bills WHERE id = ? AND shop_id = ?");
                $stmt_bills->bind_param("ii", $bill_id, $shop_id);
                if (!$stmt_bills->execute()) {
                    throw new Exception("Failed to delete bill record for bill ID: " . $bill_id);
                }
                $stmt_bills->close();
                $deleted_count++;
            }
            $stmt_check->close();
        }
        mysqli_commit($conn);
        $success = "✅ " . $deleted_count . " selected bill(s) deleted.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "❌ Error deleting bills: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
       <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        /* Custom Keyframe Animations */
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.5s ease-out forwards;
        }

        /* Shimmer effect for loading */
        .loading-shimmer {
            background: linear-gradient(to right, #f3f4f6 8%, #e5e7eb 18%, #f3f4f6 33%);
            background-size: 800px 104px;
            animation: shimmer 1.5s infinite linear;
        }
        @keyframes shimmer {
            0% { background-position: -468px 0; }
            100% { background-position: 468px 0; }
        }

        /* Custom scrollbar for better aesthetics in tables */
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
        /* Custom scrollbar for the billing list (vertical) */
        .overflow-y-scrollable::-webkit-scrollbar {
            width: 8px; /* Width for vertical scrollbar */
        }
        .overflow-y-scrollable::-webkit-scrollbar-thumb {
            background-color: #cbd5e1; /* gray-300 */
            border-radius: 4px;
        }
        .overflow-y-scrollable::-webkit-scrollbar-track {
            background-color: #f8fafc; /* gray-50 */
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800 p-6 sm:p-8">

    <div class="max-w-6xl mx-auto bg-white p-6 sm:p-10 rounded-2xl shadow-xl border border-gray-100 animate-fade-in">
       <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between">
    
    <!-- Heading -->
            <h1 class="text-3xl font-extrabold text-blue-700 mb-8 text-center sm:text-left">📜 Billing History</h1>


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

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-800 px-5 py-3 rounded-lg mb-6 flex items-center space-x-3 border border-green-200 shadow-sm">
                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium"><?= $success ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 px-5 py-3 rounded-lg mb-6 flex items-center space-x-3 border border-red-200 shadow-sm">
                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6 mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200 shadow-inner">
            <div>
                <label for="search_bill_id" class="block text-sm font-medium text-gray-700 mb-1">Search Bill ID:</label>
                <input type="text" id="search_bill_id" placeholder="e.g., 1001" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base" oninput="fetchBills()">
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                <input type="date" id="start_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base" onchange="fetchBills()">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date:</label>
                <input type="date" id="end_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base" onchange="fetchBills()">
            </div>
            <div class="flex items-end">
                   <button type="button" onclick="clearFilters()" class="w-full bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg shadow-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition duration-200 ease-in-out flex items-center justify-center">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear Filters
                </button>
            </div>
        </div>

        <form method="POST" id="billForm">
            <div id="bills_list_container" class="overflow-x-auto overflow-y-scrollable relative" style="max-height: 300px;">
                <div id="bills_table" class="min-h-[200px] flex items-center justify-center text-gray-500 text-lg">
                    <div class="loading-shimmer w-full h-full p-6 text-center flex items-center justify-center">
                        <p class="text-gray-600">Loading billing history...</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 mt-8 justify-between sm:justify-start">
                <button type="submit" name="delete_selected" id="deleteSelectedBtn" class="inline-flex items-center justify-center bg-red-600 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200 ease-in-out"
                    onclick="return confirm('Are you sure you want to delete the selected bills permanently? This action cannot be undone.');" disabled>
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 11-2 0v6a1 1 0 112 0V8z" clip-rule="evenodd" />
                    </svg>
                    Delete Selected
                </button>

               
                 
              <button type="button" onclick="printSalesReport()" class="inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
    📊 Print Sales Report
</button> <a href="billing.php" class="inline-flex items-center justify-center  bg-blue-700 text-white font-bold px-6 py-2.5 rounded-lg shadow-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-800 transition duration-200 ease-in-out">
                  
                    🧾 Back To Billing
                </a>


            </div>
        </form>
    </div>
    <script>
       function printSalesReport() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;

    const params = new URLSearchParams();
    if (start) params.append('start', start);
    if (end) params.append('end', end);

    // Open in same tab
    window.location.href = `print_sales_report.php?${params.toString()}`;
}


    </script>

    <script>
        // Function to toggle all checkboxes
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll("input[name='bill_ids[]']");
            checkboxes.forEach(cb => cb.checked = source.checked);
            toggleDeleteButton();
        }

        // Function to enable/disable delete button based on selection
        function toggleDeleteButton() {
            const checkedCount = document.querySelectorAll("input[name='bill_ids[]']:checked").length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            if (deleteBtn) {
                deleteBtn.disabled = checkedCount === 0;
            }
        }

        // Function to clear all filter inputs
        function clearFilters() {
            document.getElementById('search_bill_id').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            fetchBills(); // Fetch bills after clearing filters
        }

        // Function to fetch bills via AJAX
        function fetchBills() {
            const search = document.getElementById('search_bill_id').value;
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            const billsTableDiv = document.getElementById("bills_table");

            // Show loading shimmer
            billsTableDiv.innerHTML = `
                <div class="loading-shimmer w-full h-full p-6 text-center flex items-center justify-center">
                    <p class="text-gray-600">Loading billing history...</p>
                </div>
            `;
            // Ensure delete button is disabled while loading or if no items are initially selected
            toggleDeleteButton();

            const xhr = new XMLHttpRequest();
            // Encode parameters properly, using 'search', 'start', 'end' as key names
            const params = new URLSearchParams({
                search: search,
                start: start, // Matches fetch_bill.php's expected 'start'
                end: end      // Matches fetch_bill.php's expected 'end'
            }).toString();

            xhr.open("GET", `fetch_bill.php?${params}`, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    billsTableDiv.innerHTML = this.responseText;

                    // Add event listeners to individual checkboxes after content is loaded
                    document.querySelectorAll("input[name='bill_ids[]']").forEach(cb => {
                        cb.addEventListener('change', toggleDeleteButton);
                    });
                    // Re-attach event listener for "Check All" checkbox if it exists
                    const checkAllCb = document.getElementById('check_all_bills');
                    if (checkAllCb) {
                        checkAllCb.addEventListener('change', function() {
                            toggleAll(this);
                        });
                    }
                    toggleDeleteButton(); // Initial check after loading
                } else {
                    billsTableDiv.innerHTML = `<p class="p-6 text-center text-red-500">Error loading bills. Please try again. (Status: ${xhr.status})</p>`;
                    toggleDeleteButton();
                }
            };
            xhr.onerror = function() {
                billsTableDiv.innerHTML = `<p class="p-6 text-center text-red-500">Network error. Could not load bills.</p>`;
                toggleDeleteButton();
            };
            xhr.send();
        }

        // Fetch bills on page load
        window.onload = function() {
           
            // If there's a success or error message from PHP, hide it after a few seconds
    const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    const urlParams = new URLSearchParams(window.location.search);
    const startParam = urlParams.get('start');
    const endParam = urlParams.get('end');
    const searchParam = urlParams.get('search');

            messages.forEach(msg => {
                setTimeout(() => {
                    msg.style.transition = 'opacity 0.5s ease-out';
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 500); // Remove after transition
                }, 4000); // Hide after 4 seconds
            });
            // ... inside window.onload = function() { ...

   // ... inside window.onload = function() { ...

   
    if (startParam) {
        document.getElementById('start_date').value = startParam;
    }
    if (endParam) {
        document.getElementById('end_date').value = endParam;
    }
    if (searchParam) {
        document.getElementById('search_bill_id').value = searchParam;
    }

    fetchBills();// This will now correctly use the date from the URL
    // ... rest of your window.onload code
};
    </script>
</body>
</html>