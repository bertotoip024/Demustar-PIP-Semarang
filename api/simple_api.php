<?php
header("Content-Type: application/json");
echo json_encode([
    "ok" => true,
    "message" => "API berfungsi!",
    "data" => [
        ["id" => 1, "nama" => "Test 1", "status" => "pending"],
        ["id" => 2, "nama" => "Test 2", "status" => "proses"]
    ]
]);
?>