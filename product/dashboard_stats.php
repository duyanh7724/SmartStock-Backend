<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Kết nối database
require_once "../config.php";

// [QUAN TRỌNG] Thiết lập múi giờ Việt Nam để tính Tuần/Tháng chính xác
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Địa chỉ ảnh (Dùng IP 10.0.2.2 cho Android Emulator hoặc IP LAN cho máy thật)
$baseUrl = "http://10.0.2.2/smartstock_api/uploads/"; 

// =======================================================================
// 1) CÁC CHỈ SỐ CƠ BẢN (ĐẾM SỐ LƯỢNG)
// =======================================================================
$stats = [];

// Sử dụng try-catch để tránh lỗi chết trang nếu bảng chưa tồn tại
try {
    $stats["products"]  = $conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()["c"] ?? 0;
    $stats["imports"]   = $conn->query("SELECT COUNT(*) AS c FROM import_orders")->fetch_assoc()["c"] ?? 0;
    $stats["exports"]   = $conn->query("SELECT COUNT(*) AS c FROM export_orders")->fetch_assoc()["c"] ?? 0;
    $stats["orders"]    = $conn->query("SELECT COUNT(*) AS c FROM customer_orders")->fetch_assoc()["c"] ?? 0;
    $stats["suppliers"] = $conn->query("SELECT COUNT(*) AS c FROM supplier")->fetch_assoc()["c"] ?? 0;
    $stats["users"]     = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()["c"] ?? 0;
} catch (Exception $e) {
    // Nếu lỗi query thì trả về 0
}

// =======================================================================
// 2) TÍNH TOÁN TÀI CHÍNH
// =======================================================================

// A. GIÁ TRỊ TỒN KHO (Số lượng * Giá bán hiện tại)
$sqlInventory = "SELECT SUM(quantity * price) as total FROM product";
$resInv = $conn->query($sqlInventory);
$inventoryVal = ($resInv && $resInv->num_rows > 0) ? $resInv->fetch_assoc()['total'] : 0;

// B. DOANH THU TUẦN NÀY
// Logic: Đơn 'approved' hoặc 'completed' + Thuộc tuần hiện tại (YEARWEEK mode 1: T2-CN)
$sqlWeek = "SELECT SUM(total_amount) as total 
            FROM customer_orders 
            WHERE status IN ('approved', 'completed') 
            AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
$resWeek = $conn->query($sqlWeek);
$weekRevenue = ($resWeek && $resWeek->num_rows > 0) ? $resWeek->fetch_assoc()['total'] : 0;

// C. DOANH THU THÁNG NÀY
// Logic: Đơn 'approved' hoặc 'completed' + Thuộc tháng hiện tại
$sqlMonth = "SELECT SUM(total_amount) as total 
             FROM customer_orders 
             WHERE status IN ('approved', 'completed') 
             AND MONTH(created_at) = MONTH(CURDATE()) 
             AND YEAR(created_at) = YEAR(CURDATE())";
$resMonth = $conn->query($sqlMonth);
$monthRevenue = ($resMonth && $resMonth->num_rows > 0) ? $resMonth->fetch_assoc()['total'] : 0;

// Gán vào mảng trả về (Ép kiểu float để tránh null)
$stats["inventory_value"] = (float)$inventoryVal;
$stats["weekly_revenue"]  = (float)$weekRevenue;
$stats["monthly_revenue"] = (float)$monthRevenue;


// =======================================================================
// 3) SẢN PHẨM MỚI (Lấy 5 sản phẩm mới nhất)
// =======================================================================
$recentList = [];
$recent = $conn->query("SELECT * FROM product ORDER BY id DESC LIMIT 5");
if ($recent) {
    while ($r = $recent->fetch_assoc()) {
        $r["image_url"] = $baseUrl . ($r["image"] ?: "no_image.jpg");
        $recentList[] = $r;
    }
}

// =======================================================================
// 4) SẮP HẾT HÀNG (<= 5 cái)
// =======================================================================
$lowList = [];
$low = $conn->query("SELECT * FROM product WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5");
if ($low) {
    while ($r = $low->fetch_assoc()) {
        $r["image_url"] = $baseUrl . ($r["image"] ?: "no_image.jpg");
        $lowList[] = $r;
    }
}

// =======================================================================
// 5) TOP BÁN CHẠY (Dựa trên phiếu xuất kho)
// =======================================================================
$topList = [];
$topSql = "SELECT p.*, SUM(d.quantity) AS sold
           FROM export_order_details d
           JOIN product p ON p.id = d.product_id
           GROUP BY d.product_id
           ORDER BY sold DESC
           LIMIT 5";
$top = $conn->query($topSql);
if ($top) {
    while ($r = $top->fetch_assoc()) {
        $r["image_url"] = $baseUrl . ($r["image"] ?: "no_image.jpg");
        $topList[] = $r;
    }
}

// =======================================================================
// 6) TRẢ VỀ KẾT QUẢ JSON
// =======================================================================
echo json_encode([
    "success" => true,
    "totals" => $stats, 
    "recent_products" => $recentList,
    "low_stock" => $lowList,
    "top_selling" => $topList,
]);
?>