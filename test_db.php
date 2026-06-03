<?php
require_once __DIR__ . "/api/config.php";

$conn = db();
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$row = $result->fetch_assoc();

echo "Koneksi DB BERHASIL! Total users: " . $row['total'];
?>