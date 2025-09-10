<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bill_index'])) {
    $index = intval($_POST['bill_index']);
    $bill = $_SESSION['pending_bills'][$index] ?? null;

    if ($bill) {
        $_SESSION['resumed_bill'] = $bill;
        unset($_SESSION['pending_bills'][$index]);
        $_SESSION['pending_bills'] = array_values($_SESSION['pending_bills']); // re-index
        header("Location: billing_check.php");
        exit;
    }
}
?>
