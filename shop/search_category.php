<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$search = isset($_GET['q']) ? trim($_GET['q']) : "";

$sql = "SELECT id, name FROM categories WHERE shop_id = ? ";
$params = [$shop_id];
$types = "i";

if ($search !== "") {
    $sql .= "AND name LIKE ? ";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$sql .= "ORDER BY name";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$cats = [];
while ($row = $result->fetch_assoc()) {
    $cats[] = $row;
}

header('Content-Type: application/json');
echo json_encode($cats);
