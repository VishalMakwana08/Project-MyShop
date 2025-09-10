<?php
session_start();

// Check if the session variable exists
if (isset($_SESSION['current_bill'])) {
    // Clear the current bill
    $_SESSION['current_bill'] = [];
    // Clear customer name and mobile number
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_mobile']);
    echo json_encode(['success' => true, 'message' => 'Bill cleared.']);
} else {
    echo json_encode(['success' => false, 'message' => 'No bill to clear.']);
}
?>
