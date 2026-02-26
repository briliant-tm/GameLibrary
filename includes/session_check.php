<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nickname' => $_SESSION['nickname'],
        ]
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
