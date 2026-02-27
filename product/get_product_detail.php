<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$id = intval($_GET['id'] ?? 0);
$baseUrl = "http://10.0.2.2/smartstock_api/uploads/"; // FIX EMULATOR

$stmt = $conn->prepare("SELECT * FROM product WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}

$data["image_url"] = $baseUrl . ($data["image"] ?: "no_image.jpg");

echo json_encode(["success" => true, "data" => $data]);
?>
