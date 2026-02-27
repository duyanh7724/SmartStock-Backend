<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);
$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");

if ($id <= 0 || $name === "") {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu"]);
    exit;
}

$stmt = $conn->prepare("UPDATE category SET name=?, description=? WHERE id=?");
$stmt->bind_param("ssi", $name, $description, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cập nhật danh mục thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
