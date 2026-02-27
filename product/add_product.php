<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Xử lý preflight từ Flutter Web / Android
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config.php";

// Đọc JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON",
        "raw" => $input
    ]);
    exit;
}

// Lấy dữ liệu
$name        = $data["name"] ?? "";
$price       = floatval($data["price"] ?? 0);
$quantity    = intval($data["quantity"] ?? 0);
$description = $data["description"] ?? "";
$category_id = intval($data["category_id"] ?? 0);
$supplier_id = intval($data["supplier_id"] ?? 0);
$image       = $data["image"] ?? "no_image.jpg";  // filename từ upload_image.php

// Validate dữ liệu
if ($name === "" || $price <= 0 || $quantity < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu tên, giá hoặc số lượng"
    ]);
    exit;
}

// Chuẩn bị query
$stmt = $conn->prepare("
    INSERT INTO product (name, price, quantity, description, image, category_id, supplier_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sdissii",
    $name,
    $price,
    $quantity,
    $description,
    $image,
    $category_id,
    $supplier_id
);

// Thực thi
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Thêm sản phẩm thành công",
        "id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "DB Error: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
