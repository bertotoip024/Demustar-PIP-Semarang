<?php
// /api/teknika.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Koneksi database
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'data_taruna';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");

// Fungsi get user (sederhana untuk testing)
function getCurrentUser() {
    // Untuk testing, gunakan user default
    return ['nit' => '2200', 'nama' => 'User Demo'];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'materi':
        getMateri($conn);
        break;
    case 'quiz':
        getQuiz($conn);
        break;
    case 'soal':
        getSoal($conn);
        break;
    case 'hasil_saya':
        getHasilSaya($conn);
        break;
    case 'simpan_hasil':
        simpanHasil($conn);
        break;
    case 'detail_materi':
        getDetailMateri($conn);
        break;
    default:
        echo json_encode(['ok' => false, 'error' => 'Action tidak ditemukan: ' . $action]);
        break;
}

$conn->close();

// ========== FUNCTIONS ==========

function getMateri($conn) {
    $sql = "SELECT id, judul, deskripsi, tipe, url, thumbnail, durasi, kategori 
            FROM teknika_materi 
            WHERE status = 'aktif' 
            ORDER BY urutan ASC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['ok' => true, 'data' => $data]);
}

function getQuiz($conn) {
    $sql = "SELECT id, judul, deskripsi, waktu, jumlah_soal, passing_score, kategori 
            FROM teknika_quiz 
            WHERE status = 'aktif' 
            ORDER BY id ASC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Hitung jumlah soal
            $countSql = "SELECT COUNT(*) as total FROM teknika_soal WHERE quiz_id = " . $row['id'];
            $countResult = $conn->query($countSql);
            if ($countResult) {
                $countRow = $countResult->fetch_assoc();
                $row['jumlah_soal'] = $countRow['total'];
            }
            $data[] = $row;
        }
    }
    
    echo json_encode(['ok' => true, 'data' => $data]);
}

function getSoal($conn) {
    $quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
    
    if ($quizId == 0) {
        echo json_encode(['ok' => false, 'error' => 'Quiz ID diperlukan']);
        return;
    }
    
    $sql = "SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, poin 
            FROM teknika_soal 
            WHERE quiz_id = $quizId 
            ORDER BY id ASC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['ok' => true, 'data' => $data]);
}

function getHasilSaya($conn) {
    $user = getCurrentUser();
    $userNit = $conn->real_escape_string($user['nit']);
    
    $sql = "SELECT h.id, q.judul as quiz_judul, h.nilai, h.status, h.created_at 
            FROM teknika_hasil_quiz h
            JOIN teknika_quiz q ON h.quiz_id = q.id
            WHERE h.user_nit = '$userNit'
            ORDER BY h.created_at DESC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['ok' => true, 'data' => $data]);
}

function simpanHasil($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['ok' => false, 'error' => 'Data tidak valid']);
        return;
    }
    
    $quizId = isset($input['quiz_id']) ? intval($input['quiz_id']) : 0;
    $skor = isset($input['skor']) ? intval($input['skor']) : 0;
    $nilai = isset($input['nilai']) ? intval($input['nilai']) : 0;
    $status = isset($input['status']) ? $input['status'] : 'tidak_lulus';
    
    $user = getCurrentUser();
    $userNit = $conn->real_escape_string($user['nit']);
    $userNama = $conn->real_escape_string($user['nama']);
    
    $sql = "INSERT INTO teknika_hasil_quiz 
            (quiz_id, user_nit, user_nama, skor, nilai, status, waktu_mulai, waktu_selesai, created_at) 
            VALUES 
            ($quizId, '$userNit', '$userNama', $skor, $nilai, '$status', NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            skor = VALUES(skor), nilai = VALUES(nilai), status = VALUES(status), waktu_selesai = NOW()";
    
    if ($conn->query($sql)) {
        echo json_encode(['ok' => true, 'message' => 'Hasil quiz berhasil disimpan']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Gagal menyimpan: ' . $conn->error]);
    }
}

function getDetailMateri($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id == 0) {
        echo json_encode(['ok' => false, 'error' => 'Materi ID diperlukan']);
        return;
    }
    
    $sql = "SELECT * FROM teknika_materi WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['ok' => true, 'data' => $data]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Materi tidak ditemukan']);
    }
}
?>