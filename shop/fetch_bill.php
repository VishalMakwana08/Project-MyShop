    <?php
    // Ensure this file is accessed only via AJAX from a logged-in session
    require_once '../config.php'; // Adjust path if necessary

    // Ensure session is started for shop_id
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["shop_id"])) {
        // If not logged in or shop_id not set, return an error message
        http_response_code(403); // Forbidden
        echo '<p class="p-6 text-center text-red-500 text-lg">Access denied. Please log in.</p>';
        exit;
    }

    $shop_id = intval($_SESSION["shop_id"]); // Safely get shop_id

    // Sanitize and get filter parameters from GET request
    // Using mysqli_real_escape_string for all string inputs
    $search_param = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
    $start_date_param = isset($_GET['start']) ? mysqli_real_escape_string($conn, trim($_GET['start'])) : ''; // Corrected to use 'start'
    $end_date_param = isset($_GET['end']) ? mysqli_real_escape_string($conn, trim($_GET['end'])) : '';     // Corrected to use 'end'

    // Build the base SQL query
    $sql = "SELECT id, customer_name, customer_mobile, total_amount, created_at
            FROM bills
            WHERE shop_id = " . $shop_id; // shop_id is already intval'd

    $conditions = [];

    // Add search filter ONLY for Bill ID if provided
    if (!empty($search_param)) {
        // If Bill IDs are purely numeric and you want exact match:
        if (is_numeric($search_param)) {
            $conditions[] = "id = " . intval($search_param);
        } else {
            // If Bill IDs can be alphanumeric or you want partial match on ID:
            $conditions[] = "id LIKE '%" . $search_param . "%'";
        }
    }

    // Add date range filters if provided
    // Ensure date format matches 'YYYY-MM-DD HH:MM:SS' for comparison
    if (!empty($start_date_param)) {
        // Append ' 00:00:00' to ensure comparison from the start of the selected day
        $conditions[] = "created_at >= '" . $start_date_param . " 00:00:00'";
    }
    if (!empty($end_date_param)) {
        // Append ' 23:59:59' to ensure comparison up to the end of the selected day
        $conditions[] = "created_at <= '" . $end_date_param . " 23:59:59'";
    }

    // Append conditions to the SQL query
    if (count($conditions) > 0) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY created_at DESC"; // Order by most recent first

    // Execute the query using pure mysqli_query
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        http_response_code(500); // Internal Server Error
        echo '<p class="p-6 text-center text-red-500 text-lg">Database error: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
        mysqli_close($conn);
        exit;
    }

    $bills = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $bills[] = $row;
    }
    mysqli_free_result($result); // Free the result set

    // Close the connection
    mysqli_close($conn);

    // Now, generate the HTML table with Tailwind CSS classes
    if (empty($bills)) {
        echo '<p class="p-6 text-center text-gray-500 text-lg">No bills found for the selected criteria.</p>';
    } else {
        echo '<table class="min-w-full divide-y divide-gray-200">';
        echo '<thead class="bg-gray-100 sticky top-0">'; // Added sticky top-0 for fixed header
        echo '<tr>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">';
        echo '<input type="checkbox" id="check_all_bills" class="form-checkbox h-4 w-4 text-blue-600 rounded focus:ring-blue-500">'; // Added ID for JS
        echo '</th>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bill ID</th>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer Name</th>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mobile</th>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Amount</th>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>';
        echo '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="bg-white divide-y divide-gray-200">';
        foreach ($bills as $bill) {
            echo '<tr class="hover:bg-gray-50 transition duration-150 ease-in-out">';
            echo '<td class="px-4 py-3 text-sm">';
            echo '<input type="checkbox" name="bill_ids[]" value="' . htmlspecialchars($bill['id']) . '" class="form-checkbox h-4 w-4 text-red-600 rounded focus:ring-red-500">';
            echo '</td>';
            echo '<td class="px-4 py-3 text-sm font-medium text-gray-800">' . htmlspecialchars($bill['id']) . '</td>';
            echo '<td class="px-4 py-3 text-sm text-gray-700">' . htmlspecialchars($bill['customer_name']) . '</td>';
            echo '<td class="px-4 py-3 text-sm text-gray-600">' . htmlspecialchars($bill['customer_mobile']) . '</td>';
            echo '<td class="px-4 py-3 text-sm font-semibold text-green-700">₹' . number_format($bill['total_amount'], 2) . '</td>';
            echo '<td class="px-4 py-3 text-sm text-gray-500">' . date('Y-m-d H:i', strtotime($bill['created_at'])) . '</td>';
            echo '<td class="px-4 py-3 text-sm whitespace-nowrap">';
            echo '<a href="print_bill.php?id=' . htmlspecialchars($bill['id']) . '" class="text-blue-600 hover:underline mr-2">View</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
    ?>