<?php
require_once __DIR__ . "/config.php";
session_start();

if (!isset($_SESSION["user"]) || (($_SESSION["user"]["role"] ?? "") !== "admin")) {
  json_response(403, ["ok" => false, "message" => "Forbidden"]);
}

$conn = db();

// rekap per kandidat
$rekap = [];
$r1 = $conn->query("SELECT kandidat_id, COUNT(*) AS total FROM votes_top4 GROUP BY kandidat_id ORDER BY total DESC");
if ($r1) {
  while ($row = $r1->fetch_assoc()) $rekap[] = $row;
}

// daftar vote (nit + kandidat + waktu)
$list = [];
$r2 = $conn->query("SELECT nit, kandidat_id, created_at FROM votes_top4 ORDER BY created_at DESC");
if ($r2) {
  while ($row = $r2->fetch_assoc()) $list[] = $row;
}

json_response(200, ["ok" => true, "rekap" => $rekap, "list" => $list]);
