-- ============================================
-- ARENA GO - Futsal Booking System Database
-- ============================================

CREATE DATABASE IF NOT EXISTS arenago_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arenago_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telepon VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lapangan (Fields) table
CREATE TABLE lapangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jenis ENUM('indoor', 'outdoor') DEFAULT 'indoor',
    harga_per_jam DECIMAL(10,2) NOT NULL,
    kapasitas INT DEFAULT 10,
    deskripsi TEXT,
    foto VARCHAR(255),
    status ENUM('tersedia', 'tidak_tersedia') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lapangan_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    total_jam INT NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'dikonfirmasi', 'dibatalkan', 'selesai') DEFAULT 'pending',
    catatan TEXT,
    bukti_bayar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lapangan_id) REFERENCES lapangan(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- Sample Data
-- ============================================

-- Admin user (password: admin123)
INSERT INTO users (nama, username, email, password, telepon, role) VALUES 
('Administrator', 'admin', 'admin@arenago.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin');

-- Regular user (password: user123)
INSERT INTO users (nama, username, email, password, telepon, role) VALUES 
('Budi Santoso', 'budi', 'budi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '082111222333', 'user');

-- Sample lapangan
INSERT INTO lapangan (nama, jenis, harga_per_jam, kapasitas, deskripsi, status) VALUES
('Lapangan A - Indoor Premium', 'indoor', 150000, 12, 'Lapangan futsal indoor premium dengan lantai vinyl berkualitas tinggi, pencahayaan LED, dan AC.', 'tersedia'),
('Lapangan B - Indoor Standard', 'indoor', 100000, 10, 'Lapangan futsal indoor standard dengan lantai semen halus dan pencahayaan cukup.', 'tersedia'),
('Lapangan C - Outdoor', 'outdoor', 75000, 14, 'Lapangan futsal outdoor dengan rumput sintetis berkualitas, cocok untuk bermain sore hari.', 'tersedia'),
('Lapangan D - VIP', 'indoor', 200000, 12, 'Lapangan VIP dengan fasilitas lengkap termasuk ruang ganti, shower, dan loker.', 'tersedia');
