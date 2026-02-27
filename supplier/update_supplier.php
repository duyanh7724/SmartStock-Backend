<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);
$code = trim($data["code"] ?? "");
$name = trim($data["name"] ?? "");
$address = trim($data["address"] ?? "");
$phone = trim($data["phone"] ?? "");

if ($id <= 0 || $code === "" || $name === "") {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu"]);
    exit;
}

// Check mã trùng với suppliers khác
$check = $conn->prepare("SELECT id FROM supplier WHERE code = ? AND id != ?");
$check->bind_param("si", $code, $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Mã nhà cung cấp đã tồn tại"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE supplier
    SET code=?, name=?, address=?, phone=?
    WHERE id=?
");
$stmt->bind_param("ssssi", $code, $name, $address, $phone, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cập nhật nhà cung cấp thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
