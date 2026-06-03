<?php
// api/tugas.php
require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            getTugasById($db, $_GET['id']);
        } elseif(isset($_GET['materi_id'])) {
            getTugasByMateri($db, $_GET['materi_id']);
        } else {
            getAllTugas($db);
        }
        break;
    case 'POST':
        if(isset($_GET['submit'])) {
            submitTugas($db);
        } else {
            createTugas($db);
        }
        break;
    case 'PUT':
        updateTugas($db);
        break;
    case 'DELETE':
        deleteTugas($db);
        break;
}

function getAllTugas($db) {
    $query = "SELECT t.*, m.judul as materi_judul, tu.nama as pembuat 
              FROM tugas_nautika t 
              LEFT JOIN materi_nautika m ON t.materi_id = m.id 
              LEFT JOIN data_taruna tu ON t.created_by = tu.id 
              ORDER BY t.deadline ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $database->sendResponse(true, $stmt->fetchAll());
}

function getTugasById($db, $id) {
    $query = "SELECT t.*, m.judul as materi_judul, tu.nama as pembuat 
              FROM tugas_nautika t 
              LEFT JOIN materi_nautika m ON t.materi_id = m.id 
              LEFT JOIN data_taruna tu ON t.created_by = tu.id 
              WHERE t.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        $database->sendResponse(false, null, "Tugas tidak ditemukan", 404);
    }
    $database->sendResponse(true, $stmt->fetch());
}

function getTugasByMateri($db, $materi_id) {
    $query = "SELECT * FROM tugas_nautika WHERE materi_id = :materi_id ORDER BY deadline ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":materi_id", $materi_id);
    $stmt->execute();
    $database->sendResponse(true, $stmt->fetchAll());
}

function createTugas($db) {
    if(!isLoggedIn()) {
        $database->sendResponse(false, null, "Harus login", 401);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $query = "INSERT INTO tugas_nautika (judul, deskripsi, materi_id, deadline, max_nilai, created_by) 
              VALUES (:judul, :deskripsi, :materi_id, :deadline, :max_nilai, :created_by)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":judul", $data['judul']);
    $stmt->bindParam(":deskripsi", $data['deskripsi']);
    $stmt->bindParam(":materi_id", $data['materi_id']);
    $stmt->bindParam(":deadline", $data['deadline']);
    $stmt->bindParam(":max_nilai", $data['max_nilai']);
    $stmt->bindParam(":created_by", $_SESSION['user_id']);
    
    if($stmt->execute()) {
        $database->sendResponse(true, ['id' => $db->lastInsertId()], "Tugas berhasil dibuat");
    } else {
        $database->send