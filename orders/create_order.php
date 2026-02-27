<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config.php";
require_once "../fcm_send.php"; // [QUAN TRỌNG] Gọi file bắn thông báo

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// ... (Phần kiểm tra dữ liệu) ...
if (!$data || !isset($data["items"])) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$fullname = trim($data["fullname"] ?? "");
$phone = trim($data["phone"] ?? "");
$address = trim($data["address"] ?? "");
$note = trim($data["note"] ?? "");
$paymentMethod = $data["payment_method"] ?? "vietqr";
$totalAmount = floatval($data["total_amount"] ?? 0);
$userId = intval($data["user_id"] ?? 0);
$items = $data["items"];

if ($fullname === "" || $phone === "" || $address === "" || empty($items)) {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin"]);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Insert đơn hàng
    $stmt = $conn->prepare("INSERT INTO customer_orders (fullname, address, phone, note, payment_method, total_amount, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("ssssddi", $fullname, $address, $phone, $note, $paymentMethod, $totalAmount, $userId);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // 2. Insert chi tiết & Trừ kho
    $insertDetail = $conn->prepare("INSERT INTO customer_order_details (customer_order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $updateStock = $conn->prepare("UPDATE product SET quantity = quantity - ? WHERE id = ?");

    foreach ($items as $it) {
        $pid = intval($it["product_id"]);
        $qty = intval($it["quantity"]);
        $price = floatval($it["price"]);
        
        $insertDetail->bind_param("iiid", $orderId, $pid, $qty, $price);
        $insertDetail->execute();

        $updateStock->bind_param("ii", $qty, $pid);
        $updateStock->execute();
    }
    $insertDetail->close();
    $updateStock->close();

    $conn->commit();

    // ====================================================
    // [PHẦN MỚI] GỬI THÔNG BÁO CHO ADMIN
    // ====================================================
    
    // Tìm token của Admin (người có role = 'admin')
    // Bạn cần đảm bảo trong CSDL có user role='admin' và đã đăng nhập app để có token
    $sqlAdmin = "SELECT fcm_token FROM users WHERE role = 'admin' LIMIT 1";
    $resAdmin = $conn->query($sqlAdmin);
    
    if ($resAdmin && $rowAdmin = $resAdmin->fetch_assoc()) {
        $adminToken = $rowAdmin['fcm_token'];
        
        // Nếu Admin đã đăng nhập app và có token
        if (!empty($adminToken)) {
            $title = "📦 Đơn hàng mới #$orderId";
            $msg   = "Khách $fullname vừa đặt đơn: " . number_format($totalAmount) . " đ";
            
            // Gọi hàm gửi từ fcm_send.php
            sendPushNotification($adminToken, $title, $msg);
        }
    }
    // ====================================================

    echo json_encode(["success" => true, "data" => ["order_id" => $orderId]]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conn->close();
?>