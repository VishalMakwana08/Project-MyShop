<?php
require_once '../config.php';
require_once 'libs/fpdf.php';

if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit;
}

$shop_id = $_SESSION["shop_id"];
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$whereClause = "WHERE b.shop_id = ?";
$params = [$shop_id];
$types = "i";

if (!empty($start)) {
    $whereClause .= " AND b.date >= ?";
    $params[] = $start;
    $types .= "s";
}
if (!empty($end)) {
    $whereClause .= " AND b.date <= ?";
    $params[] = $end;
    $types .= "s";
}

// Fetch product-wise sales data
$query = "
    SELECT p.name, SUM(bi.quantity) AS total_quantity, SUM(bi.quantity * bi.price) AS total_earning
    FROM bill_items bi
    JOIN bills b ON bi.bill_id = b.id
    JOIN products p ON bi.product_id = p.product_id AND p.shop_id = b.shop_id
    $whereClause
    GROUP BY p.name
    ORDER BY total_quantity DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$totalEarnings = 0;

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $totalEarnings += $row['total_earning'];
}
$stmt->close();

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Selling Report', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Date Range: $start to $end", 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 10, 'Product Name', 1);
$pdf->Cell(40, 10, 'Quantity Sold', 1);
$pdf->Cell(60, 10, 'Total Earnings (₹)', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($data as $item) {
    $pdf->Cell(80, 10, $item['name'], 1);
    $pdf->Cell(40, 10, $item['total_quantity'], 1);
    $pdf->Cell(60, 10, number_format($item['total_earning'], 2), 1);
    $pdf->Ln();
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(120, 10, 'Total Earnings', 1);
$pdf->Cell(60, 10, '₹ ' . number_format($totalEarnings, 2), 1);
$pdf->Output("D", "Selling_Report_{$start}_to_{$end}.pdf");
?>
