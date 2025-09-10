<?php

require_once '../config.php';

if (!$_SESSION['loggedin']) {
    exit;
}

$shop_id = $_SESSION['shop_id'];
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$low_stock = isset($_GET['lowstock']) && $_GET['lowstock'] == 1;

$query = "SELECT p.*, c.name AS category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.shop_id = ? AND p.deleted_at IS NULL";
$params = [$shop_id];
$types = "i";

if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}
if ($low_stock) {
    $query .= " AND p.stock_quantity < p.low_stock_threshold";
}
$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

foreach ($products as $p): ?>
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
<?php endforeach;
if (empty($products)): ?>
<tr>
    <td colspan="9" class="p-6 text-center text-gray-500 text-lg">
        No products found matching your criteria.
        <p class="mt-2 text-sm text-gray-400">Try adjusting your filters or <a
                href="add_product.php" class="text-blue-500 hover:underline">add a new
                product</a>.</p>
    </td>
</tr>
<?php endif; ?>