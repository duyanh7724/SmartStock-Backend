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
$check = $conn->prepare("SELECT id FROM product WHERE category_id = ?");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Danh mục đang có sản phẩm. Không thể xóa."
    ]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM category WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Xóa danh mục thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
