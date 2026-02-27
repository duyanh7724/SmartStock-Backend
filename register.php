<?php
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$fullname = trim($data['fullname'] ?? '');
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

// tất cả tài khoản tự đăng ký đều là khách
$role = 'customer';

if ($fullname === '' || $username === '' || $password === '') {
    echo json_encode(["success" => false, "message" => "Vui lòng nhập đủ thông tin"]);
    exit;
}

// kiểm tra username trùng
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Tên đăng nhập đã tồn tại"]);
    exit;
}
$stmt->close();

// hash mật khẩu
$hash = password_hash($password, PASSWORD_BCRYPT);

// insert
$stmt = $conn->prepare(
    "INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("ssss", $fullname, $username, $hash, $role);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Đăng ký thành công",
        "data" => [
            "id" => $stmt->insert_id,
            "fullname" => $fullname,
            "username" => $username,
            "role" => $role
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống khi đăng ký"]);
}

$stmt->close();
$conn->close();
