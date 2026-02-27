<?php
// fcm_send.php - Phiên bản HTTP v1 dùng file JSON service_account.json
// KHÔNG CẦN SERVER KEY CŨ NỮA

function getGoogleAccessToken() {
    $keyFile = __DIR__ . '/service_account.json'; // Đọc file JSON bạn vừa tạo
    
    if (!file_exists($keyFile)) {
        // Nếu không thấy file, ghi log lỗi
        error_log("FCM Error: Không tìm thấy file service_account.json tại " . $keyFile);
        return false;
    }

    $data = json_decode(file_get_contents($keyFile), true);
    
    if (!isset($data['client_email']) || !isset($data['private_key'])) {
        error_log("FCM Error: File JSON không hợp lệ");
        return false;
    }

    // Tạo JWT (JSON Web Token) để xác thực với Google
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $data['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    // Encode Base64Url
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));

    $signatureInput = $base64UrlHeader . "." . $base64UrlClaim;
    $signature = '';
    
    // Ký tên bằng Private Key trong file JSON
    if (!openssl_sign($signatureInput, $signature, $data['private_key'], 'SHA256')) {
        error_log("FCM Error: Không thể ký SSL (Kiểm tra lại OpenSSL extension)");
        return false;
    }
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signatureInput . "." . $base64UrlSignature;

    // Gửi request lấy Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ qua SSL nếu chạy localhost
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("FCM Auth Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (isset($result['error'])) {
        error_log("FCM Auth API Error: " . json_encode($result));
        return false;
    }

    return $result['access_token'] ?? false;
}

function sendPushNotification($fcm_token, $title, $body) {
    // 1. Lấy Project ID từ file JSON
    $keyFile = __DIR__ . '/service_account.json';
    if (!file_exists($keyFile)) return json_encode(["success" => false, "message" => "Thiếu file JSON"]);
    
    $keyData = json_decode(file_get_contents($keyFile), true);
    $projectId = $keyData['project_id'];

    // 2. Lấy Access Token (Chìa khóa tạm thời)
    $accessToken = getGoogleAccessToken();
    if (!$accessToken) {
        return json_encode(["success" => false, "message" => "Lỗi xác thực Google (Không lấy được Token)"]);
    }

    // 3. Gửi tin nhắn qua API v1
    // URL định dạng: https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
    
    // Cấu trúc payload chuẩn cho FCM v1
    $payload = [
        'message' => [
            'token' => $fcm_token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'status' => 'done',
                'title' => $title,
                'body' => $body
            ],
            // Cấu hình riêng cho Android để có tiếng và độ ưu tiên cao
            'android' => [
                'priority' => 'HIGH',
                'notification' => [
                    'sound' => 'default',
                    'default_sound' => true,
                    'channel_id' => 'high_importance_channel' 
                ]
            ],
            // Cấu hình cho iOS (APNs)
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'content-available' => 1
                    ]
                ]
            ]
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return json_encode(["success" => false, "message" => "Curl Error: " . curl_error($ch)]);
    }
    
    curl_close($ch);

    return $response;
}
?>