<?php
/**
 * api/admin.php
 * Endpoint khusus admin - diproteksi JWT + role check
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/jwt.php';
require_once '../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method tidak diizinkan.']);
    exit;
}

// Middleware JWT + Admin role check
$user = require_admin();

// Hanya admin yang sampai sini
$db   = (new Database())->getConnection();
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM games");
$total_games = $stmt->fetch()['total'];

echo json_encode([
    'success' => true,
    'message' => 'Selamat datang, Admin!',
    'admin'   => $user['username'],
    'stats'   => [
        'total_users' => $total_users,
        'total_games' => $total_games
    ]
]);
