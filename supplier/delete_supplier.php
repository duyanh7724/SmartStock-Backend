<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID không hợp lệ"]);
    exit;
}

// Kiểm tra có sản phẩm liên quan không
$check = $conn->prepare("SELECT id FROM product WHERE supplier_id = ?");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Nhà cung cấp đang được gán cho sản phẩm. Không thể xóa."
    ]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM supplier WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Xóa thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
