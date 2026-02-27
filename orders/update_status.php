<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../config.php";
require_once "../fcm_send.php"; // [MỚI] Import file gửi thông báo (đã tạo ở bước trước)

// Nhận dữ liệu từ Flutter
$raw = file_get_contents("php://input");

// Nếu body rỗng -> báo lỗi
if (!$raw || strlen(trim($raw)) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "EMPTY BODY"
    ]);
    exit;
}

// Loại BOM nếu có
$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

// Decode JSON
$data = json_decode($raw, true);

// JSON lỗi -> in ra để debug
if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "JSON DECODE FAILED",
        "raw" => $raw
    ]);
    exit;
}

$id = intval($data["id"] ?? 0);
$status = trim($data["status"] ?? "");

if ($id <= 0 || $status === "") {
    echo json_encode([
        "success" => false,
        "message" => "MISSING FIELDS",
        "data" => $data
    ]);
    exit;
}

// Cập nhật trạng thái
$stmt = $conn->prepare("UPDATE customer_orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // ========================================================
    // [PHẦN MỚI] GỬI THÔNG BÁO CHO KHÁCH HÀNG
    // ========================================================
    
    // 1. Tìm Token của khách hàng sở hữu đơn hàng này
    // JOIN bảng customer_orders với users để lấy fcm_token và tên khách
    $sqlUser = "SELECT u.fcm_token, u.fullname 
                FROM customer_orders o
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? 
                LIMIT 1";
                
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $id);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    
    if ($row = $resUser->fetch_assoc()) {
        $userToken = $row['fcm_token'];
        $userName = $row['fullname'];
        
        // Chỉ gửi nếu khách hàng có token (đã từng đăng nhập app trên điện thoại)
        if (!empty($userToken)) {
            $title = "";
            $body = "";

            // Tùy chỉnh nội dung thông báo theo trạng thái đơn hàng
            if ($status == 'approved') {
                $title = "✅ Đơn hàng #$id đã được duyệt!";
                $body = "Xin chào $userName, đơn hàng của bạn đã được Admin xác nhận và đang chuẩn bị giao.";
            } 
            else if ($status == 'rejected') {
                $title = "❌ Đơn hàng #$id bị từ chối";
                $body = "Rất tiếc, đơn hàng của bạn không thể thực hiện lúc này. Vui lòng liên hệ shop để biết thêm chi tiết.";
            } 
            else if ($status == 'completed') {
                $title = "🎉 Đơn hàng #$id giao thành công";
                $body = "Cảm ơn bạn đã mua sắm tại SmartStock! Hẹn gặp lại.";
            }

            // Gọi hàm gửi nếu có nội dung
            if ($title != "") {
                sendPushNotification($userToken, $title, $body);
            }
        }
    }
    $stmtUser->close();
    // ========================================================

    echo json_encode([
        "success" => true,
        "message" => "Cập nhật thành công và đã gửi thông báo"
    ], JSON_UNESCAPED_UNICODE);

} else {
    echo json_encode([
        "success" => false, 
        "message" => "Lỗi CSDL: " . $conn->error
    ]);
}

$conn->close();
?>