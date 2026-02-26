<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ── GET ALL GAMES ─────────────────────────────────────
if ($action === 'get_games') {
    $filter_genre    = $_GET['genre'] ?? '';
    $filter_platform = $_GET['platform'] ?? '';
    $search          = $_GET['search'] ?? '';

    $sql    = "SELECT * FROM games WHERE user_id = ?";
    $params = [$user_id];
    $types  = "i";

    if ($filter_genre) {
        $sql .= " AND genre = ?";
        $params[] = $filter_genre;
        $types .= "s";
    }
    if ($filter_platform) {
        $sql .= " AND platform = ?";
        $params[] = $filter_platform;
        $types .= "s";
    }
    if ($search) {
        $sql .= " AND title LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $games = [];
    while ($row = $result->fetch_assoc()) {
        $games[] = $row;
    }
    echo json_encode(['success' => true, 'games' => $games]);
    exit;
}

// ── GET CATEGORIES (genre & platform lists) ───────────
if ($action === 'get_categories') {
    $genres = $conn->query(
        "SELECT DISTINCT genre FROM games WHERE user_id = $user_id ORDER BY genre"
    )->fetch_all(MYSQLI_ASSOC);

    $platforms = $conn->query(
        "SELECT DISTINCT platform FROM games WHERE user_id = $user_id ORDER BY platform"
    )->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success'   => true,
        'genres'    => array_column($genres, 'genre'),
        'platforms' => array_column($platforms, 'platform'),
    ]);
    exit;
}

// ── ADD GAME ──────────────────────────────────────────
if ($action === 'add_game') {
    $title    = trim($_POST['title'] ?? '');
    $genre    = trim($_POST['genre'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    if (!$title || !$genre || !$platform) {
        echo json_encode(['success' => false, 'message' => 'Judul, genre, dan platform wajib diisi.']);
        exit;
    }

    $cover_path = null;
    if (!empty($_FILES['cover']['name'])) {
        $cover_path = handleUpload($_FILES['cover']);
        if (!$cover_path) {
            echo json_encode(['success' => false, 'message' => 'Gagal upload cover. Pastikan file JPG/PNG/WEBP maks 2MB.']);
            exit;
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO games (user_id, title, genre, platform, cover_image, notes) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $user_id, $title, $genre, $platform, $cover_path, $notes);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Game berhasil ditambahkan!', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan game.']);
    }
    exit;
}

// ── EDIT GAME ─────────────────────────────────────────
if ($action === 'edit_game') {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $genre    = trim($_POST['genre'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    if (!$id || !$title || !$genre || !$platform) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }

    // Pastikan game milik user
    $stmt = $conn->prepare("SELECT cover_image FROM games WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Game tidak ditemukan.']);
        exit;
    }

    $cover_path = $existing['cover_image'];
    if (!empty($_FILES['cover']['name'])) {
        $new_cover = handleUpload($_FILES['cover']);
        if ($new_cover) {
            // Hapus cover lama
            if ($cover_path && file_exists('../' . $cover_path)) unlink('../' . $cover_path);
            $cover_path = $new_cover;
        }
    }

    // Hapus cover jika diminta
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
        if ($cover_path && file_exists('../' . $cover_path)) unlink('../' . $cover_path);
        $cover_path = null;
    }

    $stmt = $conn->prepare(
        "UPDATE games SET title = ?, genre = ?, platform = ?, cover_image = ?, notes = ? WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("sssssii", $title, $genre, $platform, $cover_path, $notes, $id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Game berhasil diperbarui!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui game.']);
    }
    exit;
}

// ── DELETE GAME ───────────────────────────────────────
if ($action === 'delete_game') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT cover_image FROM games WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();
    if (!$game) {
        echo json_encode(['success' => false, 'message' => 'Game tidak ditemukan.']);
        exit;
    }

    if ($game['cover_image'] && file_exists('../' . $game['cover_image'])) {
        unlink('../' . $game['cover_image']);
    }

    $stmt = $conn->prepare("DELETE FROM games WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Game berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus game.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);

// ── HELPER: Upload Cover ──────────────────────────────
function handleUpload($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return false;
    if ($file['size'] > 2 * 1024 * 1024) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('cover_') . '.' . $ext;
    $dest     = '../assets/uploads/covers/' . $filename;

    if (!is_dir('../assets/uploads/covers/')) {
        mkdir('../assets/uploads/covers/', 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'assets/uploads/covers/' . $filename;
    }
    return false;
}
