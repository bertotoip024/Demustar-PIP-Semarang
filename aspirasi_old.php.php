<?php
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
  respond(400, ["ok" => false, "message" => "Request harus JSON"]);
}

$nama     = trim($data["nama"] ?? "");
$kontak   = trim($data["kontak"] ?? "");
$kategori = trim($data["kategori"] ?? "");
$isi      = trim($data["isi"] ?? "");
$anonim   = !empty($data["anonim"]) ? 1 : 0;

if ($kategori === "") {
  respond(400, ["ok" => false, "message" => "Kategori wajib dipilih"]);
}

if (mb_strlen($isi, "UTF-8") < 10) {
  respond(400, ["ok" => false, "message" => "Aspirasi minimal 10 karakter"]);
}

if ($anonim === 1) {
  $nama = "";
  $kontak = "";
}

$conn = new mysqli("localhost", "root", "", "demustar_db");
if ($conn->connect_error) {
  respond(500, ["ok" => false, "message" => "Koneksi database gagal", "detail" => $conn->connect_error]);
}

$stmt = $conn->prepare("INSERT INTO aspirasi (nama, kontak, kategori, isi, anonim) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
  respond(500, ["ok" => false, "message" => "Prepare statement gagal", "detail" => $conn->error]);
}

$stmt->bind_param("ssssi", $nama, $kontak, $kategori, $isi, $anonim);

if (!$stmt->execute()) {
  respond(500, ["ok" => false, "message" => "Gagal menyimpan data", "detail" => $stmt->error]);
}

respond(200, ["ok" => true, "message" => "Aspirasi berhasil dikirim"]);
