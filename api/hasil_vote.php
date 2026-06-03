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

// Query untuk menghitung suara per kandidat
$sql = "SELECT 
            k.id as kandidat_id,
            k.nama as kandidat_nama,
            k.nit as kandidat_nit,
            COUNT(v.id) as suara
        FROM kandidat k
        LEFT JOIN vote_top4 v ON k.id = v.kandidat_id
        GROUP BY k.id, k.nama, k.nit
        ORDER BY suara DESC, k.nama ASC";

$result = $conn->query($sql);

$hasil = [];
$totalSuara = 0;

while ($row = $result->fetch_assoc()) {
    $hasil[] = [
        "id" => $row['kandidat_id'],
        "nama" => $row['kandidat_nama'],
        "nit" => $row['kandidat_nit'],
        "suara" => (int)$row['suara']
    ];
    $totalSuara += (int)$row['suara'];
}

// Hitung total pemilih yang sudah vote
$totalVote = $conn->query("SELECT COUNT(DISTINCT nit) as total FROM vote_top4")->fetch_assoc()['total'];

echo json_encode([
    "ok" => true,
    "data" => $hasil,
    "total_suara" => $totalSuara,
    "total_vote" => (int)$totalVote
]);

$conn->close();
?>