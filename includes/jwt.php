<?php
/**
 * JWT Helper - GameVault
 * Implementasi manual JWT tanpa library eksternal (HMAC-SHA256)
 */

define('JWT_SECRET', 'gamevault_secret_key_2024');
define('JWT_EXPIRY', 3600); // 1 jam

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

function generate_jwt($payload) {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['exp'] = time() + JWT_EXPIRY;
    $payload['iat'] = time();
    $body = base64url_encode(json_encode($payload));
    $signature = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$signature";
}

function verify_jwt($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $payload, $signature] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    if (!hash_equals($expected, $signature)) return null;

    $data = json_decode(base64url_decode($payload), true);
    if (!$data || $data['exp'] < time()) return null;

    return $data;
}

function get_bearer_token() {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
        return $matches[1];
    }
    return null;
}

function require_jwt_auth() {
    $token = get_bearer_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token tidak ditemukan. Silakan login.']);
        exit;
    }
    $payload = verify_jwt($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.']);
        exit;
    }
    return $payload;
}

function require_admin() {
    $user = require_jwt_auth();
    if (($user['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang diizinkan.']);
        exit;
    }
    return $user;
}
