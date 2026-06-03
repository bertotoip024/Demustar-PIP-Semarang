<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$host = 'localhost';
$user = 'root';
$pass = '';
$database = 'data_taruna';

$conn = new mysqli($host, $user, $pass, $database);

if ($conn->connect_error) {
    echo json_encode(["ok" => false, "message" => "Koneksi gagal: " . $conn->connect_error]);
    exit;
}

$result = $conn->query("SELECT COUNT(*) as total FROM users");
$total = $result->fetch_assoc()['total'];

echo json_encode(["ok" => true, "total" => (int)$total]);

$conn->close();
?>