<?php
// KHÔNG để khoảng trắng / BOM trước dòng này
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$response = [
    "success" => false,
    "message" => "",
    "data"    => []
];

// Chỉ cho phép GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response["message"] = "Method not allowed";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($userId <= 0) {
    $response["message"] = "Thiếu hoặc sai user_id";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // -------- 1. Thông tin user --------
    $stmtUser = $conn->prepare("
        SELECT id, fullname, username, role, created_at
        FROM users
        WHERE id = ?
    ");
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $userRes = $stmtUser->get_result();
    $user = $userRes->fetch_assoc();
    $stmtUser->close();

    if (!$user) {
        $response["message"] = "Không tìm thấy user";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------- 2. Lịch sử đăng nhập --------
    $stmtLog = $conn->prepare("
        SELECT id, time, ip_address
        FROM login_log
        WHERE user_id = ?
        ORDER BY time DESC
        LIMIT 50
    ");
    $stmtLog->bind_param("i", $userId);
    $stmtLog->execute();
    $logRes = $stmtLog->get_result();
    $logins = [];
    while ($row = $logRes->fetch_assoc()) {
        $logins[] = $row;
    }
    $stmtLog->close();

    // -------- 3. Phiếu nhập do user tạo --------
    $stmtImport = $conn->prepare("
        SELECT 
            io.id,
            io.created_at,
            SUM(iod.quantity) AS total_qty,
            SUM(iod.quantity * iod.unit_price) AS total_amount
        FROM import_orders AS io
        LEFT JOIN import_order_details AS iod
            ON iod.import_order_id = io.id
        WHERE io.user_id = ?
        GROUP BY io.id, io.created_at
        ORDER BY io.created_at DESC
        LIMIT 50
    ");
    $stmtImport->bind_param("i", $userId);
    $stmtImport->execute();
    $impRes = $stmtImport->get_result();
    $imports = [];
    while ($row = $impRes->fetch_assoc()) {
        $imports[] = $row;
    }
    $stmtImport->close();

    // -------- 4. Phiếu xuất do user tạo --------
    $stmtExport = $conn->prepare("
        SELECT 
            eo.id,
            eo.created_at,
            SUM(eod.quantity) AS total_qty,
            SUM(eod.quantity * eod.price) AS total_amount
        FROM export_orders AS eo
        LEFT JOIN export_order_details AS eod
            ON eod.export_order_id = eo.id
        WHERE eo.user_id = ?
        GROUP BY eo.id, eo.created_at
        ORDER BY eo.created_at DESC
        LIMIT 50
    ");
    $stmtExport->bind_param("i", $userId);
    $stmtExport->execute();
    $expRes = $stmtExport->get_result();
    $exports = [];
    while ($row = $expRes->fetch_assoc()) {
        $exports[] = $row;
    }
    $stmtExport->close();

    // -------- Kết quả --------
    $response["success"] = true;
    $response["data"] = [
        "user"    => $user,
        "logins"  => $logins,
        "imports" => $imports,
        "exports" => $exports,
    ];
} catch (Throwable $e) {
    http_response_code(500);
    $response["success"] = false;
    $response["message"] = "Lỗi server: " . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
