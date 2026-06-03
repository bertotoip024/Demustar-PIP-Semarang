<?php
// /demustar/api/auth/logout.php
session_start();

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session (jika ada)
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

// Destroy session
session_destroy();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
echo json_encode([
  "ok" => true,
  "message" => "Logout berhasil"
]);
