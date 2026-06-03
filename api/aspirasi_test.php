<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Data STATIS untuk test (pasti muncul)
$data = [
    ["id" => 1, "tanggal" => "2026-05-11", "nama" => "Budi Santoso", "kategori" => "Fasilitas", "isi" => "Perbaikan AC ruang kelas", "anonim" => 0, "status" => "pending"],
    ["id" => 2, "tanggal" => "2026-05-11", "nama" => "Andi Pratama", "kategori" => "Akademik", "isi" => "Penambahan jam praktikum", "anonim" => 0, "status" => "proses"],
    ["id" => 3, "tanggal" => "2026-05-11", "nama" => "Siti Rahma", "kategori" => "Kebersihan", "isi" => "Penambahan tempat sampah", "anonim" => 0, "status" => "selesai"]
];

echo json_encode([
    "ok" => true,
    "data" => $data,
    "stats" => ["pending" => 1, "proses" => 1, "selesai" => 1, "total" => 3]
]);
?>