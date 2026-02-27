<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$id          = $data["id"];
$name        = $data["name"];
$price       = $data["price"];
$quantity    = $data["quantity"];
$description = $data["description"];
$category_id = $data["category_id"] ?? null;
$supplier_id = $data["supplier_id"] ?? null;
$filename    = $data["image"] ?? "";
$imageBase64 = $data["image_base64"] ?? "";

if ($imageBase64 != "") {
    $uploadDir = "../../uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $uniqueName = time() . "_" . $filename;
    file_put_contents($uploadDir . $uniqueName, base64_decode($imageBase64));

    $filename = $uniqueName;
}

$stmt = $conn->prepare("
    UPDATE product 
    SET name=?, price=?, quantity=?, description=?, category_id=?, supplier_id=?, image=?
    WHERE id=?
");

$stmt->bind_param(
    "sdissssi",
    $name,
    $price,
    $quantity,
    $description,
    $category_id,
    $supplier_id,
    $filename,
    $id
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cập nhật thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
