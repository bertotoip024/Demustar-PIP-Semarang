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

$status = $_GET["status"] ?? "pending";
$allowed = ["pending","proses","selesai","ditolak"];
if (!in_array($status, $allowed, true)) $status = "pending";

$stmt = $conn->prepare(
  "SELECT id, nama, kontak, kategori, isi, anonim, status, created_at, verified_at
   FROM aspirasi
   WHERE status=?
   ORDER BY id DESC
   LIMIT 200"
);
$stmt->bind_param("s", $status);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) $rows[] = $row;

echo json_encode(["ok"=>true,"data"=>$rows], JSON_UNESCAPED_UNICODE);
