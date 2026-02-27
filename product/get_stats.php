<?php
require_once "../config.php";

$stats = [];

// Tổng sản phẩm
$r1 = $conn->query("SELECT COUNT(*) AS total FROM product")->fetch_assoc();
$stats['products'] = intval($r1['total']);

// Tổng phiếu nhập
$r2 = $conn->query("SELECT COUNT(*) AS total FROM import_orders")->fetch_assoc();
$stats['imports'] = intval($r2['total']);

// Tổng phiếu xuất
$r3 = $conn->query("SELECT COUNT(*) AS total FROM export_orders")->fetch_assoc();
$stats['exports'] = intval($r3['total']);

// Tổng đơn hàng khách
$r4 = $conn->query("SELECT COUNT(*) AS total FROM customer_orders")->fetch_assoc();
$stats['orders'] = intval($r4['total']);

echo json_encode([
    "success" => true,
    "data" => $stats
]);
