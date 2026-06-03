<?php
// api/materi.php
require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            getMateriById($db, $_GET['id']);
        } elseif(isset($_GET['search'])) {
            searchMateri($db, $_GET['search']);
        } elseif(isset($_GET['bab'])) {
            getMateriByBab($db, $_GET['bab']);
        } else {
            getAllMateri($db);
        }
        break;
    case 'POST':
        uploadMateri($db);
        break;
    case 'PUT':
        updateMateri($db);
        break;
    case 'DELETE':
        deleteMateri($db);
        break;
}

function getAllMateri($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT m.*, t.nama as pengajar, t.id as pengajar_id,
              (SELECT COUNT(*) FROM soal_nautika WHERE materi_id = m.id) as total_soal
              FROM materi_nautika m 
              LEFT JOIN data_taruna t ON m.created_by = t.id 
              WHERE m.is_active = 1
              ORDER BY m.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM materi_nautika WHERE is_active = 1";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];
    
    $database->sendResponse(true, [
        'data' => $result,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

function getMateriById($db, $id) {
    $query = "SELECT m.*, t.nama as pengajar, t.nisn as pengajar_nisn 
              FROM materi_nautika m 
              LEFT JOIN data_taruna t ON m.created_by = t.id 
              WHERE m.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        $database->sendResponse(false, null, "Materi tidak ditemukan", 404);
    }
    
    $result = $stmt->fetch();
    
    // Update views
    $updateViews = "UPDATE materi_nautika SET views = views + 1 WHERE id = :id";
    $stmtViews = $db->prepare($updateViews);
    $stmtViews->bindParam(":id", $id);
    $stmtViews->execute();
    
    // Get related soal
    $soalQuery = "SELECT id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, bobot 
                  FROM soal_nautika WHERE materi_id = :materi_id";
    $soalStmt = $db->prepare($soalQuery);
    $soalStmt->bindParam(":materi_id", $id);
    $soalStmt->execute();
    $result['soal'] = $soalStmt->fetchAll();
    
    $database->sendResponse(true, $result);
}

function uploadMateri($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login terlebih dahulu", 401);
    }
    
    $uploadDir = "../uploads/materi/";
    if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $judul = $_POST['judul'] ?? '';
    $bab = $_POST['bab'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $created_by = $_SESSION['user_id'];
    
    if(empty($judul)) {
        $database->sendResponse(false, null, "Judul materi wajib diisi", 400);
    }
    
    $filePath = "";
    $fileType = "other";
    
    if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'mp4', 'avi', 'mov', 'ppt', 'pptx', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        if(!in_array($ext, $allowed)) {
            $database->sendResponse(false, null, "Tipe file tidak diizinkan", 400);
        }
        
        $fileName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if(move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            $filePath = "uploads/materi/" . $fileName;
            
            $typeMap = ['pdf' => 'pdf', 'mp4' => 'video', 'avi' => 'video', 'mov' => 'video', 
                        'ppt' => 'ppt', 'pptx' => 'ppt', 'doc' => 'doc', 'docx' => 'doc'];
            $fileType = $typeMap[$ext] ?? 'other';
        } else {
            $database->sendResponse(false, null, "Gagal upload file", 500);
        }
    }
    
    $query = "INSERT INTO materi_nautika (judul, bab, deskripsi, file_path, file_type, created_by) 
              VALUES (:judul, :bab, :deskripsi, :file_path, :file_type, :created_by)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":judul", $judul);
    $stmt->bindParam(":bab", $bab);
    $stmt->bindParam(":deskripsi", $deskripsi);
    $stmt->bindParam(":file_path", $filePath);
    $stmt->bindParam(":file_type", $fileType);
    $stmt->bindParam(":created_by", $created_by);
    
    if($stmt->execute()) {
        $database->sendResponse(true, ['id' => $db->lastInsertId()], "Materi berhasil diupload");
    } else {
        $database->sendResponse(false, null, "Gagal menyimpan materi", 500);
    }
}

function searchMateri($db, $keyword) {
    $query = "SELECT m.*, t.nama as pengajar 
              FROM materi_nautika m 
              LEFT JOIN data_taruna t ON m.created_by = t.id 
              WHERE m.judul LIKE :keyword OR m.bab LIKE :keyword OR m.deskripsi LIKE :keyword
              ORDER BY m.created_at DESC";
    $stmt = $db->prepare($query);
    $searchTerm = "%{$keyword}%";
    $stmt->bindParam(":keyword", $searchTerm);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $database->sendResponse(true, $result);
}

function getMateriByBab($db, $bab) {
    $query = "SELECT * FROM materi_nautika WHERE bab = :bab AND is_active = 1 ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":bab", $bab);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $database->sendResponse(true, $result);
}

function updateMateri($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $query = "UPDATE materi_nautika SET judul = :judul, bab = :bab, deskripsi = :deskripsi 
              WHERE id = :id AND (created_by = :user_id OR (SELECT level FROM data_taruna WHERE id = :user_id) = 'admin')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data['id']);
    $stmt->bindParam(":judul", $data['judul']);
    $stmt->bindParam(":bab", $data['bab']);
    $stmt->bindParam(":deskripsi", $data['deskripsi']);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    
    if($stmt->execute() && $stmt->rowCount() > 0) {
        $database->sendResponse(true, null, "Materi berhasil diupdate");
    } else {
        $database->sendResponse(false, null, "Gagal update atau tidak punya akses", 403);
    }
}

function deleteMateri($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $query = "UPDATE materi_nautika SET is_active = 0 
              WHERE id = :id AND (created_by = :user_id OR (SELECT level FROM data_taruna WHERE id = :user_id) = 'admin')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data['id']);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    
    if($stmt->execute() && $stmt->rowCount() > 0) {
        $database->sendResponse(true, null, "Materi berhasil dihapus");
    } else {
        $database->sendResponse(false, null, "Gagal hapus atau tidak punya akses", 403);
    }
}
?>