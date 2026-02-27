<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php"; // Chú ý đường dẫn trỏ về đúng file config

$sql = "SELECT * FROM bank_info WHERE id = 1";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode(["success" => true, "data" => $data]);
} else {
    // Nếu chưa có thì trả về rỗng
    echo json_encode(["success" => false, "message" => "Chưa có thông tin ngân hàng"]);
}
$conn->close();
?>