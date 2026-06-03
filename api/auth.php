<?php
// config/db.php
class Database {
    private $host = "localhost";
    private $db_name = "demustar_nautika";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}

// SESSION GLOBAL - SAMA UNTUK SEMUA HALAMAN
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 7200,
        'path' => '/',           // KRUSIAL: biar semua folder bisa akses
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_name('DEMUSTAR_SESSION');
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUser($db) {
    if (!isLoggedIn()) return null;
    $query = "SELECT id, nisn, nama, kelas, jurusan, username, email, level, poin 
              FROM data_taruna WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->fetch();
}
?>