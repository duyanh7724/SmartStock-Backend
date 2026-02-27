<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["items"]) || !isset($input["user_id"])) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$user_id = intval($input["user_id"]);
$items   = $input["items"];

if ($user_id <= 0 || empty($items)) {
    echo json_encode(["success" => false, "message" => "Missing user or items"]);
    exit;
}

// BẮT ĐẦU TRANSACTION
$conn->begin_transaction();

try {

    // 1) Tạo phiếu xuất
    $stmt = $conn->prepare("
        INSERT INTO export_orders (created_at, user_id)
        VALUES (NOW(), ?)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $export_id = $stmt->insert_id;
    $stmt->close();

    // Chuẩn bị statement
    $insertDetail = $conn->prepare("
        INSERT INTO export_order_details (export_order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");

    $getQty = $conn->prepare("SELECT quantity FROM product WHERE id = ?");
    $updateQty = $conn->prepare("UPDATE product SET quantity = quantity - ? WHERE id = ?");

    // 2) Xử lý từng item
    foreach ($items as $it) {

        $pid   = intval($it["product_id"]);
        $qty   = intval($it["quantity"]);
        $price = floatval($it["price"]);

        if ($pid <= 0 || $qty <= 0) {
            throw new Exception("Dữ liệu không hợp lệ: product_id=$pid, qty=$qty");
        }

        // kiểm tra sản phẩm tồn tại
        $check = $conn->prepare("SELECT quantity FROM product WHERE id = ?");
        $check->bind_param("i", $pid);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$res) {
            throw new Exception("Sản phẩm ID $pid không tồn tại");
        }

        $stock = intval($res["quantity"]);

        if ($stock < $qty) {
            throw new Exception("Sản phẩm ID $pid không đủ tồn kho. Hiện có: $stock, yêu cầu: $qty");
        }

        // ghi chi tiết xuất
        $insertDetail->bind_param("iiid", $export_id, $pid, $qty, $price);
        $insertDetail->execute();

        // trừ tồn kho
        $updateQty->bind_param("ii", $qty, $pid);
        $updateQty->execute();
    }

    $insertDetail->close();
    $updateQty->close();

    // không lỗi → commit
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Xuất kho thành công",
        "export_id" => $export_id
    ]);

} catch (Exception $e) {

    // lỗi → rollback
    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "Lỗi xuất kho: " . $e->getMessage()
    ]);
}

$conn->close();
?>
