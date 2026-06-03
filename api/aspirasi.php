<?php
/**
 * API Aspirasi DEMUSTAR
 * Lokasi: C:\xampp\htdocs\demustar\api\aspirasi.php
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$database = 'data_taruna';

$conn = new mysqli($host, $user, $pass, $database);

if ($conn->connect_error) {
    echo json_encode(["ok" => false, "message" => "Koneksi database gagal: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

// ========== GET - Ambil Data ==========
if ($method === 'GET') {
    $sql = "SELECT id, nama, kontak, kategori, isi, anonim, status, DATE_FORMAT(tanggal, '%Y-%m-%d') as tanggal 
            FROM aspirasi 
            ORDER BY id DESC";
    
    $result = $conn->query($sql);
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['anonim'] == 1) {
            $row['nama'] = 'Anonim';
            $row['kontak'] = '';
        }
        $data[] = $row;
    }
    
    // Hitung statistik
    $pending = 0;
    $proses = 0;
    $selesai = 0;
    
    $statResult = $conn->query("SELECT status, COUNT(*) as total FROM aspirasi GROUP BY status");
    while ($row = $statResult->fetch_assoc()) {
        if ($row['status'] == 'pending') $pending = $row['total'];
        if ($row['status'] == 'proses') $proses = $row['total'];
        if ($row['status'] == 'selesai') $selesai = $row['total'];
    }
    
    echo json_encode([
        "ok" => true,
        "data" => $data,
        "stats" => [
            "pending" => (int)$pending,
            "proses" => (int)$proses,
            "selesai" => (int)$selesai,
            "total" => count($data)
        ]
    ]);
}

// ========== POST - Kirim Aspirasi ==========
elseif ($method === 'POST') {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    
    if (!is_array($data)) {
        echo json_encode(["ok" => false, "message" => "Request harus JSON"]);
        exit;
    }
    
    $nama = trim($data["nama"] ?? "");
    $kontak = trim($data["kontak"] ?? "");
    $kategori = trim($data["kategori"] ?? "");
    $isi = trim($data["isi"] ?? "");
    $anonim = !empty($data["anonim"]) ? 1 : 0;
    
    if (empty($kategori)) {
        echo json_encode(["ok" => false, "message" => "Kategori wajib dipilih"]);
        exit;
    }
    
    if (strlen($isi) < 10) {
        echo json_encode(["ok" => false, "message" => "Aspirasi minimal 10 karakter"]);
        exit;
    }
    
    if ($anonim == 1) {
        $nama = "";
        $kontak = "";
    }
    
    $stmt = $conn->prepare("INSERT INTO aspirasi (nama, kontak, kategori, isi, anonim, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssssi", $nama, $kontak, $kategori, $isi, $anonim);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Aspirasi berhasil dikirim", "id" => $stmt->insert_id]);
    } else {
        echo json_encode(["ok" => false, "message" => "Gagal menyimpan: " . $stmt->error]);
    }
    $stmt->close();
}

// ========== PUT - Update Status ==========
elseif ($method === 'PUT') {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    
    if (!is_array($data)) {
        echo json_encode(["ok" => false, "message" => "Request harus JSON"]);
        exit;
    }
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $status = isset($data['status']) ? $data['status'] : '';
    
    if ($id <= 0) {
        echo json_encode(["ok" => false, "message" => "ID tidak valid"]);
        exit;
    }
    
    $allowed = ['pending', 'proses', 'selesai'];
    if (!in_array($status, $allowed)) {
        echo json_encode(["ok" => false, "message" => "Status tidak valid"]);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE aspirasi SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Status berhasil diupdate"]);
    } else {
        echo json_encode(["ok" => false, "message" => "Gagal update status"]);
    }
    $stmt->close();
}

// ========== DELETE - Hapus Aspirasi ==========
elseif ($method === 'DELETE') {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    
    if (!is_array($data)) {
        echo json_encode(["ok" => false, "message" => "Request harus JSON"]);
        exit;
    }
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(["ok" => false, "message" => "ID tidak valid"]);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM aspirasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Aspirasi berhasil dihapus"]);
    } else {
        echo json_encode(["ok" => false, "message" => "Gagal menghapus"]);
    }
    $stmt->close();
}

else {
    echo json_encode(["ok" => false, "message" => "Method tidak diizinkan"]);
}

$conn->close();
?>