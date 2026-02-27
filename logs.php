<?php
// KHÔNG để khoảng trắng / BOM trước dòng này

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$response = [
    "success" => false,
    "message" => "",
    "data"    => []
];

try {
    // Bảng login_log hiện có: id, user_id, time, ip_address
    // Join với users để lấy username, fullname, role
    $sql = "
        SELECT 
            ll.id,
            ll.user_id,
            ll.time,
            ll.ip_address,
            u.username,
            u.fullname,
            u.role
        FROM login_log AS ll
        LEFT JOIN users AS u ON ll.user_id = u.id
        ORDER BY ll.time DESC
        LIMIT 200
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Query error: ' . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }

    $response['success'] = true;
} catch (Throwable $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
