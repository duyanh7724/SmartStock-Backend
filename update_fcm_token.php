<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once "../config.php"; // Chú ý đường dẫn trỏ đúng về file config.php

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data['user_id'] ?? 0);
$token = $data['fcm_token'] ?? '';

if ($user_id <= 0 || empty($token)) {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu"]);
    exit();
}

// Cập nhật token vào bảng users
$stmt = $conn->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
$stmt->bind_param("si", $token, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cập nhật token thành công"]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi DB: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>