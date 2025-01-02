<?php
$host = 'localhost';
$db   = 'db_spp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS db_spp");
    $pdo->exec("USE db_spp");

    // Create tables
    $pdo->exec("CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'siswa', 'kasir', 'kepala_sekolah') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_role (role)
    )");

    $pdo->exec("CREATE TABLE siswa (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nis VARCHAR(20) UNIQUE NOT NULL,
        nama VARCHAR(100) NOT NULL,
        kelas VARCHAR(20) NOT NULL,
        alamat TEXT,
        no_telp VARCHAR(15),
        user_id INT,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_kelas (kelas),
        INDEX idx_nama (nama)
    )");

    $pdo->exec("CREATE TABLE spp_periode (
        id INT PRIMARY KEY AUTO_INCREMENT,
        tahun_ajaran VARCHAR(10) NOT NULL,
        nominal DECIMAL(10,2) NOT NULL,
        INDEX idx_tahun (tahun_ajaran)
    )");

    $pdo->exec("CREATE TABLE pembayaran_spp (
        id INT PRIMARY KEY AUTO_INCREMENT,
        siswa_id INT NOT NULL,
        periode_id INT NOT NULL,
        tanggal_bayar DATE NOT NULL,
        bulan TINYINT NOT NULL,
        jumlah_bayar DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
        kasir_id INT NOT NULL,
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (siswa_id) REFERENCES siswa(id),
        FOREIGN KEY (periode_id) REFERENCES spp_periode(id),
        FOREIGN KEY (kasir_id) REFERENCES users(id),
        INDEX idx_tanggal (tanggal_bayar),
        INDEX idx_status (status)
    )");

    // Insert users with hashed passwords
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

    $users = [
        ['admin', '12345', 'admin'],
        ['kepala.sekolah', '12345', 'kepala_sekolah'],
        ['kasir1', '12345', 'kasir'],
        ['siswa1', '12345', 'siswa'],
        ['siswa2', '12345', 'siswa']
    ];

    foreach ($users as $user) {
        $stmt->execute([$user[0], password_hash($user[1], PASSWORD_DEFAULT), $user[2]]);
    }

    // Insert sample students
    $pdo->exec("INSERT INTO siswa (nis, nama, kelas, alamat, no_telp, user_id) VALUES 
        ('2024001', 'John Doe', 'X-IPA-1', 'Jl. Contoh No. 1', '081234567890', 4),
        ('2024002', 'Jane Smith', 'X-IPA-2', 'Jl. Sample No. 2', '081234567891', 5)");

    // Insert sample SPP periods
    $pdo->exec("INSERT INTO spp_periode (tahun_ajaran, nominal) VALUES 
        ('2023/2024', 500000),
        ('2024/2025', 550000)");

    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
