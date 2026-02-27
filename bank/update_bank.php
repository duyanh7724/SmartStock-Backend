<?php
// api/bank/update_bank.php
include '../config.php'; // Kết nối CSDL

// Nhận dữ liệu từ Flutter
$bank_code = $_POST['bank_code'];       // Ví dụ: TCB, VCB
$bank_name = $_POST['bank_name'];       // Ví dụ: Techcombank
$account_number = $_POST['account_number'];
$account_name = $_POST['account_name'];
$id = $_POST['id'] ?? 1; // Mặc định update row ID = 1 (vì shop thường chỉ có 1 tk nhận tiền)

if (!$bank_code || !$account_number || !$account_name) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đủ thông tin']);
    exit();
}

$sql = "UPDATE bank_info SET bank_code = ?, bank_name = ?, account_number = ?, account_name = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $bank_code, $bank_name, $account_number, $account_name, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Cập nhật ngân hàng thành công']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $conn->error]);
}
?>