<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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
    echo json_encode(["ok" => false, "message" => "Koneksi gagal: " . $conn->connect_error]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - Cek status vote user
if ($method === 'GET') {
    session_start();
    $nit = $_SESSION['user']['nit'] ?? '';
    
    if (empty($nit)) {
        echo json_encode(["ok" => true, "has_voted" => false, "message" => "Belum login"]);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT kandidat_id, kandidat_nama FROM vote_top4 WHERE nit = ?");
    $stmt->bind_param("s", $nit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "ok" => true,
            "has_voted" => true,
            "vote" => ["kandidat_id" => $row['kandidat_id'], "kandidat_nama" => $row['kandidat_nama']]
        ]);
    } else {
        echo json_encode(["ok" => true, "has_voted" => false]);
    }
    $stmt->close();
}

// POST - Submit vote
elseif ($method === 'POST') {
    session_start();
    $nit = $_SESSION['user']['nit'] ?? '';
    
    if (empty($nit)) {
        echo json_encode(["ok" => false, "message" => "Silakan login terlebih dahulu"]);
        exit;
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    $kandidat_id = $input['kandidat_id'] ?? '';
    $kandidat_nama = $input['kandidat_nama'] ?? '';
    
    if (empty($kandidat_id)) {
        echo json_encode(["ok" => false, "message" => "Kandidat tidak valid"]);
        exit;
    }
    
    // Cek apakah sudah pernah vote
    $check = $conn->prepare("SELECT id FROM vote_top4 WHERE nit = ?");
    $check->bind_param("s", $nit);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["ok" => false, "message" => "Anda sudah melakukan vote sebelumnya"]);
        $check->close();
        exit;
    }
    $check->close();
    
    // Simpan vote
    $stmt = $conn->prepare("INSERT INTO vote_top4 (nit, kandidat_id, kandidat_nama) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nit, $kandidat_id, $kandidat_nama);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Vote berhasil disimpan"]);
    } else {
        echo json_encode(["ok" => false, "message" => "Gagal menyimpan vote: " . $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>