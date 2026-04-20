<?php
$conn = new mysqli("localhost", "root", "", "gamelibrary_db");

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Koneksi gagal: " . $conn->connect_error
    ]));
}