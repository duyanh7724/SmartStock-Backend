<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "config.php"; // Kết nối CSDL

$data = json_decode(file_get_contents("php://input"), true);

// 1. Kiểm tra dữ liệu gửi lên
$email = $data['email'] ?? '';
$google_id = $data['google_id'] ?? '';
$fullname = $data['fullname'] ?? '';
$avatar = $data['avatar'] ?? '';
$fcm_token = $data['fcm_token'] ?? ''; // Token để bắn thông báo

if (empty($email) || empty($google_id)) {
    echo json_encode(["success" => false, "message" => "Thiếu email hoặc Google ID"]);
    exit();
}

// 2. Kiểm tra xem email đã tồn tại trong DB chưa
$checkStmt = $conn->prepare("SELECT id, fullname, username, role, avatar FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$result = $checkStmt->get_result();
$user = $result->fetch_assoc();
$checkStmt->close();

if ($user) {
    // === TRƯỜNG HỢP A: ĐÃ CÓ TÀI KHOẢN ===
    // Cập nhật lại google_id, avatar và fcm_token mới nhất
    $updateSql = "UPDATE users SET google_id = ?, avatar = ?, fcm_token = ? WHERE email = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ssss", $google_id, $avatar, $fcm_token, $email);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Đăng nhập Google thành công",
        "data" => [
            "id" => $user['id'],
            "fullname" => $user['fullname'],
            "username" => $user['username'],
            "role" => $user['role'],
            "avatar" => $avatar, // Trả về avatar mới nhất từ Google
            "email" => $email
        ]
    ]);

} else {
    // === TRƯỜNG HỢP B: CHƯA CÓ TÀI KHOẢN (TỰ ĐỘNG ĐĂNG KÝ) ===
    
    // Tự tạo username từ email (lấy phần trước @)
    $username = explode('@', $email)[0];
    
    // Kiểm tra xem username tự tạo có bị trùng không, nếu trùng thì thêm số ngẫu nhiên
    $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        $username = $username . rand(100, 999);
    }
    $checkUser->close();

    $role = 'customer'; // Mặc định là khách hàng
    $password = null;   // Google login không cần pass

    $insertSql = "INSERT INTO users (fullname, username, email, google_id, avatar, role, password, fcm_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertSql);
    // password là null, nên bind là 's' vẫn ok trong php hoặc xử lý riêng, 
    // nhưng an toàn nhất là ta không bind biến password mà để NULL trong câu query nếu driver kén chọn.
    // Tuy nhiên mysqli cho phép bind null.
    $stmt->bind_param("ssssssss", $fullname, $username, $email, $google_id, $avatar, $role, $password, $fcm_token);
    
    if ($stmt->execute()) {
        $newUserId = $stmt->insert_id;
        echo json_encode([
            "success" => true,
            "message" => "Đăng ký mới qua Google thành công",
            "data" => [
                "id" => $newUserId,
                "fullname" => $fullname,
                "username" => $username,
                "role" => $role,
                "avatar" => $avatar,
                "email" => $email
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Lỗi DB: " . $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>