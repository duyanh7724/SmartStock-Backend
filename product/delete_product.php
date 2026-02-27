<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

// Lấy file ảnh để xóa
$imgQuery = $conn->prepare("SELECT image FROM product WHERE id = ?");
$imgQuery->bind_param("i", $id);
$imgQuery->execute();
$imgRes = $imgQuery->get_result()->fetch_assoc();

if ($imgRes && !empty($imgRes["image"])) {
    $file = "../../uploads/" . $imgRes["image"];
    if (file_exists($file)) unlink($file);
}

// Xóa sản phẩm
$stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Xóa sản phẩm thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
