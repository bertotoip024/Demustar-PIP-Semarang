<?php
/**
 * DEMUSTAR — api/auth/login.php
 * Database: data_taruna | Tabel: data_taruna | Kolom NIT: nit
 */
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
        if (ob_get_level()) ob_clean();
        http_response_code(500);
        echo json_encode(["ok" => false, "message" => "PHP Error: " . $e['message']]);
    }
});
ob_start();

function respond(int $code, string $msg, $data = null): void {
    if (ob_get_level()) ob_clean();
    http_response_code($code);
    echo json_encode(["ok" => $code < 400, "message" => $msg, "data" => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>7200,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$conn = new mysqli('localhost', 'root', '', 'data_taruna');
if ($conn->connect_error) respond(500, "Koneksi DB gagal: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

function norm_date(string $s): string {
    $s = trim($s);
    if ($s === "") return "";
    $ts = strtotime($s);
    return $ts ? date("Y-m-d", $ts) : "";
}

$body = @json_decode((string)file_get_contents("php://input"), true);
if (!is_array($body)) respond(400, "Request harus JSON.");

$nit_input = trim((string)($body["nit"]       ?? ""));
$pass      = (string)($body["password"]        ?? "");
$dob_input = norm_date((string)($body["tanggal_lahir"] ?? $body["tgl_lahir"] ?? $body["dob"] ?? ""));

if ($nit_input === "") respond(400, "NIT wajib diisi.");
if ($pass      === "") respond(400, "Password wajib diisi.");
if ($dob_input === "") respond(400, "Tanggal lahir wajib diisi.");

// Query ke tabel data_taruna, kolom nit
$stmt = $conn->prepare("SELECT * FROM `data_taruna` WHERE `nit` = ? LIMIT 1");
if (!$stmt) respond(500, "DB error: " . $conn->error);
$stmt->bind_param("s", $nit_input);
$stmt->execute();
$user = ($r = $stmt->get_result()) ? $r->fetch_assoc() : null;

if (!$user) respond(401, "NIT tidak ditemukan (contoh: admin001, 2220002, 220003).");

// Validasi tanggal lahir
$dob_db = norm_date((string)($user['tanggal_lahir'] ?? ""));
if ($dob_db !== "" && $dob_db !== "0000-00-00" && $dob_input !== $dob_db) {
    respond(401, "Tanggal lahir tidak sesuai. Gunakan: " . date("d/m/Y", strtotime($dob_db)));
}

// Validasi password (SHA256)
$stored  = (string)($user["password_hash"] ?? "");
if ($stored === "") respond(401, "Akun belum punya password.");

$hashInfo = password_get_info($stored);
$loginOk  = false;

if (!empty($hashInfo["algo"])) {
    $loginOk = password_verify($pass, $stored);
} else {
    $loginOk = strtolower(hash("sha256", $pass)) === strtolower($stored);
    if ($loginOk) {
        $nh = password_hash($pass, PASSWORD_DEFAULT);
        if ($nh) {
            $up = $conn->prepare("UPDATE `data_taruna` SET `password_hash`=? WHERE `id`=?");
            if ($up) { $uid=(int)($user["id"]??0); $up->bind_param("si",$nh,$uid); @$up->execute(); }
        }
    }
}

if (!$loginOk) respond(401, "Password salah. Gunakan NIT sebagai password (contoh: admin001).");

// Buat session
session_regenerate_id(true);
$_SESSION["user"] = [
    "id"            => (int)($user["id"]   ?? 0),
    "nit"           => (string)($user["nit"]  ?? ""),
    "nama"          => (string)($user["nama"] ?? ""),
    "role"          => (string)($user["role"] ?? "user"),
    "tanggal_lahir" => $dob_db,
];

respond(200, "Login berhasil", [
    "nit"  => (string)($user["nit"]  ?? ""),
    "nama" => (string)($user["nama"] ?? ""),
    "role" => (string)($user["role"] ?? "user"),
    "tanggal_lahir" => $dob_db,
]);
