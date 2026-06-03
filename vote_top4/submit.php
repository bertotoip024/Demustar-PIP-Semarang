<?php
require_once __DIR__ . "/../api/config.php";
session_start();

if (!isset($_SESSION["user"])) {
  json_response(401, ["ok" => false, "message" => "Belum login"]);
}

$nit = (string)($_SESSION["user"]["nit"] ?? "");
$input = json_decode(file_get_contents("php://input"), true);
$kandidat_id = trim((string)($input["kandidat_id"] ?? ""));

if ($nit === "" || $kandidat_id === "") {
  json_response(400, ["ok" => false, "message" => "Data tidak lengkap"]);
}

$conn = db();

// pastikan 1 orang 1 vote (mengandalkan UNIQUE nit di tabel)
$stmt = $conn->prepare("INSERT INTO votes_top4 (nit, kandidat_id) VALUES (?, ?)");
if (!$stmt) {
  json_response(500, ["ok" => false, "message" => "Server error (prepare)"]);
}
$stmt->bind_param("ss", $nit, $kandidat_id);

if (!$stmt->execute()) {
  // duplicate key (sudah pernah vote)
  if ($conn->errno === 1062) {
    json_response(409, ["ok" => false, "message" => "Kamu sudah melakukan voting"]);
  }
  json_response(500, ["ok" => false, "message" => "Gagal menyimpan vote"]);
}

json_response(200, ["ok" => true, "message" => "Vote berhasil disimpan", "kandidat_id" => $kandidat_id]);
