<?php
// /demustar/api/auth/me.php

session_start();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if (!isset($_SESSION["user"])) {
  http_response_code(401);
  echo json_encode([
    "ok" => false,
    "message" => "Belum login"
  ]);
  exit;
}

echo json_encode([
  "ok" => true,
  "user" => $_SESSION["user"]
]);
