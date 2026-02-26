<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ── REGISTER ──────────────────────────────────────────
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $nickname = trim($_POST['nickname'] ?? $username);
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan.']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, nickname, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $nickname, $email, $hashed);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Akun berhasil dibuat! Silakan login.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat akun.']);
    }
    exit;
}

// ── LOGIN ─────────────────────────────────────────────
if ($action === 'login') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$identifier || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username/email dan password wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, nickname, email, password FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Akun tidak ditemukan.']);
        exit;
    }

    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password salah.']);
        exit;
    }

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nickname'] = $user['nickname'] ?: $user['username'];
    echo json_encode(['success' => true, 'message' => 'Login berhasil!', 'nickname' => $_SESSION['nickname']]);
    exit;
}

// ── LOGOUT ────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
