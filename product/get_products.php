<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$sql = "SELECT 
            p.*, 
            c.name AS category_name, 
            s.name AS supplier_name
        FROM product p
        LEFT JOIN category c ON p.category_id = c.id
        LEFT JOIN supplier s ON p.supplier_id = s.id";

$result = $conn->query($sql);

$baseUrl = "http://localhost/smartstock_api/uploads/";

$products = [];
while ($row = $result->fetch_assoc()) {

    // Tạo link ảnh đầy đủ
    if (!empty($row['image'])) {
        $row['image_url'] = $baseUrl . $row['image'];
    } else {
        $row['image_url'] = $baseUrl . "no_image.jpg";
    }

    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $products
], JSON_UNESCAPED_UNICODE);
?>
