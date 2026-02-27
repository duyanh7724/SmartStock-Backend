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
    echo json_encode(["success" => false, "message" => "User ID or items missing"]);
    exit;
}

// BẮT ĐẦU TRANSACTION
$conn->begin_transaction();

try {

    // 1) Tạo phiếu nhập
    $stmt = $conn->prepare("
        INSERT INTO import_orders (created_at, user_id)
        VALUES (NOW(), ?)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $import_id = $stmt->insert_id;
    $stmt->close();

    // Chuẩn bị statement
    $insertDetail = $conn->prepare("
        INSERT INTO import_order_details (import_order_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");

    $updateQty = $conn->prepare("
        UPDATE product SET quantity = quantity + ?
        WHERE id = ?
    ");

    // 2) Xử lý từng item
    foreach ($items as $it) {

        $pid  = intval($it["product_id"]);
        $qty  = intval($it["quantity"]);
        $price = floatval($it["unit_price"]);

        if ($pid <= 0 || $qty <= 0 || $price < 0) {
            throw new Exception("Dữ liệu không hợp lệ: product_id=$pid, qty=$qty");
        }

        // kiểm tra sản phẩm có tồn tại
        $check = $conn->prepare("SELECT id FROM product WHERE id = ?");
        $check->bind_param("i", $pid);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$res) {
            throw new Exception("Sản phẩm ID $pid không tồn tại");
        }

        // thêm chi tiết phiếu nhập
        $insertDetail->bind_param("iiid", $import_id, $pid, $qty, $price);
        $insertDetail->execute();

        // tăng tồn kho
        $updateQty->bind_param("ii", $qty, $pid);
        $updateQty->execute();
    }

    $insertDetail->close();
    $updateQty->close();

    // nếu không lỗi → commit
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Nhập kho thành công",
        "import_id" => $import_id
    ]);

} catch (Exception $e) {

    // có lỗi → rollback
    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "Lỗi nhập kho: " . $e->getMessage()
    ]);
}

$conn->close();
?>
