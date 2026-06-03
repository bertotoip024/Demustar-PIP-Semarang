<?php
// /demustar/api/kalk.php
// API untuk Beranda KALK (Kalkulasi Navigasi)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========== KONEKSI DATABASE ==========
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

// ========== FUNGSI GET USER ==========
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Cek dari session
    if (isset($_SESSION['user_nit']) && isset($_SESSION['user_nama'])) {
        return ['nit' => $_SESSION['user_nit'], 'nama' => $_SESSION['user_nama']];
    }
    
    // Cek dari cookie/token (opsional)
    if (isset($_COOKIE['user_nit']) && isset($_COOKIE['user_nama'])) {
        return ['nit' => $_COOKIE['user_nit'], 'nama' => $_COOKIE['user_nama']];
    }
    
    // Untuk testing/demo - gunakan user default
    return ['nit' => '2200', 'nama' => 'User Demo'];
}

// ========== HANDLE ACTION ==========
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
    case 'statistik':
        getStatistik($conn);
        break;
    case 'kategori':
        getKategori($conn);
        break;
    default:
        echo json_encode(['ok' => false, 'error' => 'Action tidak ditemukan: ' . $action]);
        break;
}

$conn->close();

// ============================================
// FUNGSI API
// ============================================

/**
 * GET - Ambil semua materi aktif
 */
function getMateri($conn) {
    $sql = "SELECT id, judul, deskripsi, tipe, url, thumbnail, durasi, kategori, tingkat, urutan 
            FROM kalk_materi 
            WHERE status = 'aktif' 
            ORDER BY 
                CASE tingkat 
                    WHEN 'dasar' THEN 1 
                    WHEN 'menengah' THEN 2 
                    WHEN 'lanjutan' THEN 3 
                END, 
                urutan ASC, 
                id ASC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Konversi URL YouTube embed ke watch URL
            if ($row['tipe'] == 'video' && strpos($row['url'], 'youtube.com/embed/') !== false) {
                $videoId = str_replace('https://www.youtube.com/embed/', '', $row['url']);
                $row['url'] = 'https://www.youtube.com/watch?v=' . $videoId;
            }
            $data[] = $row;
        }
    }
    
    echo json_encode(['ok' => true, 'data' => $data, 'total' => count($data)]);
}

/**
 * GET - Ambil semua quiz aktif
 */
function getQuiz($conn) {
    $sql = "SELECT id, materi_id, judul, deskripsi, waktu, jumlah_soal, passing_score, kategori, tingkat, status
            FROM kalk_quiz 
            WHERE status = 'aktif' 
            ORDER BY 
                CASE tingkat 
                    WHEN 'dasar' THEN 1 
                    WHEN 'menengah' THEN 2 
                    WHEN 'lanjutan' THEN 3 
                END, 
                id ASC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Hitung jumlah soal sebenarnya jika belum diisi
            if ($row['jumlah_soal'] == 0) {
                $countSql = "SELECT COUNT(*) as total FROM kalk_soal WHERE quiz_id = " . $row['id'];
                $countResult = $conn->query($countSql);
                if ($countResult && $countResult->num_rows > 0) {
                    $countRow = $countResult->fetch_assoc();
                    $row['jumlah_soal'] = $countRow['total'];
                }
            }
            $data[] = $row;
        }
    }
    
    echo json_encode(['ok' => true, 'data' => $data, 'total' => count($data)]);
}

/**
 * GET - Ambil soal berdasarkan quiz_id
 */
function getSoal($conn) {
    $quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
    
    if ($quizId == 0) {
        echo json_encode(['ok' => false, 'error' => 'Quiz ID diperlukan']);
        return;
    }
    
    // Cek apakah quiz exist
    $checkSql = "SELECT id, judul FROM kalk_quiz WHERE id = $quizId AND status = 'aktif'";
    $checkResult = $conn->query($checkSql);
    
    if (!$checkResult || $checkResult->num_rows == 0) {
        echo json_encode(['ok' => false, 'error' => 'Quiz tidak ditemukan']);
        return;
    }
    
    $quizData = $checkResult->fetch_assoc();
    
    $sql = "SELECT id, quiz_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, penjelasan, poin 
            FROM kalk_soal 
            WHERE quiz_id = $quizId 
            ORDER BY id ASC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode([
        'ok' => true, 
        'data' => $data, 
        'quiz' => $quizData,
        'total' => count($data)
    ]);
}

/**
 * GET - Ambil hasil quiz user yang login
 */
function getHasilSaya($conn) {
    $user = getCurrentUser();
    $userNit = $conn->real_escape_string($user['nit']);
    
    $sql = "SELECT h.id, h.quiz_id, q.judul as quiz_judul, q.kategori, q.tingkat,
                   h.skor, h.nilai, h.status, h.jawaban,
                   h.waktu_mulai, h.waktu_selesai, h.created_at
            FROM kalk_hasil_quiz h
            JOIN kalk_quiz q ON h.quiz_id = q.id
            WHERE h.user_nit = '$userNit'
            ORDER BY h.created_at DESC";
    
    $result = $conn->query($sql);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Decode jawaban jika perlu
            if ($row['jawaban']) {
                $row['jawaban_decoded'] = json_decode($row['jawaban'], true);
            }
            $data[] = $row;
        }
    }
    
    // Hitung statistik
    $totalQuiz = count($data);
    $lulusCount = 0;
    $totalNilai = 0;
    $bestNilai = 0;
    
    foreach ($data as $h) {
        if ($h['status'] == 'lulus') $lulusCount++;
        $totalNilai += $h['nilai'];
        if ($h['nilai'] > $bestNilai) $bestNilai = $h['nilai'];
    }
    
    $avgNilai = $totalQuiz > 0 ? round($totalNilai / $totalQuiz) : 0;
    
    echo json_encode([
        'ok' => true, 
        'data' => $data,
        'stats' => [
            'total_quiz' => $totalQuiz,
            'lulus' => $lulusCount,
            'rata_rata' => $avgNilai,
            'tertinggi' => $bestNilai
        ]
    ]);
}

/**
 * POST - Simpan hasil quiz
 */
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
    $jawaban = isset($input['jawaban']) ? json_encode($input['jawaban']) : null;
    $waktuMulai = isset($input['waktu_mulai']) ? $input['waktu_mulai'] : null;
    $waktuSelesai = isset($input['waktu_selesai']) ? $input['waktu_selesai'] : null;
    
    if ($quizId == 0) {
        echo json_encode(['ok' => false, 'error' => 'Quiz ID diperlukan']);
        return;
    }
    
    $user = getCurrentUser();
    $userNit = $conn->real_escape_string($user['nit']);
    $userNama = $conn->real_escape_string($user['nama']);
    $jawabanEscaped = $jawaban ? "'" . $conn->real_escape_string($jawaban) . "'" : "NULL";
    
    // Cek apakah sudah pernah mengerjakan quiz ini
    $checkSql = "SELECT id, nilai FROM kalk_hasil_quiz 
                 WHERE quiz_id = $quizId AND user_nit = '$userNit'";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $existing = $checkResult->fetch_assoc();
        // Update hasil yang sudah ada
        $sql = "UPDATE kalk_hasil_quiz 
                SET skor = $skor, 
                    nilai = $nilai, 
                    status = '$status', 
                    jawaban = $jawabanEscaped,
                    waktu_selesai = " . ($waktuSelesai ? "'$waktuSelesai'" : "NOW()") . "
                WHERE quiz_id = $quizId AND user_nit = '$userNit'";
        
        $message = 'Hasil quiz berhasil diperbarui';
        $oldNilai = $existing['nilai'];
    } else {
        // Insert hasil baru
        $sql = "INSERT INTO kalk_hasil_quiz 
                (quiz_id, user_nit, user_nama, skor, nilai, status, jawaban, waktu_mulai, waktu_selesai, created_at) 
                VALUES 
                ($quizId, '$userNit', '$userNama', $skor, $nilai, '$status', $jawabanEscaped, 
                " . ($waktuMulai ? "'$waktuMulai'" : "NOW()") . ", 
                " . ($waktuSelesai ? "'$waktuSelesai'" : "NOW()") . ", 
                NOW())";
        
        $message = 'Hasil quiz berhasil disimpan';
        $oldNilai = null;
    }
    
    if ($conn->query($sql)) {
        // Update jumlah soal di tabel quiz jika perlu
        $updateJumlahSql = "UPDATE kalk_quiz q 
                            SET q.jumlah_soal = (SELECT COUNT(*) FROM kalk_soal WHERE quiz_id = q.id)
                            WHERE q.id = $quizId AND (q.jumlah_soal = 0 OR q.jumlah_soal IS NULL)";
        $conn->query($updateJumlahSql);
        
        echo json_encode([
            'ok' => true, 
            'message' => $message,
            'data' => [
                'quiz_id' => $quizId,
                'skor' => $skor,
                'nilai' => $nilai,
                'status' => $status,
                'previous_nilai' => $oldNilai
            ]
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Gagal menyimpan: ' . $conn->error]);
    }
}

/**
 * GET - Detail materi berdasarkan ID
 */
function getDetailMateri($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id == 0) {
        echo json_encode(['ok' => false, 'error' => 'Materi ID diperlukan']);
        return;
    }
    
    $sql = "SELECT * FROM kalk_materi WHERE id = $id AND status = 'aktif'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Konversi URL YouTube jika perlu
        if ($data['tipe'] == 'video' && strpos($data['url'], 'youtube.com/embed/') !== false) {
            $videoId = str_replace('https://www.youtube.com/embed/', '', $data['url']);
            $data['url_embed'] = $data['url'];
            $data['url_watch'] = 'https://www.youtube.com/watch?v=' . $videoId;
        }
        
        // Update view count (opsional)
        // $updateView = "UPDATE kalk_materi SET view_count = view_count + 1 WHERE id = $id";
        // $conn->query($updateView);
        
        echo json_encode(['ok' => true, 'data' => $data]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Materi tidak ditemukan']);
    }
}

/**
 * GET - Statistik keseluruhan
 */
function getStatistik($conn) {
    // Total materi
    $materiSql = "SELECT COUNT(*) as total FROM kalk_materi WHERE status = 'aktif'";
    $materiResult = $conn->query($materiSql);
    $totalMateri = $materiResult->fetch_assoc()['total'];
    
    // Total materi per kategori
    $kategoriSql = "SELECT kategori, COUNT(*) as total FROM kalk_materi WHERE status = 'aktif' GROUP BY kategori";
    $kategoriResult = $conn->query($kategoriSql);
    $materiPerKategori = [];
    while ($row = $kategoriResult->fetch_assoc()) {
        $materiPerKategori[$row['kategori']] = $row['total'];
    }
    
    // Total materi per tingkat
    $tingkatSql = "SELECT tingkat, COUNT(*) as total FROM kalk_materi WHERE status = 'aktif' GROUP BY tingkat";
    $tingkatResult = $conn->query($tingkatSql);
    $materiPerTingkat = [];
    while ($row = $tingkatResult->fetch_assoc()) {
        $materiPerTingkat[$row['tingkat']] = $row['total'];
    }
    
    // Total quiz
    $quizSql = "SELECT COUNT(*) as total FROM kalk_quiz WHERE status = 'aktif'";
    $quizResult = $conn->query($quizSql);
    $totalQuiz = $quizResult->fetch_assoc()['total'];
    
    // Total soal
    $soalSql = "SELECT COUNT(*) as total FROM kalk_soal";
    $soalResult = $conn->query($soalSql);
    $totalSoal = $soalResult->fetch_assoc()['total'];
    
    // Total pengerjaan
    $pengerjaanSql = "SELECT COUNT(*) as total FROM kalk_hasil_quiz";
    $pengerjaanResult = $conn->query($pengerjaanSql);
    $totalPengerjaan = $pengerjaanResult->fetch_assoc()['total'];
    
    // Rata-rata nilai
    $avgSql = "SELECT ROUND(AVG(nilai), 2) as rata_rata FROM kalk_hasil_quiz";
    $avgResult = $conn->query($avgSql);
    $rataRata = $avgResult->fetch_assoc()['rata_rata'] ?? 0;
    
    // Total lulus
    $lulusSql = "SELECT COUNT(*) as total FROM kalk_hasil_quiz WHERE status = 'lulus'";
    $lulusResult = $conn->query($lulusSql);
    $totalLulus = $lulusResult->fetch_assoc()['total'];
    
    // Persentase kelulusan
    $persenLulus = $totalPengerjaan > 0 ? round(($totalLulus / $totalPengerjaan) * 100, 2) : 0;
    
    echo json_encode([
        'ok' => true,
        'data' => [
            'total_materi' => (int)$totalMateri,
            'materi_per_kategori' => $materiPerKategori,
            'materi_per_tingkat' => $materiPerTingkat,
            'total_quiz' => (int)$totalQuiz,
            'total_soal' => (int)$totalSoal,
            'total_pengerjaan' => (int)$totalPengerjaan,
            'rata_rata_nilai' => (float)$rataRata,
            'total_lulus' => (int)$totalLulus,
            'persentase_kelulusan' => $persenLulus
        ]
    ]);
}

/**
 * GET - Daftar kategori dan tingkat yang tersedia
 */
function getKategori($conn) {
    // Ambil semua kategori unik
    $kategoriSql = "SELECT DISTINCT kategori FROM kalk_materi WHERE status = 'aktif' ORDER BY kategori";
    $kategoriResult = $conn->query($kategoriSql);
    $kategori = [];
    while ($row = $kategoriResult->fetch_assoc()) {
        $kategori[] = $row['kategori'];
    }
    
    // Ambil semua tingkat unik
    $tingkatSql = "SELECT DISTINCT tingkat FROM kalk_materi WHERE status = 'aktif' ORDER BY 
                    CASE tingkat 
                        WHEN 'dasar' THEN 1 
                        WHEN 'menengah' THEN 2 
                        WHEN 'lanjutan' THEN 3 
                    END";
    $tingkatResult = $conn->query($tingkatSql);
    $tingkat = [];
    while ($row = $tingkatResult->fetch_assoc()) {
        $tingkat[] = $row['tingkat'];
    }
    
    echo json_encode([
        'ok' => true,
        'data' => [
            'kategori' => $kategori,
            'tingkat' => $tingkat
        ]
    ]);
}
?>