<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, DELETE");

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
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

// Bisa terima POST biasa atau DELETE dengan JSON body
$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    $body = json_decode(file_get_contents("php://input"), true);
    $id   = (int)($body['id'] ?? 0);
}

if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID tidak valid."]);
    exit;
}

$game->id = $id;

// Ambil data dulu untuk hapus cover — sekaligus ownership check
$existing = $game->readOne();
if (!$existing) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Game tidak ditemukan."]);
    exit;
}

// Hapus file cover dari disk sebelum delete record
Game::deleteCover($existing['cover_image']);

if ($game->delete()) {
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Game berhasil dihapus."]);
} else {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "Gagal menghapus game."]);
}
?>
