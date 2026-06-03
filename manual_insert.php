<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'data_taruna';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi GAGAL: " . $conn->connect_error);
}

echo "<h2>Test Insert Manual Aspirasi</h2>";

// Insert data test
$sql = "INSERT INTO aspirasi (nama, kontak, kategori, isi, status) VALUES 
        ('Budi Santoso', '08123456789', 'Fasilitas', 'Mohon perbaikan AC di ruang kelas 3B yang sudah tidak dingin selama 2 minggu', 'pending'),
        ('Andi Pratama', '08234567890', 'Akademik', 'Usulan penambahan jam belajar di perpustakaan', 'pending'),
        ('Siti Rahma', '08345678901', 'Kebersihan', 'Mohon penambahan tempat sampah di area parkir', 'pending')";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>✅ Insert berhasil!</p>";
} else {
    echo "<p style='color:red'>❌ Insert gagal: " . $conn->error . "</p>";
}

// Tampilkan data
$result = $conn->query("SELECT * FROM aspirasi ORDER BY id DESC");
echo "<h3>Data Aspirasi di Database:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Nama</th><th>Kontak</th><th>Kategori</th><th>Isi</th><th>Status</th><th>Tanggal</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nama']}</td>";
    echo "<td>{$row['kontak']}</td>";
    echo "<td>{$row['kategori']}</td>";
    echo "<td>" . substr($row['isi'], 0, 50) . "...</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['tanggal']}</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>