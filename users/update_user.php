<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);

$id       = intval($data["id"] ?? 0);
$fullname = trim($data["fullname"] ?? "");
$role     = trim($data["role"] ?? "");
$password = trim($data["password"] ?? "");

if ($id <= 0 || $fullname === "" || $role === "") {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu"]);
    exit;
}

if ($password !== "") {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET fullname=?, role=?, password=? WHERE id=?");
    $stmt->bind_param("sssi", $fullname, $role, $hash, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET fullname=?, role=? WHERE id=?");
    $stmt->bind_param("ssi", $fullname, $role, $id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cập nhật user thành công"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
