<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'message' => 'Belum login']);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'data' => [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['role'] ?? 'user', // Default peran 'user' jika tidak diset
        'profile_pic' => $_SESSION['profile_pic'] ?? 'default.jpg'
    ]
]);
