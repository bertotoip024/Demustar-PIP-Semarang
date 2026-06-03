<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

if (!isset($_SESSION["user"]) || ($_SESSION["user"]["role"] ?? "") !== "admin") {
  http_response_code(401);
  echo json_encode(["ok"=>false,"message"=>"Unauthorized"]);
  exit;
}

require __DIR__ . "/config.php";
$conn = db();

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"message"=>"Request harus JSON"]);
  exit;
}

$id = (int)($data["id"] ?? 0);
$status = $data["status"] ?? "";

$allowed = ["pending","proses","selesai","ditolak"];
if ($id <= 0 || !in_array($status, $allowed, true)) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"message"=>"Input tidak valid"]);
  exit;
}

// kalau status bukan pending, anggap diverifikasi
if ($status !== "pending") {
  $stmt = $conn->prepare("UPDATE aspirasi SET status=?, verified_at=NOW() WHERE id=?");
  $stmt->bind_param("si", $status, $id);
} else {
  $stmt = $conn->prepare("UPDATE aspirasi SET status=?, verified_at=NULL WHERE id=?");
  $stmt->bind_param("si", $status, $id);
}

if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"message"=>"Gagal update", "detail"=>$stmt->error]);
  exit;
}

echo json_encode(["ok"=>true,"message"=>"Status diperbarui"], JSON_UNESCAPED_UNICODE);
