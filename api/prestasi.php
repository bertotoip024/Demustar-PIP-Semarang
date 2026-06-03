<?php
/**
 * API Prestasi DEMUSTAR
 * Method: POST (kirim prestasi), GET (ambil data), PUT (update status), DELETE (hapus)
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
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
    echo json_encode(["ok" => false, "message" => "Koneksi gagal: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

// ========== GET - Ambil Data Prestasi ==========
if ($method === 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    if ($status !== 'all') {
        $sql = "SELECT id, nama, kelas, nit, tingkat, tanggal, link_dokumen, deskripsi, saran, status, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
                FROM prestasi 
                WHERE status = ? 
                ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status);
    } else {
        $sql = "SELECT id, nama, kelas, nit, tingkat, tanggal, link_dokumen, deskripsi, saran, status, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
                FROM prestasi 
                ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Hitung statistik
    $pending = $conn->query("SELECT COUNT(*) as total FROM prestasi WHERE status='pending'")->fetch_assoc()['total'];
    $proses = $conn->query("SELECT COUNT(*) as total FROM prestasi WHERE status='proses'")->fetch_assoc()['total'];
    $selesai = $conn->query("SELECT COUNT(*) as total FROM prestasi WHERE status='selesai'")->fetch_assoc()['total'];
    
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
    $stmt->close();
}

// ========== POST - Kirim Prestasi Baru ==========
elseif ($method === 'POST') {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    
    if (!is_array($data)) {
        echo json_encode(["ok" => false, "message" => "Request harus JSON"]);
        exit;
    }
    
    $nama = trim($data["nama"] ?? "");
    $kelas = trim($data["kelas"] ?? "");
    $nit = trim($data["nit"] ?? "");
    $tingkat = trim($data["tingkat"] ?? "");
    $tanggal = trim($data["tanggal"] ?? "");
    $link_dokumen = trim($data["link_dokumen"] ?? "");
    $deskripsi = trim($data["deskripsi"] ?? "");
    $saran = trim($data["saran"] ?? "");
    
    // Validasi
    $errors = [];
    if (empty($nama)) $errors[] = "Nama wajib diisi";
    if (empty($kelas)) $errors[] = "Kelas wajib diisi";
    if (empty($nit)) $errors[] = "NIT wajib diisi";
    if (empty($tingkat)) $errors[] = "Tingkat kejuaraan wajib dipilih";
    if (empty($tanggal)) $errors[] = "Tanggal wajib diisi";
    if (empty($link_dokumen)) $errors[] = "Link dokumen wajib diisi";
    if (empty($deskripsi)) $errors[] = "Deskripsi prestasi wajib diisi";
    if (strlen($deskripsi) < 10) $errors[] = "Deskripsi prestasi minimal 10 karakter";
    
    if (!empty($errors)) {
        echo json_encode(["ok" => false, "message" => implode(", ", $errors)]);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO prestasi (nama, kelas, nit, tingkat, tanggal, link_dokumen, deskripsi, saran, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssssssss", $nama, $kelas, $nit, $tingkat, $tanggal, $link_dokumen, $deskripsi, $saran);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Pengajuan prestasi berhasil dikirim", "id" => $stmt->insert_id]);
    } else {
        echo json_encode(["ok" => false, "message" => "Gagal menyimpan: " . $stmt->error]);
    }
    $stmt->close();
}

// ========== PUT - Update Status Prestasi ==========
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
    
    $stmt = $conn->prepare("UPDATE prestasi SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Status berhasil diupdate"]);
    } else {
        echo json_encode(["ok" => false, "message" => "Gagal update status"]);
    }
    $stmt->close();
}

// ========== DELETE - Hapus Prestasi ==========
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
    
    $stmt = $conn->prepare("DELETE FROM prestasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "message" => "Prestasi berhasil dihapus"]);
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