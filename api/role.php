<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user'])) {
    echo json_encode(["ok" => false, "role" => null, "message" => "Belum login"]);
    exit;
}

echo json_encode([
    "ok" => true,
    "role" => $_SESSION['user']['role'],
    "user" => $_SESSION['user']
]);
?>