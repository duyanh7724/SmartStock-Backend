<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config.php";

// [QUAN TRỌNG] Đặt múi giờ để tính tuần/tháng chính xác
date_default_timezone_set('Asia/Ho_Chi_Minh');

$baseUrl = "http://10.0.2.2/smartstock_api/uploads/"; // IP cho Emulator

// =============================
// 1) TỔNG THỐNG KÊ
// =============================
$totals = [];

// --- A. ĐẾM SỐ LƯỢNG (Giữ nguyên) ---
$totals['products']  = $conn->query("SELECT COUNT(*) AS t FROM product")->fetch_assoc()['t'] ?? 0;
$totals['imports']   = $conn->query("SELECT COUNT(*) AS t FROM import_orders")->fetch_assoc()['t'] ?? 0;
$totals['exports']   = $conn->query("SELECT COUNT(*) AS t FROM export_orders")->fetch_assoc()['t'] ?? 0;
$totals['orders']    = $conn->query("SELECT COUNT(*) AS t FROM customer_orders")->fetch_assoc()['t'] ?? 0;
$totals['suppliers'] = $conn->query("SELECT COUNT(*) AS t FROM supplier")->fetch_assoc()['t'] ?? 0;
$totals['users']     = $conn->query("SELECT COUNT(*) AS t FROM users")->fetch_assoc()['t'] ?? 0;

// --- B. GIÁ TRỊ TỒN KHO (Giữ nguyên) ---
$sv = $conn->query("SELECT SUM(price * quantity) AS total FROM product")->fetch_assoc()['total'];
$totals['stock_value'] = (float)($sv ?? 0);

// --- C. DOANH THU THEO THỜI GIAN (Giữ nguyên) ---
// 1. Tuần này
$sqlWeek = "SELECT SUM(total_amount) as total 
            FROM customer_orders 
            WHERE status IN ('approved', 'completed') 
            AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
$resWeek = $conn->query($sqlWeek);
$totals['weekly_revenue'] = ($resWeek && $resWeek->num_rows > 0) ? (float)$resWeek->fetch_assoc()['total'] : 0;

// 2. Tháng này
$sqlMonth = "SELECT SUM(total_amount) as total 
             FROM customer_orders 
             WHERE status IN ('approved', 'completed') 
             AND MONTH(created_at) = MONTH(CURDATE()) 
             AND YEAR(created_at) = YEAR(CURDATE())";
$resMonth = $conn->query($sqlMonth);
$totals['monthly_revenue'] = ($resMonth && $resMonth->num_rows > 0) ? (float)$resMonth->fetch_assoc()['total'] : 0;


// --- [MỚI - QUAN TRỌNG] D. TỔNG THU CHI TOÀN BỘ THỜI GIAN ---
// Phần này giúp trang Báo cáo Tài chính hiển thị số liệu

// 1. Tổng chi nhập hàng (Import Cost)
$sqlImport = "SELECT SUM(quantity * unit_price) as total FROM import_order_details";
$resImport = $conn->query($sqlImport);
$totals['total_import_cost'] = ($resImport && $resImport->num_rows > 0) ? (float)$resImport->fetch_assoc()['total'] : 0;

// 2. Tổng giá trị xuất kho nội bộ (Export Value)
$sqlExport = "SELECT SUM(quantity * price) as total FROM export_order_details";
$resExport = $conn->query($sqlExport);
$totals['total_export_value'] = ($resExport && $resExport->num_rows > 0) ? (float)$resExport->fetch_assoc()['total'] : 0;

// 3. Tổng thu bán hàng (Customer Revenue - All Time)
$sqlRev = "SELECT SUM(total_amount) as total FROM customer_orders WHERE status IN ('approved', 'completed')";
$resRev = $conn->query($sqlRev);
$totals['total_customer_revenue'] = ($resRev && $resRev->num_rows > 0) ? (float)$resRev->fetch_assoc()['total'] : 0;


// =============================
// 2) SẢN PHẨM MỚI (TOP 5)
// =============================
$recent_products = [];
$q = $conn->query("SELECT id, name, price, quantity, image FROM product ORDER BY id DESC LIMIT 5");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $row['image_url'] = $baseUrl . ($row['image'] ?: "no_image.jpg");
        $recent_products[] = $row;
    }
}

// =============================
// 3) SẢN PHẨM SẮP HẾT HÀNG
// =============================
$low_stock = [];
$q = $conn->query("SELECT id, name, quantity, price, image FROM product WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $row['image_url'] = $baseUrl . ($row['image'] ?: "no_image.jpg");
        $low_stock[] = $row;
    }
}

// =============================
// 4) TOP 5 BÁN CHẠY
// =============================
$top_selling = [];
$q = $conn->query("
    SELECT p.id, p.name, SUM(d.quantity) AS sold, p.image
    FROM export_order_details d
    JOIN product p ON p.id = d.product_id
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5
");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $row['image_url'] = $baseUrl . ($row['image'] ?: "no_image.jpg");
        $top_selling[] = $row;
    }
}

// =============================
// 5) BIỂU ĐỒ NHẬP – XUẤT THEO THÁNG
// =============================
$monthly_chart = [];

// Lấy thống kê nhập
$q = $conn->query("SELECT MONTH(created_at) AS m, YEAR(created_at) AS y, COUNT(*) AS import_count FROM import_orders GROUP BY y, m");
$imports = [];
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $imports[$r['y'] . '-' . $r['m']] = intval($r['import_count']);
    }
}

// Lấy thống kê xuất
$q2 = $conn->query("SELECT MONTH(created_at) AS m, YEAR(created_at) AS y, COUNT(*) AS export_count FROM export_orders GROUP BY y, m");
$exports = [];
if ($q2) {
    while ($r = $q2->fetch_assoc()) {
        $exports[$r['y'] . '-' . $r['m']] = intval($r['export_count']);
    }
}

// Gộp dữ liệu 12 tháng
for ($i = 11; $i >= 0; $i--) {
    $time = strtotime("-$i month");
    $y = date("Y", $time);
    $m = date("n", $time);
    $key = "$y-$m";

    $monthly_chart[] = [
        "month" => "$m/$y",
        "imports" => $imports[$key] ?? 0,
        "exports" => $exports[$key] ?? 0
    ];
}

// =============================
// TRẢ VỀ JSON
// =============================
echo json_encode([
    "success" => true,
    "data" => [
        "totals" => $totals,
        "recent_products" => $recent_products,
        "low_stock" => $low_stock,
        "top_selling" => $top_selling,
        "monthly_chart" => $monthly_chart
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>