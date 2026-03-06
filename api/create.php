<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$title    = trim($_POST['title']    ?? '');
$genre    = trim($_POST['genre']    ?? '');
$platform = trim($_POST['platform'] ?? '');
$notes    = trim($_POST['notes']    ?? '');

if (!$title || !$genre || !$platform) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Judul, genre, dan platform wajib diisi."]);
    exit;
}

// Handle upload cover (opsional)
$cover_path = null;
if (!empty($_FILES['cover']['name'])) {
    $cover_path = Game::handleUpload($_FILES['cover']);
    if ($cover_path === false) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Gagal upload cover. Pastikan file JPG/PNG/WEBP maks 2MB."]);
        exit;
    }
}

$game->title       = $title;
$game->genre       = $genre;
$game->platform    = $platform;
$game->cover_image = $cover_path;
$game->notes       = $notes ?: null;

if ($game->create()) {
    http_response_code(201);
    echo json_encode(["success" => true, "message" => "Game berhasil ditambahkan!"]);
} else {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "Gagal menambahkan game."]);
}
?>
