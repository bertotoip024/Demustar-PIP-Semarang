<?php
require_once __DIR__ . "/../api/config.php";
session_start();

if (!isset($_SESSION["user"])) {
  json_response(401, ["ok" => false, "message" => "Belum login"]);
}

$nit = (string)($_SESSION["user"]["nit"] ?? "");
if ($nit === "") {
  json_response(400, ["ok" => false, "message" => "Session tidak valid"]);
}

$conn = db();
$stmt = $conn->prepare("SELECT kandidat_id, created_at FROM votes_top4 WHERE nit=? LIMIT 1");
if (!$stmt) json_response(500, ["ok" => false, "message" => "Server error (prepare)"]);
$stmt->bind_param("s", $nit);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;

json_response(200, [
  "ok" => true,
  "has_voted" => $row ? true : false,
  "vote" => $row ?: null
]);
