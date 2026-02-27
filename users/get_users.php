<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$sql = "SELECT id, fullname, username, role, created_at 
        FROM users
        ORDER BY id DESC";

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
