<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");

if ($name === "") {
    echo json_encode(["success" => false, "message" => "Tên danh mục không được rỗng"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO category (name, description) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $description);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Thêm danh mục thành công",
        "id" => $stmt->insert_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
