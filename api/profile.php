<?php
/**
 * api/profile.php
 * Endpoint profil yang diproteksi JWT
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method tidak diizinkan.']);
    exit;
}

// Middleware JWT - otomatis return 401 jika token tidak valid
$user = require_jwt_auth();

// Kalau sampai sini, token valid
echo json_encode([
    'success' => true,
    'user'    => [
        'user_id'  => $user['user_id'],
        'username' => $user['username'],
        'nickname' => $user['nickname'],
        'role'     => $user['role']
    ]
]);
