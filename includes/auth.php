<?php
session_start();
require_once 'connection.php';
require_once 'jwt.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

$action = $_POST['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '');

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
    $role   = 'user'; // default role
    $stmt   = $conn->prepare("INSERT INTO users (username, nickname, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $nickname, $email, $hashed, $role);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Akun berhasil dibuat! Silakan login.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat akun.']);
    }
    exit;
}

// ── LOGIN (Session) ────────────────────────────────────────────────
if ($action === 'login') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$identifier || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username/email dan password wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, nickname, email, password, role FROM users WHERE username = ? OR email = ?");
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
    $_SESSION['role']     = $user['role'];

    echo json_encode([
        'success'  => true,
        'message'  => 'Login berhasil!',
        'nickname' => $_SESSION['nickname']
    ]);
    exit;
}

// ── LOGIN JWT ────────────────────────────────────────────────────
if ($action === 'login_jwt') {
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $identifier = trim($body['identifier'] ?? $_POST['identifier'] ?? '');
    $password   = $body['password'] ?? $_POST['password'] ?? '';

    if (!$identifier || !$password) {
        echo json_encode(['success' => false, 'message' => 'Identifier dan password wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, nickname, email, password, role FROM users WHERE username = ? OR email = ?");
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

    $token = generate_jwt([
        'user_id'  => $user['id'],
        'username' => $user['username'],
        'nickname' => $user['nickname'] ?: $user['username'],
        'role'     => $user['role']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Login JWT berhasil!',
        'token'   => $token,
        'user'    => [
            'id'       => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'],
            'role'     => $user['role']
        ]
    ]);
    exit;
}

// ── LOGOUT ────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Berhasil logout.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
