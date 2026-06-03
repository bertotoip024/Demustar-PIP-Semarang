<?php
/**
 * DEMUSTAR - Debug 2: Cek Struktur Tabel
 * Path: C:\xampp\htdocs\demustar\api\auth\debug2.php
 * Akses: http://localhost/demustar/api/auth/debug2.php
 * HAPUS setelah selesai!
 */
ini_set('display_errors', '1');
ini_set('html_errors', '0');
error_reporting(E_ALL);

header("Content-Type: text/html; charset=UTF-8");
echo "<style>
  body{font-family:monospace;padding:20px;background:#111;color:#eee;font-size:14px;}
  .ok{color:#4ade80;} .err{color:#f87171;} .warn{color:#fbbf24;}
  table{border-collapse:collapse;width:100%;margin:10px 0;}
  th,td{border:1px solid #444;padding:8px 12px;text-align:left;}
  th{background:#333;}
  pre{background:#222;padding:10px;border-radius:6px;overflow-x:auto;}
  h3{color:#FFD700;border-bottom:1px solid #333;padding-bottom:6px;}
</style>";

echo "<h2>DEMUSTAR Debug 2 — Struktur Tabel</h2>";

// Load config
$loaded = false;
foreach ([__DIR__."/../config.php", __DIR__."/config.php"] as $p) {
    if (file_exists($p)) { require_once $p; $loaded = true; echo "<p class='ok'>✓ Config: $p</p>"; break; }
}
if (!$loaded) { echo "<p class='err'>✗ config.php tidak ditemukan</p>"; exit; }

$conn = db();
echo "<p class='ok'>✓ Koneksi DB berhasil</p>";

// Cek database aktif
$dbRow = $conn->query("SELECT DATABASE() as db")->fetch_assoc();
echo "<p class='warn'>📦 Database aktif: <strong>{$dbRow['db']}</strong></p>";

// List semua tabel
echo "<h3>Semua Tabel di Database</h3>";
$tables = $conn->query("SHOW TABLES");
echo "<table><tr><th>Nama Tabel</th></tr>";
while ($t = $tables->fetch_row()) {
    echo "<tr><td>{$t[0]}</td></tr>";
}
echo "</table>";

// Struktur tabel users
echo "<h3>Struktur Tabel `users`</h3>";
$cols = $conn->query("DESCRIBE `users`");
if (!$cols) {
    echo "<p class='err'>✗ Tabel users tidak ada atau error: " . $conn->error . "</p>";
} else {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    $colNames = [];
    while ($col = $cols->fetch_assoc()) {
        $colNames[] = $col['Field'];
        echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";

    // Sample data (tanpa password)
    echo "<h3>Sample Data Users (tanpa password)</h3>";
    $safeCols = array_filter($colNames, fn($c) => !str_contains(strtolower($c), 'pass') && !str_contains(strtolower($c), 'hash'));
    $selectCols = implode(', ', array_map(fn($c) => "`$c`", $safeCols));
    $rows = $conn->query("SELECT $selectCols FROM `users` LIMIT 6");
    if ($rows && $rows->num_rows > 0) {
        echo "<table><tr>";
        foreach ($safeCols as $c) echo "<th>$c</th>";
        echo "</tr>";
        while ($row = $rows->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Identifikasi kolom NIT
    echo "<h3>Identifikasi Kolom NIT / Username</h3>";
    $nitCandidates = ['nit', 'mt', 'username', 'user', 'nim', 'nis', 'no_induk', 'nomor_induk', 'id_user', 'login'];
    foreach ($nitCandidates as $candidate) {
        if (in_array($candidate, $colNames)) {
            echo "<p class='ok'>✓ Ditemukan kolom: <strong>$candidate</strong></p>";
        }
    }

    // SQL yang harus dijalankan untuk fix
    echo "<h3>SQL Fix yang Perlu Dijalankan</h3>";
    echo "<p class='warn'>Jalankan salah satu di phpMyAdmin sesuai nama kolom yang ada:</p>";
    
    // Cek kolom mana yang ada
    $nitCol = null;
    foreach (['nit','mt','username','nim'] as $c) {
        if (in_array($c, $colNames)) { $nitCol = $c; break; }
    }
    
    if ($nitCol && $nitCol !== 'nit') {
        echo "<p class='warn'>Kolom NIT kamu bernama: <strong>$nitCol</strong> (bukan 'nit')</p>";
        echo "<p>Opsi 1 — Tambah kolom alias <code>nit</code>:</p>";
        echo "<pre>ALTER TABLE users ADD COLUMN nit VARCHAR(40) GENERATED ALWAYS AS (`$nitCol`) VIRTUAL;</pre>";
        echo "<p>Opsi 2 — Reset password berdasarkan kolom <strong>$nitCol</strong>:</p>";
        echo "<pre>UPDATE users SET password_hash = SHA2(`$nitCol`, 256);</pre>";
    } elseif ($nitCol === 'nit') {
        echo "<p class='ok'>✓ Kolom 'nit' sudah ada. Reset password:</p>";
        echo "<pre>UPDATE users SET password_hash = SHA2(nit, 256);</pre>";
    } else {
        echo "<p class='err'>✗ Tidak ditemukan kolom NIT/username yang dikenal.</p>";
        echo "<p>Kolom yang ada: " . implode(', ', $colNames) . "</p>";
    }
}

echo "<hr><p style='color:#555'>Hapus debug2.php setelah selesai!</p>";
?>
