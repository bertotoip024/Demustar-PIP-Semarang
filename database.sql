-- =====================================================
-- DATABASE DEMUSTAR NAUTIKA
-- Politeknik Ilmu Pelayaran Semarang
-- =====================================================

-- Buat database
CREATE DATABASE IF NOT EXISTS demustar_nautika;
USE demustar_nautika;

-- =====================================================
-- TABEL 1: data_taruna (Data taruna/pengguna)
-- =====================================================
CREATE TABLE IF NOT EXISTS data_taruna (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nisn VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(20),
    jurusan VARCHAR(50) DEFAULT 'Nautika',
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    foto VARCHAR(255),
    poin INT DEFAULT 0,
    level ENUM('admin','pengajar','taruna') DEFAULT 'taruna',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL 2: materi_nautika (Materi bahan ajar)
-- =====================================================
CREATE TABLE IF NOT EXISTS materi_nautika (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    bab VARCHAR(50),
    deskripsi TEXT,
    file_path VARCHAR(500),
    file_type ENUM('pdf', 'video', 'ppt', 'doc', 'other') DEFAULT 'pdf',
    thumbnail VARCHAR(500),
    durasi INT DEFAULT 0,
    created_by INT,
    views INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES data_taruna(id) ON DELETE SET NULL,
    INDEX idx_bab (bab),
    INDEX idx_created (created_by),
    INDEX idx_active (is_active),
    FULLTEXT INDEX idx_search (judul, deskripsi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL 3: soal_nautika (Soal ujian/quiz)
-- =====================================================
CREATE TABLE IF NOT EXISTS soal_nautika (
    id INT PRIMARY KEY AUTO_INCREMENT,
    materi_id INT,
    pertanyaan TEXT NOT NULL,
    pilihan_a TEXT,
    pilihan_b TEXT,
    pilihan_c TEXT,
    pilihan_d TEXT,
    jawaban_benar CHAR(1) CHECK (jawaban_benar IN ('A','B','C','D')),
    bobot INT DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (materi_id) REFERENCES materi_nautika(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES data_taruna(id) ON DELETE SET NULL,
    INDEX idx_materi (materi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL 4: tugas_nautika (Tugas)
-- =====================================================
CREATE TABLE IF NOT EXISTS tugas_nautika (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    materi_id INT,
    deadline DATE,
    max_nilai INT DEFAULT 100,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (materi_id) REFERENCES materi_nautika(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES data_taruna(id) ON DELETE SET NULL,
    INDEX idx_deadline (deadline),
    INDEX idx_materi (materi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL 5: pengumpulan_tugas (Pengumpulan tugas taruna)
-- =====================================================
CREATE TABLE IF NOT EXISTS pengumpulan_tugas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tugas_id INT,
    taruna_id INT,
    file_path VARCHAR(500),
    komentar TEXT,
    nilai DECIMAL(5,2),
    status ENUM('pending','dikumpul','dinilai') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tugas_id) REFERENCES tugas_nautika(id) ON DELETE CASCADE,
    FOREIGN KEY (taruna_id) REFERENCES data_taruna(id) ON DELETE CASCADE,
    INDEX idx_tugas (tugas_id),
    INDEX idx_taruna (taruna_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_pengumpulan (tugas_id, taruna_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL 6: log_aktivitas (Catatan aktivitas pengguna)
-- =====================================================
CREATE TABLE IF NOT EXISTS log_aktivitas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    aktivitas VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES data_taruna(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGER: Update poin otomatis saat tugas dinilai
-- =====================================================
DELIMITER //
CREATE TRIGGER update_poin_after_nilai
AFTER UPDATE ON pengumpulan_tugas
FOR EACH ROW
BEGIN
    IF NEW.nilai IS NOT NULL AND OLD.nilai IS NULL THEN
        UPDATE data_taruna 
        SET poin = poin + FLOOR(NEW.nilai / 10)
        WHERE id = NEW.taruna_id;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- DATA SAMPLE (Data awal untuk testing)
-- =====================================================

-- Admin & Pengajar
INSERT INTO data_taruna (nisn, nama, kelas, username, password, email, level, poin) VALUES
('ADMIN001', 'Administrator', 'Admin', 'admin', MD5('admin123'), 'admin@demustar.com', 'admin', 0),
('PJ001', 'Capt. Andi Wijaya, M.Mar', 'Pengajar', 'pengajar', MD5('pengajar123'), 'andi.wijaya@demustar.com', 'pengajar', 0),
('PJ002', 'Capt. Budi Santoso, M.Si', 'Pengajar', 'budi.pengajar', MD5('budi123'), 'budi.santoso@demustar.com', 'pengajar', 0);

-- Taruna Nautika
INSERT INTO data_taruna (nisn, nama, kelas, username, password, email, level, poin) VALUES
('2024001', 'Andi Setiawan', 'Nautika A', 'andi', MD5('andi123'), 'andi@student.demustar.com', 'taruna', 120),
('2024002', 'Budi Santoso', 'Nautika A', 'budi', MD5('budi123'), 'budi@student.demustar.com', 'taruna', 85),
('2024003', 'Citra Dewi Lestari', 'Nautika B', 'citra', MD5('citra123'), 'citra@student.demustar.com', 'taruna', 240),
('2024004', 'Dian Pratama Putra', 'Nautika B', 'dian', MD5('dian123'), 'dian@student.demustar.com', 'taruna', 55),
('2024005', 'Eka Fitriani', 'Nautika A', 'eka', MD5('eka123'), 'eka@student.demustar.com', 'taruna', 310),
('2024006', 'Fajar Nugroho', 'Nautika A', 'fajar', MD5('fajar123'), 'fajar@student.demustar.com', 'taruna', 175),
('2024007', 'Gita Permatasari', 'Nautika B', 'gita', MD5('gita123'), 'gita@student.demustar.com', 'taruna', 90),
('2024008', 'Hendra Kurniawan', 'Nautika C', 'hendra', MD5('hendra123'), 'hendra@student.demustar.com', 'taruna', 45);

-- Materi Pembelajaran
INSERT INTO materi_nautika (judul, bab, deskripsi, file_type, created_by, views) VALUES
('Navigasi Darat & Laut - Bab 1', 'Navigasi', 'Mempelajari dasar-dasar navigasi darat dan laut menggunakan peta dan kompas. Materi ini mencakup pengertian navigasi, jenis-jenis navigasi, dan peralatan navigasi dasar.', 'pdf', 2, 150),
('Navigasi Darat & Laut - Bab 2', 'Navigasi', 'Teknik membaca peta laut, menentukan posisi kapal, dan perhitungan jarak pelayaran.', 'pdf', 2, 98),
('Meteorologi Maritim - Bab 1', 'Meteorologi', 'Memahami pola cuaca, awan, angin, dan interpretasi data meteorologi untuk keselamatan pelayaran.', 'pdf', 2, 210),
('Meteorologi Maritim - Bab 2', 'Meteorologi', 'Prakiraan cuaca, membaca peta sinoptik, dan antisipasi cuaca buruk di laut.', 'video', 2, 75),
('Ilmu Pelayaran - Bab 1', 'Ilmu Pelayaran', 'Dasar-dasar ilmu pelayaran, manajemen kapal, dan prosedur keselamatan di atas kapal.', 'video', 2, 210),
('Ilmu Pelayaran - Bab 2', 'Ilmu Pelayaran', 'Penanganan darurat, prosedur abandon ship, dan penggunaan life saving apparatus.', 'ppt', 2, 45),
('Komunikasi Kapal - Bab 1', 'Komunikasi', 'Prosedur komunikasi radio, kode sinyal maritim, dan GMDSS (Global Maritime Distress Safety System).', 'ppt', 2, 88),
('Komunikasi Kapal - Bab 2', 'Komunikasi', 'Penggunaan VHF radio, prosedur Mayday, dan komunikasi internasional di laut.', 'pdf', 2, 62),
('Keselamatan Pelayaran - Bab 1', 'Keselamatan', 'Standar keselamatan internasional SOLAS dan implementasinya di kapal.', 'pdf', 3, 120),
('Keselamatan Pelayaran - Bab 2', 'Keselamatan', 'Prosedur ISM Code dan audit keselamatan kapal.', 'pdf', 3, 85);

-- Soal untuk materi Navigasi (materi_id = 1)
INSERT INTO soal_nautika (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, bobot, created_by) VALUES
(1, 'Apa yang dimaksud dengan garis bujur (longitude)?', 'Garis vertikal yang menghubungkan kutub utara dan selatan', 'Garis horizontal sejajar khatulistiwa', 'Garis yang membagi bumi menjadi belahan utara dan selatan', 'Garis yang menunjukkan kedalaman laut', 'A', 1, 2),
(1, 'Alat yang digunakan untuk mengukur sudut elevasi benda langit adalah...', 'Barometer', 'Sekstan', 'Kompass', 'Log book', 'B', 1, 2),
(1, 'Berapa derajat garis khatulistiwa?', '0°', '90°', '180°', '360°', 'A', 1, 2),
(1, 'Satuan kecepatan dalam pelayaran adalah...', 'Km/jam', 'Meter/detik', 'Knot', 'Mil/jam', 'C', 1, 2),
(1, 'Apa kepanjangan dari GPS?', 'Global Positioning System', 'Global Position Signal', 'General Positioning System', 'Geographical Position System', 'A', 1, 2);

-- Soal untuk materi Meteorologi (materi_id = 3)
INSERT INTO soal_nautika (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, bobot, created_by) VALUES
(3, 'Alat yang digunakan untuk mengukur tekanan udara adalah...', 'Termometer', 'Barometer', 'Hygrometer', 'Anemometer', 'B', 1, 2),
(3, 'Apa yang dimaksud dengan angin muson?', 'Angin yang berhembus dari laut ke darat', 'Angin yang berubah arah setiap musim', 'Angin kencang disertai hujan', 'Angin yang bertiup sepanjang tahun', 'B', 1, 2),
(3, 'Berapa skala Beaufort untuk angin topan?', 'Skala 6', 'Skala 8', 'Skala 10', 'Skala 12', 'D', 1, 2);

-- Soal untuk materi Ilmu Pelayaran (materi_id = 5)
INSERT INTO soal_nautika (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, bobot, created_by) VALUES
(5, 'Apa singkatan dari SOLAS?', 'Safety of Life at Sea', 'Safety of Living at Sea', 'Standard of Life at Sea', 'Safety on Life and Ship', 'A', 1, 2),
(5, 'Berapa jumlah sekoci minimal yang harus tersedia di kapal?', '1 buah', '2 buah', '3 buah', '4 buah', 'B', 1, 2),
(5, 'Apa fungsi dari ballast tank?', 'Menyimpan bahan bakar', 'Menstabilkan kapal', 'Menyimpan air tawar', 'Tempat mesin', 'B', 1, 2);

-- Tugas
INSERT INTO tugas_nautika (judul, deskripsi, materi_id, deadline, max_nilai, created_by) VALUES
('Tugas Navigasi - Menentukan Posisi Kapal', 'Buatlah perhitungan menentukan posisi kapal berdasarkan data yang diberikan. Kerjakan di kertas dan upload dalam bentuk PDF.', 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 100, 2),
('Tugas Meteorologi - Analisis Cuaca', 'Analisis peta cuaca yang telah diberikan dan buat laporan prakiraan cuaca untuk rute pelayaran Jakarta - Surabaya.', 3, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 100, 2),
('Tugas Ilmu Pelayaran - Prosedur Darurat', 'Jelaskan prosedur abandon ship yang benar beserta gambar ilustrasinya.', 5, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 100, 2),
('Tugas Komunikasi - Prosedur Mayday', 'Buat script komunikasi radio untuk keadaan darurat mayday yang benar sesuai standar internasional.', 7, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 100, 3),
('Tugas Keselamatan - ISM Code', 'Buat ringkasan tentang ISM Code dan implementasinya di kapal niaga. Minimal 500 kata.', 9, DATE_ADD(CURDATE(), INTERVAL 21 DAY), 100, 3);

-- =====================================================
-- STORED PROCEDURE untuk laporan
-- =====================================================

-- Procedure: Get statistik dashboard
DELIMITER //
CREATE PROCEDURE get_dashboard_stats()
BEGIN
    -- Total materi
    SELECT COUNT(*) as total_materi FROM materi_nautika WHERE is_active = 1;
    
    -- Total soal
    SELECT COUNT(*) as total_soal FROM soal_nautika;
    
    -- Total tugas aktif
    SELECT COUNT(*) as total_tugas FROM tugas_nautika WHERE deadline >= CURDATE();
    
    -- Total taruna
    SELECT COUNT(*) as total_taruna FROM data_taruna WHERE level = 'taruna';
END//
DELIMITER ;

-- Procedure: Get materi populer
DELIMITER //
CREATE PROCEDURE get_materi_populer(IN limit_count INT)
BEGIN
    SELECT m.*, t.nama as pengajar 
    FROM materi_nautika m
    LEFT JOIN data_taruna t ON m.created_by = t.id
    WHERE m.is_active = 1
    ORDER BY m.views DESC
    LIMIT limit_count;
END//
DELIMITER ;

-- =====================================================
-- VIEW untuk laporan mudah
-- =====================================================

-- View: Laporan tugas per taruna
CREATE VIEW v_laporan_tugas AS
SELECT 
    t.judul as tugas,
    dt.nama as taruna,
    pt.status,
    pt.nilai,
    pt.submitted_at
FROM pengumpulan_tugas pt
JOIN tugas_nautika t ON pt.tugas_id = t.id
JOIN data_taruna dt ON pt.taruna_id = dt.id;

-- View: Statistik materi
CREATE VIEW v_statistik_materi AS
SELECT 
    m.judul,
    m.bab,
    m.views,
    COUNT(DISTINCT s.id) as total_soal,
    m.created_at
FROM materi_nautika m
LEFT JOIN soal_nautika s ON m.id = s.materi_id
WHERE m.is_active = 1
GROUP BY m.id;

-- =====================================================
-- INDEX untuk performa optimal
-- =====================================================

-- Composite index untuk pencarian
CREATE INDEX idx_materi_search ON materi_nautika(bab, is_active);
CREATE INDEX idx_tugas_deadline_status ON tugas_nautika(deadline);
CREATE INDEX idx_pengumpulan_status ON pengumpulan_tugas(status, submitted_at);

-- =====================================================
-- SELESAI
-- =====================================================