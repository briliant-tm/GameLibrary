<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Silakan login ulang.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// ── GET PROFILE ───────────────────────────────────────
if ($action === 'get_profile') {
    $stmt = $conn->prepare("SELECT username, nickname, email, avatar, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

// ── EDIT PROFILE ─────────────────────────────────────
if ($action === 'edit_profile') {
    $nickname = trim($_POST['nickname'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if (!$nickname || !$email) {
        echo json_encode(['success' => false, 'message' => 'Nickname dan email wajib diisi.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
        exit;
    }

    // Cek email duplikat (kecuali milik user sendiri)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan akun lain.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET nickname = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nickname, $email, $user_id);
    if ($stmt->execute()) {
        $_SESSION['nickname'] = $nickname;
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui!', 'nickname' => $nickname]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil.']);
    }
    exit;
}

// ── CHANGE PASSWORD ───────────────────────────────────
if ($action === 'change_password') {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';

    if (!$old_pass || !$new_pass) {
        echo json_encode(['success' => false, 'message' => 'Semua field password wajib diisi.']);
        exit;
    }
    if (strlen($new_pass) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!password_verify($old_pass, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password lama salah.']);
        exit;
    }

    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah password.']);
    }
    exit;
}

// ── DELETE ACCOUNT ────────────────────────────────────
if ($action === 'delete_account') {
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password salah. Akun tidak dihapus.']);
        exit;
    }

    // Hapus cover game milik user ini
    $stmt = $conn->prepare("SELECT cover_image FROM games WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $games = $stmt->get_result();
    while ($g = $games->fetch_assoc()) {
        if ($g['cover_image'] && file_exists('../' . $g['cover_image'])) {
            unlink('../' . $g['cover_image']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Akun berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus akun.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
