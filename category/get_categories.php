<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once "../config.php";

$sql = "SELECT id, name, description FROM category ORDER BY id DESC";
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
