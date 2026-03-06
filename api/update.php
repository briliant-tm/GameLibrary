<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");

// Terima POST karena FormData (multipart/file upload) tidak bisa dikirim via PUT di browser
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode(["message" => "Method tidak diizinkan."]);
    exit;
}

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Silakan login terlebih dahulu."]);
    exit;
}

include_once '../config/Database.php';
include_once '../models/Game.php';

$db   = (new Database())->getConnection();
$game = new Game($db);
$game->user_id = $_SESSION['user_id'];

$id       = (int)($_POST['id']       ?? 0);
$title    = trim($_POST['title']     ?? '');
$genre    = trim($_POST['genre']     ?? '');
$platform = trim($_POST['platform']  ?? '');
$notes    = trim($_POST['notes']     ?? '');

if (!$id || !$title || !$genre || !$platform) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Data tidak lengkap."]);
    exit;
}

$game->id = $id;

// Ambil data lama untuk keperluan cover — sekaligus ownership check
$existing = $game->readOne();
if (!$existing) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Game tidak ditemukan."]);
    exit;
}

$cover_path = $existing['cover_image'];

// Upload cover baru jika ada
if (!empty($_FILES['cover']['name'])) {
    $new_cover = Game::handleUpload($_FILES['cover']);
    if ($new_cover !== false) {
        Game::deleteCover($cover_path);   // hapus cover lama dari disk
        $cover_path = $new_cover;
    }
}

// Hapus cover jika user klik tombol "hapus cover"
if (($_POST['remove_cover'] ?? '0') === '1') {
    Game::deleteCover($cover_path);
    $cover_path = null;
}

$game->title       = $title;
$game->genre       = $genre;
$game->platform    = $platform;
$game->cover_image = $cover_path;
$game->notes       = $notes ?: null;

if ($game->update()) {
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Game berhasil diperbarui!"]);
} else {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "Gagal memperbarui game."]);
}
?>
