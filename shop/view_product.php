<?php
require_once '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-red-500'>Invalid product ID.</p>";
    exit;
}

$id = intval($_GET['id']);

// Fetch product info
$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<p class='text-gray-600'>No product found.</p>";
    exit;
}
$p = $result->fetch_assoc();
$stmt->close();

// Fetch product attributes
$attributes = [];
$stmt = $conn->prepare("SELECT attribute_name, attribute_value FROM product_attribute_values WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$attr_result = $stmt->get_result();
while ($row = $attr_result->fetch_assoc()) {
    $attributes[] = $row;
}
$stmt->close();
?>

<div class="space-y-4">
    <h2 class="text-2xl font-bold text-blue-700"><?= htmlspecialchars($p['name']) ?></h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <?php if (!empty($p['image_path']) && file_exists($p['image_path'])): ?>
                <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="Product Image" class="w-full rounded-lg border shadow-sm">
            <?php else: ?>
                <div class="w-full h-48 flex items-center justify-center bg-gray-100 border rounded-lg text-gray-400">No Image</div>
            <?php endif; ?>
        </div>
        <div class="space-y-2 text-gray-700 text-sm">
            <p><strong>Product ID:</strong> <?= htmlspecialchars($p['product_id']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></p>
            <p><strong>Price:</strong> ₹<?= number_format($p['price'], 2) ?></p>
            <p><strong>Discount:</strong> <?= floatval($p['discount_percent']) ?>%</p>
            <p><strong>Stock Quantity:</strong>
                <?= $p['is_decimal_quantity'] == 0 ? intval($p['stock_quantity']) : rtrim(rtrim(number_format($p['stock_quantity'], 2), '0'), '.') ?>
                <?= htmlspecialchars($p['unit_measurement'] ?? '') ?>
            </p>
            <p><strong>Low Stock Threshold:</strong> <?= htmlspecialchars($p['low_stock_threshold']) ?></p>
            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($p['description'])) ?></p>
        </div>
    </div>

    <?php if (!empty($attributes)): ?>
    <div class="mt-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Product Attributes</h3>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
            <?php foreach ($attributes as $attr): ?>
                <li><strong><?= htmlspecialchars($attr['attribute_name']) ?>:</strong> <?= htmlspecialchars($attr['attribute_value']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
