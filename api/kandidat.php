<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$database = 'data_taruna';

$conn = new mysqli($host, $user, $pass, $database);

if ($conn->connect_error) {
    echo json_encode(["ok" => false, "message" => "Koneksi database gagal: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

// Ambil data kandidat dari database
$sql = "SELECT id, nama, nit, visi, misi FROM kandidat ORDER BY id";
$result = $conn->query($sql);

$kandidat = [];
while ($row = $result->fetch_assoc()) {
    $kandidat[] = [
        "id" => $row['id'],
        "nama" => $row['nama'],
        "nit" => $row['nit'],
        "visi" => $row['visi'],
        "misi" => $row['misi']
    ];
}

echo json_encode([
    "ok" => true,
    "data" => $kandidat,
    "total" => count($kandidat)
]);

$conn->close();
?>