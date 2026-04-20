-- =============================================
-- GameVault Auth - Database Setup
-- Praktikum 6: Autentikasi dan Autorisasi
-- =============================================

CREATE DATABASE IF NOT EXISTS gamelibrary_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gamelibrary_db;

-- Tabel Users (ditambah kolom role untuk authorization)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    nickname VARCHAR(50),
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Games
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    genre VARCHAR(100) NOT NULL,
    platform VARCHAR(100) NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tambahkan kolom role jika database sudah ada
-- ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';

-- Data awal admin (password: admin123)
-- INSERT INTO users (username, nickname, email, password, role)
-- VALUES ('admin', 'Administrator', 'admin@gamevault.com', '$2y$10$...', 'admin');