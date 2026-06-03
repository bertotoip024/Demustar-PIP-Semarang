<?php
/**
 * Konfigurasi Database DEMUSTAR
 * Lokasi: C:\xampp\htdocs\demustar\api\config.php
 */

function db() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $database = 'data_taruna';
    
    $conn = new mysqli($host, $user, $pass, $database);
    
    if ($conn->connect_error) {
        die(json_encode(["ok" => false, "message" => "Koneksi database gagal: " . $conn->connect_error]));
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

function json_response($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
?>