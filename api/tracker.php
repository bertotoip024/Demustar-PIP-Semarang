<?php
require_once __DIR__ . "/config.php";

$conn = db();

// Hitung yang pending (belum diverifikasi)
$pendingRow = $conn->query("SELECT COUNT(*) AS c FROM aspirasi WHERE status='pending'");
$pending = $pendingRow ? ((int)($pendingRow->fetch_assoc()["c"] ?? 0)) : 0;

// Hitung yang sudah diverifikasi (proses/selesai)
$prosesRow  = $conn->query("SELECT COUNT(*) AS c FROM aspirasi WHERE status='proses'");
$selesaiRow = $conn->query("SELECT COUNT(*) AS c FROM aspirasi WHERE status='selesai'");
$proses  = $prosesRow  ? ((int)($prosesRow->fetch_assoc()["c"] ?? 0)) : 0;
$selesai = $selesaiRow ? ((int)($selesaiRow->fetch_assoc()["c"] ?? 0)) : 0;

// "Masuk" untuk tracker = yang sudah diverifikasi (proses + selesai)
$masuk = $proses + $selesai;

json_response(200, [
  "ok" => true,
  "pending" => $pending,
  "masuk" => $masuk,
  "proses" => $proses,
  "selesai" => $selesai
]);
