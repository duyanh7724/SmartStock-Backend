<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "../config.php";

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid order ID"]);
    exit;
}

$conn->begin_transaction();

try {
    // Xóa chi tiết
    $stmt = $conn->prepare("DELETE FROM customer_order_details WHERE customer_order_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Xóa đơn chính
    $stmt2 = $conn->prepare("DELETE FROM customer_orders WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();

    $conn->commit();

    echo json_encode(["success" => true, "message" => "Xóa đơn hàng thành công"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error: ".$e->getMessage()]);
}

$conn->close();
