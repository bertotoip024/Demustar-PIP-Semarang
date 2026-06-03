<?php
// api/soal.php
require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['materi_id'])) {
            getSoalByMateri($db, $_GET['materi_id']);
        } elseif(isset($_GET['id'])) {
            getSoalById($db, $_GET['id']);
        } elseif(isset($_GET['quiz'])) {
            getQuizQuestions($db, $_GET['quiz']);
        } else {
            getAllSoal($db);
        }
        break;
    case 'POST':
        if(isset($_GET['submit'])) {
            submitQuiz($db);
        } else {
            createSoal($db);
        }
        break;
    case 'PUT':
        updateSoal($db);
        break;
    case 'DELETE':
        deleteSoal($db);
        break;
}

function getAllSoal($db) {
    $query = "SELECT s.*, m.judul as materi_judul, t.nama as pembuat 
              FROM soal_nautika s 
              LEFT JOIN materi_nautika m ON s.materi_id = m.id 
              LEFT JOIN data_taruna t ON s.created_by = t.id 
              ORDER BY s.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $database->sendResponse(true, $stmt->fetchAll());
}

function getSoalByMateri($db, $materi_id) {
    $query = "SELECT s.* FROM soal_nautika s WHERE s.materi_id = :materi_id ORDER BY s.id ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":materi_id", $materi_id);
    $stmt->execute();
    $database->sendResponse(true, $stmt->fetchAll());
}

function getQuizQuestions($db, $materi_id) {
    $query = "SELECT id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, bobot 
              FROM soal_nautika WHERE materi_id = :materi_id ORDER BY RAND()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":materi_id", $materi_id);
    $stmt->execute();
    $database->sendResponse(true, $stmt->fetchAll());
}

function createSoal($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required = ['materi_id', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar'];
    foreach($required as $field) {
        if(!isset($data[$field]) || empty($data[$field])) {
            $database->sendResponse(false, null, "Field $field diperlukan", 400);
        }
    }
    
    $query = "INSERT INTO soal_nautika (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, bobot, created_by) 
              VALUES (:materi_id, :pertanyaan, :pilihan_a, :pilihan_b, :pilihan_c, :pilihan_d, :jawaban_benar, :bobot, :created_by)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":materi_id", $data['materi_id']);
    $stmt->bindParam(":pertanyaan", $data['pertanyaan']);
    $stmt->bindParam(":pilihan_a", $data['pilihan_a']);
    $stmt->bindParam(":pilihan_b", $data['pilihan_b']);
    $stmt->bindParam(":pilihan_c", $data['pilihan_c']);
    $stmt->bindParam(":pilihan_d", $data['pilihan_d']);
    $stmt->bindParam(":jawaban_benar", $data['jawaban_benar']);
    $stmt->bindParam(":bobot", $data['bobot']);
    $stmt->bindParam(":created_by", $_SESSION['user_id']);
    
    if($stmt->execute()) {
        $database->sendResponse(true, ['id' => $db->lastInsertId()], "Soal berhasil ditambahkan");
    } else {
        $database->sendResponse(false, null, "Gagal menambahkan soal", 500);
    }
}

function submitQuiz($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    $materi_id = $data['materi_id'];
    $jawaban = $data['jawaban'];
    
    // Get all correct answers
    $query = "SELECT id, jawaban_benar, bobot FROM soal_nautika WHERE materi_id = :materi_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":materi_id", $materi_id);
    $stmt->execute();
    $soal = $stmt->fetchAll();
    
    $totalBobot = 0;
    $skor = 0;
    
    foreach($soal as $s) {
        $totalBobot += $s['bobot'];
        if(isset($jawaban[$s['id']]) && $jawaban[$s['id']] == $s['jawaban_benar']) {
            $skor += $s['bobot'];
        }
    }
    
    $nilai = $totalBobot > 0 ? ($skor / $totalBobot) * 100 : 0;
    $nilai = round($nilai, 2);
    
    $database->sendResponse(true, [
        'nilai' => $nilai,
        'skor' => $skor,
        'total_bobot' => $totalBobot
    ], "Quiz selesai! Nilai: $nilai");
}

function updateSoal($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $query = "UPDATE soal_nautika SET pertanyaan = :pertanyaan, pilihan_a = :pilihan_a, 
              pilihan_b = :pilihan_b, pilihan_c = :pilihan_c, pilihan_d = :pilihan_d, 
              jawaban_benar = :jawaban_benar, bobot = :bobot WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data['id']);
    $stmt->bindParam(":pertanyaan", $data['pertanyaan']);
    $stmt->bindParam(":pilihan_a", $data['pilihan_a']);
    $stmt->bindParam(":pilihan_b", $data['pilihan_b']);
    $stmt->bindParam(":pilihan_c", $data['pilihan_c']);
    $stmt->bindParam(":pilihan_d", $data['pilihan_d']);
    $stmt->bindParam(":jawaban_benar", $data['jawaban_benar']);
    $stmt->bindParam(":bobot", $data['bobot']);
    
    if($stmt->execute()) {
        $database->sendResponse(true, null, "Soal berhasil diupdate");
    } else {
        $database->sendResponse(false, null, "Gagal update soal", 500);
    }
}

function deleteSoal($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $query = "DELETE FROM soal_nautika WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data['id']);
    
    if($stmt->execute()) {
        $database->sendResponse(true, null, "Soal berhasil dihapus");
    } else {
        $database->sendResponse(false, null, "Gagal hapus soal", 500);
    }
}
?>