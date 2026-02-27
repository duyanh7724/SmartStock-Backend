<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$id = intval($_GET["id"] ?? 0);

$sql = "SELECT d.id, d.product_id, d.quantity, d.price,
               p.name AS product_name
        FROM export_order_details d
        JOIN product p ON d.product_id = p.id
        WHERE d.export_order_id = $id";

$res = $conn->query($sql);

$list = [];
while ($row = $res->fetch_assoc()) {
    $list[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $list
], JSON_UNESCAPED_UNICODE);

$conn->close();
