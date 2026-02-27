<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$oid = intval($_GET["id"] ?? 0);

if ($oid <= 0) {
    echo json_encode(["success" => false, "data" => []]);
    exit;
}

$sql = "
    SELECT d.*, p.name AS product_name, p.image AS product_image
    FROM customer_order_details d
    JOIN product p ON d.product_id = p.id
    WHERE d.customer_order_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $oid);
$stmt->execute();
$res = $stmt->get_result();

$list = [];
$baseUrl = "http://localhost/smartstock_api/uploads/";

while ($row = $res->fetch_assoc()) {
    $row["image_url"] = $baseUrl . ($row["product_image"] ?? "no_image.jpg");
    $list[] = $row;
}

echo json_encode(["success" => true, "data" => $list], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
