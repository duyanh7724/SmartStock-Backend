<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$userId = intval($_GET["user_id"] ?? 0);

if ($userId > 0) {
    $sql = "
        SELECT *
        FROM customer_orders
        WHERE user_id = $userId
        ORDER BY id DESC
    ";
} else {
    $sql = "
        SELECT *
        FROM customer_orders
        ORDER BY id DESC
    ";
}

$result = $conn->query($sql);
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $orders
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
