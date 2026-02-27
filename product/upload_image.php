<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$targetDir = "../uploads/";

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!empty($_FILES["file"]["name"])) {

    $safeName = preg_replace("/[^a-zA-Z0-9\.]/", "", basename($_FILES["file"]["name"]));
    $filename = time() . "_" . $safeName;

    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        echo json_encode([
            "success" => true,
            "filename" => $filename
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Upload failed"
        ]);
    }

} else {
    echo json_encode([
        "success" => false,
        "message" => "No file received"
    ]);
}
?>
