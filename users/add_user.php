<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$fullname = trim($data["fullname"] ?? "");
$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");
$role     = trim($data["role"] ?? "staff");

if ($fullname === "" || $username === "" || $password === "") {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin"]);
    exit;
}

// check trùng username
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Tên đăng nhập đã tồn tại"]);
    exit;
}
$check->close();

// hash mật khẩu
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $fullname, $username, $hash, $role);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Thêm user thành công",
        "id" => $stmt->insert_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
