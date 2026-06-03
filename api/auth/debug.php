<?php
/**
 * DEMUSTAR - Debug Login
 * Letakkan di: C:\xampp\htdocs\demustar\api\auth\debug.php
 * Akses via: http://localhost/demustar/api/auth/debug.php
 * HAPUS file ini setelah masalah selesai!
 */
header("Content-Type: text/html; charset=UTF-8");

echo "<h2>DEMUSTAR Login Debug</h2>";
echo "<style>body{font-family:monospace;padding:20px;background:#111;color:#eee;} .ok{color:#4ade80;} .err{color:#f87171;} .warn{color:#fbbf24;} pre{background:#222;padding:10px;border-radius:8px;overflow-x:auto;}</style>";

// 1. Cek PHP version
echo "<h3>1. PHP Version</h3>";
echo "<p class='ok'>PHP " . phpversion() . "</p>";

// 2. Cek config.php
echo "<h3>2. Config.php</h3>";
$configPaths = [
    __DIR__ . "/../config.php",
    __DIR__ . "/../../api/config.php",
    dirname(__DIR__) . "/config.php",
];

$configFound = false;
foreach ($configPaths as $path) {
    $exists = file_exists($path);
    $color  = $exists ? "ok" : "err";
    $status = $exists ? "✓ ADA" : "✗ TIDAK ADA";
    echo "<p class='$color'>$status → $path</p>";
    if ($exists && !$configFound) {
        $configFound = true;
        echo "<p class='ok'>→ Menggunakan: $path</p>";
        require_once $path;
    }
}

if (!$configFound) {
    echo "<p class='err'>FATAL: config.php tidak ditemukan di semua path!</p>";
    exit;
}

// 3. Cek fungsi db()
echo "<h3>3. Database Connection</h3>";
if (!function_exists('db')) {
    echo "<p class='err'>FATAL: fungsi db() tidak ada di config.php</p>";
    exit;
}

try {
    $conn = db();
    echo "<p class='ok'>✓ Koneksi database berhasil</p>";
} catch (Exception $e) {
    echo "<p class='err'>✗ Koneksi gagal: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 4. Cek tabel users
echo "<h3>4. Tabel Users</h3>";
$result = $conn->query("SELECT id, nit, nama, role, tanggal_lahir, LEFT(password_hash,20) as hash_awal FROM users LIMIT 10");
if (!$result) {
    echo "<p class='err'>✗ Query gagal: " . htmlspecialchars($conn->error) . "</p>";
    exit;
}

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr style='background:#333;'><th>ID</th><th>NIT</th><th>Nama</th><th>Role</th><th>Tanggal Lahir</th><th>Hash (20 char)</th><th>Hash Type</th></tr>";

while ($row = $result->fetch_assoc()) {
    // Cek tipe hash
    $fullHash = "";
    $stmtH = $conn->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmtH->bind_param("i", $row['id']);
    $stmtH->execute();
    $rH = $stmtH->get_result()->fetch_assoc();
    $fullHash = $rH['password_hash'] ?? "";
    
    $info = password_get_info($fullHash);
    $hashType = !empty($info['algo']) ? "bcrypt/argon2 ✓" : "SHA256/legacy";
    
    // Cek apakah SHA256(nit) == password_hash
    $sha256Match = (hash("sha256", $row['nit']) === strtolower($fullHash)) ? "✓ SHA256(NIT) cocok" : "✗";
    
    echo "<tr>";
    echo "<td style='padding:6px'>{$row['id']}</td>";
    echo "<td style='padding:6px'>{$row['nit']}</td>";
    echo "<td style='padding:6px'>{$row['nama']}</td>";
    echo "<td style='padding:6px'>{$row['role']}</td>";
    echo "<td style='padding:6px'>{$row['tanggal_lahir']}</td>";
    echo "<td style='padding:6px;font-size:11px'>{$row['hash_awal']}...</td>";
    echo "<td style='padding:6px;font-size:11px'>$hashType<br><span style='color:#fbbf24'>$sha256Match</span></td>";
    echo "</tr>";
}
echo "</table>";

// 5. Test login manual
echo "<h3>5. Test Login Manual (NIT: 220001)</h3>";
$testNit  = "220001";
$testPass = "220001"; // password = NIT
$testDob  = "2005-03-15";

$stmt = $conn->prepare("SELECT * FROM users WHERE nit=? LIMIT 1");
$stmt->bind_param("s", $testNit);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "<p class='err'>✗ User NIT $testNit tidak ditemukan</p>";
} else {
    echo "<p class='ok'>✓ User ditemukan: {$user['nama']}</p>";
    echo "<p>Tanggal lahir di DB: <strong>{$user['tanggal_lahir']}</strong></p>";
    echo "<p>Tanggal lahir test: <strong>$testDob</strong></p>";
    
    // Cek DOB
    if ($user['tanggal_lahir'] === $testDob) {
        echo "<p class='ok'>✓ Tanggal lahir cocok</p>";
    } else {
        echo "<p class='err'>✗ Tanggal lahir TIDAK cocok! DB: {$user['tanggal_lahir']} vs Input: $testDob</p>";
    }
    
    // Cek password
    $stored = $user['password_hash'] ?? "";
    $info = password_get_info($stored);
    
    if (!empty($info['algo'])) {
        // bcrypt
        $ok = password_verify($testPass, $stored);
        echo "<p>" . ($ok ? "<span class='ok'>✓ Password bcrypt cocok</span>" : "<span class='err'>✗ Password bcrypt TIDAK cocok</span>") . "</p>";
    } else {
        // SHA256
        $sha = hash("sha256", $testPass);
        $ok  = ($sha === strtolower($stored));
        echo "<p>" . ($ok ? "<span class='ok'>✓ Password SHA256 cocok</span>" : "<span class='err'>✗ Password SHA256 TIDAK cocok</span>") . "</p>";
        echo "<p class='warn'>SHA256('$testPass') = " . substr($sha, 0, 30) . "...</p>";
        echo "<p class='warn'>DB hash =             " . substr($stored, 0, 30) . "...</p>";
    }
}

// 6. Solusi reset password
echo "<h3>6. Solusi: Reset Password ke SHA256(NIT)</h3>";
echo "<p class='warn'>Jalankan SQL ini di phpMyAdmin jika password tidak cocok:</p>";
echo "<pre>UPDATE users SET password_hash = SHA2(nit, 256);</pre>";
echo "<p class='warn'>Kemudian login dengan: NIT = password</p>";

echo "<hr><p style='color:#666'>Hapus file debug.php setelah selesai!</p>";
?>
