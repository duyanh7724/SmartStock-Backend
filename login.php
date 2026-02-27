<?php
// KHÔNG để khoảng trắng / BOM trước dòng này
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// Đọc JSON body
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        "success" => false,
        "message" => "Vui lòng nhập đủ username và mật khẩu"
    ]);
    exit;
}

/**
 * Ghi log đăng nhập vào bảng login_log
 * user_id có thể NULL (khi đăng nhập thất bại)
 */
function write_login_log(mysqli $conn, ?int $userId): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $conn->prepare("INSERT INTO login_log (user_id, ip_address) VALUES (?, ?)");
    // user_id là INT, có thể NULL
    if ($userId === null) {
        $null = null;
        $stmt->bind_param("is", $null, $ip);
    } else {
        $stmt->bind_param("is", $userId, $ip);
    }

    $stmt->execute();
    $stmt->close();
}

// Tìm user theo username
$stmt = $conn->prepare(
    "SELECT id, fullname, username, password, role 
     FROM users 
     WHERE username = ?"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Có user → kiểm tra mật khẩu
    if (password_verify($password, $row['password'])) {
        // Ghi log thành công
        write_login_log($conn, (int)$row['id']);

        echo json_encode([
            "success" => true,
            "message" => "Đăng nhập thành công",
            "data" => [
                "id"       => (int)$row['id'],
                "fullname" => $row['fullname'],
                "username" => $row['username'],
                "role"     => $row['role']
            ]
        ]);
    } else {
        // Sai mật khẩu → ghi log với user_id = NULL
        write_login_log($conn, null);

        echo json_encode([
            "success" => false,
            "message" => "Sai mật khẩu"
        ]);
    }
} else {
    // Không tồn tại tài khoản → ghi log với user_id = NULL
    write_login_log($conn, null);

    echo json_encode([
        "success" => false,
        "message" => "Tài khoản không tồn tại"
    ]);
}

$stmt->close();
$conn->close();
