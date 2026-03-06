<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$action = $_GET['action'] ?? 'get_games';

// ── GET CATEGORIES ────────────────────────────────────────────
if ($action === 'get_categories') {
    $cat = $game->getCategories();
    http_response_code(200);
    echo json_encode(["success" => true] + $cat);
    exit;
}

// ── GET GAMES ─────────────────────────────────────────────────
$stmt  = $game->read(
    $_GET['genre']    ?? '',
    $_GET['platform'] ?? '',
    $_GET['search']   ?? ''
);
$games = $stmt->fetchAll();

if (count($games) > 0) {
    http_response_code(200);
    echo json_encode(["success" => true, "games" => $games]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "games" => [], "message" => "Data tidak ditemukan."]);
}
?>
