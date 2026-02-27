<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$code = trim($data["code"] ?? "");
$name = trim($data["name"] ?? "");
$address = trim($data["address"] ?? "");
$phone = trim($data["phone"] ?? "");

if ($code === "" || $name === "") {
    echo json_encode(["success" => false, "message" => "Thiếu mã hoặc tên nhà cung cấp"]);
    exit;
}

// Kiểm tra trùng code
$check = $conn->prepare("SELECT id FROM supplier WHERE code = ?");
$check->bind_param("s", $code);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Mã nhà cung cấp đã tồn tại"]);
    exit;
}
$check->close();

// Insert
$stmt = $conn->prepare("INSERT INTO supplier (code, name, address, phone) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $code, $name, $address, $phone);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Thêm nhà cung cấp thành công",
        "id" => $stmt->insert_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
