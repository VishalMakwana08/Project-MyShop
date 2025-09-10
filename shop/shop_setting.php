<?php
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$success = $error = "";

// Default Settings Fetch
$query = "SELECT * FROM shop_settings WHERE shop_id = $shop_id LIMIT 1";
$result = mysqli_query($conn, $query);
$settings = mysqli_fetch_assoc($result);

// Initialize defaults if no record exists
if (!$settings) {
    $settings = [
        'show_gst' => 1,
        'show_license' => 1,
        'bill_footer' => 'Thank you for shopping with us!',
        'low_stock_threshold' => 5,
        'show_discount_items' => 1
    ];
    // Insert default
    $insert = "INSERT INTO shop_settings (shop_id) VALUES ($shop_id)";
    mysqli_query($conn, $insert);
}

// Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $show_gst = isset($_POST['show_gst']) ? 1 : 0;
    $show_license = isset($_POST['show_license']) ? 1 : 0;
    $footer = mysqli_real_escape_string($conn, $_POST['bill_footer']);
    $threshold = intval($_POST['low_stock_threshold']);
    $show_discount = isset($_POST['show_discount_items']) ? 1 : 0;

    $update = "UPDATE shop_settings SET show_gst = $show_gst, show_license = $show_license, bill_footer = '$footer',
                low_stock_threshold = $threshold, show_discount_items = $show_discount WHERE shop_id = $shop_id";

    if (mysqli_query($conn, $update)) {
        $success = "✅ Settings updated successfully.";
    } else {
        $error = "❌ Failed to update settings.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4">⚙ Shop Settings</h1>

    <?php if ($success) echo "<div class='bg-green-100 text-green-800 px-4 py-2 rounded mb-3'>$success</div>"; ?>
    <?php if ($error) echo "<div class='bg-red-100 text-red-800 px-4 py-2 rounded mb-3'>$error</div>"; ?>

    <form method="POST">
        <label class="flex items-center mb-2">
            <input type="checkbox" name="show_gst" class="mr-2" <?= $settings['show_gst'] ? 'checked' : '' ?>> Show GST Number on Bill
        </label>

        <label class="flex items-center mb-2">
            <input type="checkbox" name="show_license" class="mr-2" <?= $settings['show_license'] ? 'checked' : '' ?>> Show Shop License on Bill
        </label>

        <label class="flex items-center mb-2">
            <input type="checkbox" name="show_discount_items" class="mr-2" <?= $settings['show_discount_items'] ? 'checked' : '' ?>> Auto Show Discounted Items
        </label>

        <div class="mb-3">
            <label class="block mb-1 font-medium">Bill Footer Message</label>
            <textarea name="bill_footer" rows="2" class="w-full border rounded p-2"><?= htmlspecialchars($settings['bill_footer']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-medium">Low Stock Threshold</label>
            <input type="number" name="low_stock_threshold" min="1" class="w-full border rounded p-2" value="<?= $settings['low_stock_threshold'] ?>">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">💾 Save Settings</button>
        <a href="dashboard.php" class="ml-3 text-gray-600 underline">← Back to Dashboard</a>
    </form>
</div>
</body>
</html>
